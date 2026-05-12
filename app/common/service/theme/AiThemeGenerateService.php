<?php
declare(strict_types=1);

namespace app\common\service\theme;

use app\common\model\AiThemeRecord;
use app\common\service\ai\AiProviderFactory;
use app\common\service\ai\AiProviderInterface;
use think\facade\Log;

/**
 * AI主题生成编排服务 - V3.0 Phase 2
 *
 * 职责：
 * - Prompt组装与优化
 * - 调用AiProviderFactory获取Provider生成模板
 * - 解析LLM响应为文件列表
 * - 调用ThemeFileService落盘
 * - 调用ThemeValidatorService校验
 * - 状态机管理与日志记录
 */
class AiThemeGenerateService
{
    protected ThemeFileService $fileService;
    protected ThemeValidatorService $validatorService;
    protected ?AiProviderInterface $provider = null;

    public function __construct()
    {
        $this->fileService = new ThemeFileService();
        $this->validatorService = new ThemeValidatorService();
    }

    /**
     * 初始化Provider（延迟加载）
     */
    protected function getProvider(): AiProviderInterface
    {
        if ($this->provider === null) {
            $this->provider = AiProviderFactory::getDefault();
        }
        return $this->provider;
    }

    /**
     * 指定Provider名称创建实例
     */
    public function setProvider(string $providerName): static
    {
        $this->provider = AiProviderFactory::create($providerName);
        return $this;
    }

    /**
     * 创建生成任务（仅创建记录，不执行生成）
     *
     * @param int $userId 创建人ID
     * @param string $description 用户描述
     * @param array $options 生成选项
     * @return int 任务记录ID
     */
    public function createTask(int $userId, string $description, array $options = []): int
    {
        $themeName = 'ai-theme-' . date('Ymd') . '-' . substr(uniqid(), -6);

        $record = new AiThemeRecord();
        $record->user_id    = $userId;
        $record->theme_name = $themeName;
        $record->description = $description;
        $record->options    = $options;
        $record->status     = AiThemeRecord::STATUS_GENERATING;
        $record->save();

        Log::info("[AiThemeGenerate] 任务创建: record_id={$record->id}, theme_name={$themeName}");

        return (int) $record->id;
    }

    /**
     * 执行单个生成任务（由CLI命令调用）
     *
     * @param int $recordId 任务记录ID
     * @return array ['success'=>bool, 'message'=>string, 'record_id'=>int]
     */
    public function executeTask(int $recordId): array
    {
        $record = AiThemeRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'message' => '任务记录不存在', 'record_id' => $recordId];
        }

        $themeName = $record->theme_name;
        $baseDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;

        Log::info("[AiThemeGenerate] 开始生成: record_id={$recordId}, theme_name={$themeName}");

        try {
            // 1. 构建Prompt
            $prompt = $this->buildPrompt($record->description, $record->options ?: []);
            $record->prompt_log = $prompt;
            $record->save();

            // 2. 调用LLM生成
            $systemPrompt = $this->getSystemPrompt();
            $maxTokens = (int) config('ai.theme_generate.max_tokens', 8192);
            $temperature = (float) config('ai.theme_generate.temperature', 0.5);

            $llmResponse = $this->getProvider()->write($prompt, [
                'system_prompt' => $systemPrompt,
                'max_tokens'    => $maxTokens,
                'temperature'   => $temperature,
            ]);

            // 3. 解析响应为文件列表
            $files = $this->parseResponse($llmResponse);

            if (empty($files)) {
                throw new \RuntimeException('LLM响应未包含有效文件内容');
            }

            // 4. 文件落盘
            $writeResult = $this->fileService->writeThemeFiles($baseDir, $files);

            // 5. 校验流水线
            $validateResult = $this->validatorService->validate($baseDir);

            // 6. 更新记录状态
            if ($validateResult['passed']) {
                AiThemeRecord::markPendingReview(
                    $recordId,
                    $writeResult['files_tree']
                );
                Log::info("[AiThemeGenerate] 生成完成待审核: record_id={$recordId}, files={$writeResult['written_count']}");
            } else {
                AiThemeRecord::markValidateFailed($recordId, $validateResult);
                Log::warning("[AiThemeGenerate] 校验失败: record_id={$recordId}, summary={$validateResult['summary']}");
            }

            return [
                'success'    => true,
                'message'    => $validateResult['passed'] ? '生成完成，待审核' : '校验未通过',
                'record_id'  => $recordId,
                'validate'   => $validateResult,
                'files_count'=> $writeResult['written_count'],
            ];

        } catch (\Throwable $e) {
            // 回滚已写入的文件
            $this->fileService->rollback();

            $errorMsg = $e->getMessage();
            Log::error("[AiThemeGenerate] 生成失败: record_id={$recordId}, error={$errorMsg}");

            // 检查重试次数
            if ((int) $record->retry_count < 3) {
                AiThemeRecord::incrementRetry($recordId);
                AiThemeRecord::markFailed($recordId, $errorMsg);
            } else {
                AiThemeRecord::markFailed($recordId, "重试次数用尽: {$errorMsg}");
            }

            return [
                'success'   => false,
                'message'   => $errorMsg,
                'record_id' => $recordId,
            ];
        }
    }

    /**
     * 重新执行失败的任务
     */
    public function retryTask(int $recordId): array
    {
        $record = AiThemeRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'message' => '任务记录不存在'];
        }

        $validRetryStatuses = [
            AiThemeRecord::STATUS_GENERATE_FAILED,
            AiThemeRecord::STATUS_VALIDATE_FAILED,
            AiThemeRecord::STATUS_REJECTED,
        ];

        if (!in_array((int) $record->status, $validRetryStatuses, true)) {
            return ['success' => false, 'message' => '当前状态不允许重试'];
        }

        // 重置状态为生成中
        $record->status = AiThemeRecord::STATUS_GENERATING;
        $record->retry_count = (int) $record->retry_count + 1;
        $record->error_msg = null;
        $record->save();

        Log::info("[AiThemeGenerate] 任务重试: record_id={$recordId}, retry_count={$record->retry_count}");

        return $this->executeTask($recordId);
    }

    /**
     * 构建生成Prompt
     */
    protected function buildPrompt(string $description, array $options): string
    {
        $style = $options['style'] ?? '现代简约';
        $colorScheme = $options['color_scheme'] ?? '蓝色系';
        $layoutType = $options['layout_type'] ?? '响应式';
        $pageTypes = $options['page_types'] ?? ['首页', '列表页', '详情页'];

        $pagesText = implode('、', $pageTypes);

        return <<<PROMPT
请为AI-CMS内容管理系统生成一套完整的网站前台主题模板。

## 用户需求
{$description}

## 设计参数
- 风格: {$style}
- 色系: {$colorScheme}
- 布局: {$layoutType}
- 需要页面: {$pagesText}

## 技术约束
1. 使用ThinkPHP模板引擎语法（{volist}循环、{if}条件、{include}引入）
2. 必须包含独立的 layout.html 作为布局模板
3. CSS使用CSS变量（如 --i8j-primary, --i8j-bg, --i8j-text 等）
4. 图片使用占位符（如 /assets/placeholder/xxx.jpg）
5. JS使用原生JavaScript，不依赖jQuery等外部库
6. 所有模板文件使用UTF-8编码

## 输出格式要求
请按以下格式返回每个文件：

```file:路径/文件名.扩展名
文件内容
```

例如：
```file:pc/layout.html
<!DOCTYPE html>
<html>
<head>...</head>
<body>...</body>
</html>
```

必须包含的文件：
- theme.json（主题元信息）
- pc/layout.html（PC端布局）
- pc/index.html（PC端首页）
- mobile/layout.html（移动端布局，如需要）
- assets/css/style.css（样式文件）
- assets/js/main.js（脚本文件）

请确保生成完整、可直接运行的模板代码。
PROMPT;
    }

    /**
     * 获取System Prompt
     */
    protected function getSystemPrompt(): string
    {
        return '你是一个专业的前端开发工程师，精通ThinkPHP模板引擎、CSS变量和现代响应式网页设计。你的任务是根据用户描述生成完整可用的CMS前台主题模板。请确保代码规范、语义化HTML、可访问性良好。只输出文件内容，不要输出解释性文字。';
    }

    /**
     * 解析LLM响应为文件列表
     */
    protected function parseResponse(string $response): array
    {
        $files = [];

        // 匹配 ```file:path
        // content
        // ``` 格式
        $pattern = '/```file:([^\n]+)\n(.*?)```/s';
        if (preg_match_all($pattern, $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $path = trim($match[1]);
                $content = $match[2];

                // 去除可能的换行符前缀
                $content = ltrim($content, "\n\r");

                $files[] = [
                    'path'    => $path,
                    'content' => $content,
                ];
            }
        }

        return $files;
    }

    /**
     * 检查今日生成次数是否已达上限
     */
    public function checkDailyLimit(): bool
    {
        $limit = (int) config('ai.theme_generate.daily_limit', 50);
        $todayCount = AiThemeRecord::getTodayCount();
        return $todayCount < $limit;
    }

    /**
     * 获取今日剩余次数
     */
    public function getRemainingQuota(): int
    {
        $limit = (int) config('ai.theme_generate.daily_limit', 50);
        $todayCount = AiThemeRecord::getTodayCount();
        return max(0, $limit - $todayCount);
    }

    // ==================== V3.0 Phase 3: 增量修改（同步模式） ====================

    /**
     * 增量修改（多轮对话）—— 同步模式
     *
     * @param int $recordId 主题记录ID
     * @param int $userId 用户ID
     * @param string $instruction 用户修改指令
     * @return array ['success'=>bool, 'message'=>string, 'changed_files'=>array, 'validate_result'=>array]
     */
    public function generateIncremental(int $recordId, int $userId, string $instruction): array
    {
        $record = AiThemeRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'message' => '记录不存在'];
        }

        if (!$record->canModify()) {
            return ['success' => false, 'message' => '当前状态不允许修改'];
        }

        $themeName = $record->theme_name;
        $baseDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;

        if (!is_dir($baseDir)) {
            return ['success' => false, 'message' => '主题目录不存在'];
        }

        // 1. 版本备份
        $versionManager = new ThemeVersionManager();
        $backupResult = $versionManager->backupBeforeChange($themeName, $record->getCurrentVersion(), $instruction);
        if (!$backupResult['success']) {
            Log::warning("[AiThemeGenerate] 备份失败，继续执行: record_id={$recordId}");
        }

        // 2. 读取当前文件快照
        $currentFiles = $this->scanThemeFiles($baseDir);

        // 3. 构建 Prompt
        $contextBuilder = new IncrementalContextBuilder();
        $promptData = $contextBuilder->buildIncrementalPrompt($record, $instruction, $currentFiles);

        // 4. 检查 Token 预算
        if ($promptData['context_tokens'] > $contextBuilder->contextBudget) {
            return ['success' => false, 'message' => '对话上下文过长，请新建对话'];
        }

        // 5. 记录用户消息
        $currentVersion = $record->getCurrentVersion();
        AiThemeChatLog::logMessage($recordId, $currentVersion, $userId, AiThemeChatLog::ROLE_USER, $instruction);

        try {
            // 6. 调用 LLM（同步，直接等待）
            $maxTokens = (int) config('ai.theme_chat.max_tokens', 8192);
            $llmResponse = $this->getProvider()->write($promptData['prompt'], [
                'system_prompt' => $promptData['system_prompt'],
                'max_tokens'    => $maxTokens,
                'temperature'   => 0.3,
            ]);

            // 7. 解析响应
            $files = $this->parseResponse($llmResponse);

            if (empty($files)) {
                throw new \RuntimeException('LLM 未返回有效文件内容');
            }

            // 8. 处理文件变更（写入/删除）
            $changedFiles = [];
            $writeFiles = [];
            foreach ($files as $file) {
                $path = $file['path'] ?? '';
                $content = $file['content'] ?? '';
                if (empty($path)) {
                    continue;
                }
                if ($content === '[DELETE]') {
                    $this->fileService->deleteThemeFile($baseDir, $path);
                    $changedFiles[] = $path . ' (删除)';
                } else {
                    $writeFiles[] = $file;
                    $changedFiles[] = $path;
                }
            }

            // 9. 写入变更文件
            if (!empty($writeFiles)) {
                $this->fileService->writeThemeFiles($baseDir, $writeFiles);
            }

            // 10. 单文件校验（只校验变更的文件）
            $validateErrors = [];
            foreach ($writeFiles as $file) {
                $filePath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['path']);
                $fileResult = $this->validatorService->validateFile($filePath);
                if (!$fileResult['passed']) {
                    $validateErrors[] = $file['path'] . ': ' . $fileResult['summary'];
                }
            }

            // 11. 版本号递增
            $record->bumpVersion();
            $record->save();

            // 12. 记录 AI 响应
            AiThemeChatLog::logMessage(
                $recordId,
                $record->getCurrentVersion(),
                $userId,
                AiThemeChatLog::ROLE_AI,
                $llmResponse,
                $changedFiles
            );

            // 13. 更新文件树
            $newFilesTree = $this->fileService->scanFilesTree($baseDir);
            $record->files_tree = $newFilesTree;
            $record->save();

            Log::info("[AiThemeGenerate] 增量修改成功: record_id={$recordId}, version={$record->version}, files=" . implode(',', $changedFiles));

            return [
                'success'         => true,
                'message'         => empty($validateErrors) ? '修改成功' : '修改成功，部分文件校验未通过: ' . implode('; ', $validateErrors),
                'changed_files'   => $changedFiles,
                'version'         => $record->getCurrentVersion(),
                'validate_errors' => $validateErrors,
            ];

        } catch (\Throwable $e) {
            Log::error("[AiThemeGenerate] 增量修改失败: record_id={$recordId}, error=" . $e->getMessage());
            return [
                'success' => false,
                'message' => '修改失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 单文件重生成 —— 同步模式
     *
     * @param int $recordId 主题记录ID
     * @param int $userId 用户ID
     * @param string $filePath 目标文件相对路径
     * @param string $instruction 修改指令
     * @return array ['success'=>bool, 'message'=>string, 'content'=>string]
     */
    public function regenerateFile(int $recordId, int $userId, string $filePath, string $instruction): array
    {
        $record = AiThemeRecord::find($recordId);
        if (!$record) {
            return ['success' => false, 'message' => '记录不存在'];
        }

        if (!$record->canModify()) {
            return ['success' => false, 'message' => '当前状态不允许修改'];
        }

        $themeName = $record->theme_name;
        $baseDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);

        if (!is_file($fullPath)) {
            return ['success' => false, 'message' => '文件不存在: ' . $filePath];
        }

        $fileContent = file_get_contents($fullPath) ?: '';

        // 1. 版本备份
        $versionManager = new ThemeVersionManager();
        $backupResult = $versionManager->backupBeforeChange($themeName, $record->getCurrentVersion(), "修改 {$filePath}");

        // 2. 构建 Prompt
        $contextBuilder = new IncrementalContextBuilder();
        $promptData = $contextBuilder->buildFileRegeneratePrompt($record, $filePath, $fileContent, $instruction);

        // 3. 记录用户消息
        $currentVersion = $record->getCurrentVersion();
        AiThemeChatLog::logMessage($recordId, $currentVersion, $userId, AiThemeChatLog::ROLE_USER, "修改 {$filePath}: {$instruction}");

        try {
            // 4. 调用 LLM
            $maxTokens = (int) config('ai.theme_chat.max_tokens', 8192);
            $llmResponse = $this->getProvider()->write($promptData['prompt'], [
                'system_prompt' => $promptData['system_prompt'],
                'max_tokens'    => $maxTokens,
                'temperature'   => 0.3,
            ]);

            // 5. 解析响应
            $files = $this->parseResponse($llmResponse);

            if (empty($files)) {
                throw new \RuntimeException('LLM 未返回有效文件内容');
            }

            $newFile = $files[0];
            $newContent = $newFile['content'] ?? '';

            // 6. 写入文件
            $this->fileService->writeThemeFiles($baseDir, [['path' => $filePath, 'content' => $newContent]]);

            // 7. 单文件校验
            $validateResult = $this->validatorService->validateFile($fullPath);

            // 8. 版本号递增
            $record->bumpVersion();
            $record->save();

            // 9. 记录 AI 响应
            AiThemeChatLog::logMessage(
                $recordId,
                $record->getCurrentVersion(),
                $userId,
                AiThemeChatLog::ROLE_AI,
                $llmResponse,
                [$filePath]
            );

            // 10. 更新文件树
            $newFilesTree = $this->fileService->scanFilesTree($baseDir);
            $record->files_tree = $newFilesTree;
            $record->save();

            Log::info("[AiThemeGenerate] 单文件重生成成功: record_id={$recordId}, file={$filePath}, version={$record->version}");

            return [
                'success'          => true,
                'message'          => $validateResult['passed'] ? '文件修改成功' : '文件已修改，校验未通过: ' . $validateResult['summary'],
                'content'          => $newContent,
                'validate_result'  => $validateResult,
                'version'          => $record->getCurrentVersion(),
            ];

        } catch (\Throwable $e) {
            Log::error("[AiThemeGenerate] 单文件重生成失败: record_id={$recordId}, file={$filePath}, error=" . $e->getMessage());
            return [
                'success' => false,
                'message' => '文件修改失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 扫描主题目录获取文件内容快照
     */
    protected function scanThemeFiles(string $baseDir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
            if (in_array($file->getExtension(), ['html', 'css', 'js', 'json'], true)) {
                $files[$relPath] = file_get_contents($file->getPathname()) ?: '';
            }
        }

        return $files;
    }
}
