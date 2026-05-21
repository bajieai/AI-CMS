<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
        // 同步batch_id到独立列（便于查询）
        if (!empty($options['batch_id'])) {
            $record->batch_id = $options['batch_id'];
        }
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
    public function executeTask(int $recordId, ?float $overrideTemp = null): array
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
            $temperature = $overrideTemp ?? (float) config('ai.theme_generate.temperature', 0.5);

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

            // 4.5 自动创建皮肤目录（public/skin/themes/{theme}/pc/）
            $this->ensureSkinAssets($themeName, $baseDir);

            // 5. 校验流水线（新模板，使用65分阈值）
            $validateResult = $this->validatorService->validate($baseDir, true);

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
     * V2.9.8 B-2: 重新执行失败的任务（增强版：3次指数退避+动态temperature+错误分类+每日上限）
     */
    public function retryTask(int $recordId, int $maxRetries = 3): array
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

        // 每日重试上限检查
        $dailyLimit = (int) config('ai.retry_daily_limit', 20);
        $todayCount = AiThemeRecord::where('retry_count', '>', 0)
            ->whereDay('update_time', date('Y-m-d'))
            ->count();
        if ($todayCount >= $dailyLimit) {
            Log::warning("[AiThemeGenerate] 每日重试上限触发: {$todayCount}/{$dailyLimit}");
            return ['success' => false, 'message' => '今日重试次数已达上限(' . $dailyLimit . ')，请明日再试'];
        }

        $backoff = [1, 3, 5];      // 退避间隔（秒）
        $temperatures = [0.7, 0.8, 0.9]; // 动态temperature
        $errors = [];
        $attempts = 0;

        for ($i = 0; $i <= $maxRetries; $i++) {
            if ($i > 0) {
                $sleepTime = $backoff[min($i - 1, count($backoff) - 1)];
                Log::info("[AiThemeGenerate] 重试退避: record_id={$recordId}, attempt={$i}, sleep={$sleepTime}s");
                sleep($sleepTime);
            }

            // 重置状态
            $record->status = AiThemeRecord::STATUS_GENERATING;
            $record->retry_count = (int) $record->retry_count + 1;
            $record->error_msg = null;
            $record->save();

            $temp = $i > 0 ? $temperatures[min($i - 1, count($temperatures) - 1)] : null;
            $result = $this->executeTask($recordId, $temp);
            $attempts++;

            if ($result['success'] && ($result['validate']['passed'] ?? false)) {
                Log::info("[AiThemeGenerate] 重试成功: record_id={$recordId}, attempts={$attempts}");
                return [
                    'success' => true,
                    'message' => '重试成功（第' . $attempts . '次）',
                    'record_id' => $recordId,
                    'attempts' => $attempts,
                ];
            }

            // 错误分类
            $errMsg = $result['message'] ?? '未知错误';
            $errors[] = [
                'attempt' => $attempts,
                'type' => $this->classifyError($errMsg, $result),
                'message' => $errMsg,
            ];

            Log::warning("[AiThemeGenerate] 重试失败: record_id={$recordId}, attempt={$attempts}, type={$errors[count($errors)-1]['type']}");
        }

        // 全部失败
        $lastError = $errors[count($errors) - 1]['message'] ?? '重试次数已耗尽';
        $record->status = AiThemeRecord::STATUS_GENERATE_FAILED;
        $record->error_msg = "[重试{$attempts}次均失败] " . $lastError;
        $record->save();

        return [
            'success' => false,
            'message' => '重试' . $attempts . '次后仍失败: ' . $lastError,
            'record_id' => $recordId,
            'attempts' => $attempts,
            'errors' => $errors,
        ];
    }

    /**
     * 错误分类
     */
    protected function classifyError(string $error, array $result = []): string
    {
        $lower = strtolower($error);
        if (str_contains($lower, 'timeout') || str_contains($lower, 'timed out') || str_contains($lower, '连接超时')) {
            return 'llm_timeout';
        }
        if (str_contains($lower, 'refused') || str_contains($lower, 'safety') || str_contains($lower, 'rejected')) {
            return 'llm_rejected';
        }
        if (str_contains($lower, 'tpl-ai-') || str_contains($lower, '语法') || str_contains($lower, 'syntax')) {
            return 'syntax_validation';
        }
        if (str_contains($lower, 'quality_low') || str_contains($lower, '质量')) {
            return 'quality_failed';
        }
        if (!$result['success'] ?? true) {
            return 'generation_failed';
        }
        return 'unknown';
    }

    /**
     * 自动创建主题皮肤目录（public/skin/themes/）
     * 确保浏览器可通过 {$skin} 访问 CSS/JS/图片资源
     */
    protected function ensureSkinAssets(string $themeName, string $baseDir): void
    {
        $skinBase = root_path() . 'public' . DIRECTORY_SEPARATOR . 'skin'
            . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeName;

        // === 修复layout.html: {__CONTENT__} → {block name="content"}{/block} ===
        foreach (['pc', 'mobile'] as $device) {
            $layoutFile = $baseDir . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR . 'layout.html';
            if (file_exists($layoutFile)) {
                $content = file_get_contents($layoutFile);
                if (strpos($content, '{__CONTENT__}') !== false) {
                    $content = str_replace('{__CONTENT__}', '{block name="content"}{/block}', $content);
                    file_put_contents($layoutFile, $content, LOCK_EX);
                    Log::info("[AiThemeGenerate] 修复{$device}/layout.html: __CONTENT__→block");
                }
            }
        }

        // === 读取theme.json获取主题色 ===
        $themeColor = '#3b82f6';
        $jsonPath = $baseDir . DIRECTORY_SEPARATOR . 'theme.json';
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if (!empty($json['color'])) {
                $themeColor = $json['color'];
            }
        }
        // 命名色转十六进制
        $namedColors = [
            'red' => '#e53935', 'green' => '#43a047', 'blue' => '#1a73e8',
            'yellow' => '#fdd835', 'orange' => '#fb8c00', 'purple' => '#8e24aa', 'pink' => '#d81b60',
        ];
        if (isset($namedColors[$themeColor])) {
            $themeColor = $namedColors[$themeColor];
        }

        // === 为pc和mobile创建皮肤目录+文件 ===
        foreach (['pc', 'mobile'] as $device) {
            $skinDir = $skinBase . DIRECTORY_SEPARATOR . $device;

            // 创建子目录
            foreach (['css', 'js', 'images'] as $subDir) {
                $dir = $skinDir . DIRECTORY_SEPARATOR . $subDir;
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }

            // style.css
            $cssFile = $skinDir . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css';
            if (!file_exists($cssFile)) {
                $css = ":root {\n"
                    . "    --primary: {$themeColor};\n"
                    . "    --bg: #ffffff;\n"
                    . "    --bg-alt: #f5f5f5;\n"
                    . "    --text: #333333;\n"
                    . "    --border: #e0e0e0;\n"
                    . "    --radius: 8px;\n"
                    . "    --shadow: 0 2px 8px rgba(0,0,0,0.1);\n"
                    . "}\n"
                    . "* { margin: 0; padding: 0; box-sizing: border-box; }\n"
                    . "body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: var(--text); background: var(--bg); line-height: 1.6; }\n"
                    . "a { color: var(--primary); text-decoration: none; }\n"
                    . ".top-bar { background: var(--primary); color: #fff; text-align: center; padding: 10px 0; font-size: 14px; }\n"
                    . ".main-content .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }\n"
                    . ".main-content section { padding: 40px 0; }\n"
                    . ".main-content section:nth-child(even) { background: var(--bg-alt); }\n"
                    . ".product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }\n"
                    . ".product-card { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }\n"
                    . ".category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 15px; }\n"
                    . ".mobile-menu { display: none; }\n"
                    . ".mobile-menu.active { display: block; position: fixed; top: 60px; left: 0; right: 0; background: #fff; z-index: 99; }\n"
                    . ".menu-toggle { display: none; background: none; border: none; cursor: pointer; padding: 10px; }\n"
                    . "@media (max-width: 768px) { .menu-toggle { display: block; } .header-nav { display: none; } }\n";
                file_put_contents($cssFile, $css, LOCK_EX);
                Log::info("[AiThemeGenerate] 创建style.css: {$device}, color={$themeColor}");
            }

            // main.js
            $jsFile = $skinDir . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'main.js';
            if (!file_exists($jsFile)) {
                $js = "document.addEventListener('DOMContentLoaded',function(){\n"
                    . "var b=document.getElementById('menuToggle'),m=document.getElementById('mobileMenu');\n"
                    . "if(b&&m){b.addEventListener('click',function(){m.classList.toggle('active')})}\n"
                    . "});\n";
                file_put_contents($jsFile, $js, LOCK_EX);
            }

            // 占位图片（logo.png + 通用）
            $imgDir = $skinDir . DIRECTORY_SEPARATOR . 'images';
            $placeholders = [
                'logo.png', 'banner1.png', 'banner2.png',
                'category1.png', 'category2.png', 'category3.png', 'category4.png', 'category5.png',
                'product1.png', 'product2.png', 'product3.png',
            ];
            foreach ($placeholders as $img) {
                $imgPath = $imgDir . DIRECTORY_SEPARATOR . $img;
                if (!file_exists($imgPath)) {
                    $label = pathinfo($img, PATHINFO_FILENAME);
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">'
                        . '<rect width="200" height="200" fill="' . $themeColor . '" opacity="0.1"/>'
                        . '<text x="100" y="105" text-anchor="middle" font-family="Arial,sans-serif" font-size="14" fill="' . $themeColor . '" opacity="0.5">' . htmlspecialchars($label) . '</text>'
                        . '</svg>';
                    file_put_contents($imgPath, $svg, LOCK_EX);
                }
            }
        }

        // V2.9.8 B-1: AfterGenerate钩子扩展
        try {
            $this->extractAssetPathsAndSync($themeName, $baseDir);
            $this->generateSkeletonCss($themeName, $baseDir);
            $this->generateTransparentPlaceholders($themeName);
            Log::info("[AiThemeGenerate] AfterGenerate钩子完成: theme={$themeName}");
        } catch (\Throwable $e) {
            Log::warning("[AiThemeGenerate] AfterGenerate钩子异常: theme={$themeName}, error=" . $e->getMessage());
        }

        Log::info("[AiThemeGenerate] 皮肤目录初始化完成: theme={$themeName}");
    }

    /**
     * V2.9.8 B-1: 从HTML提取CSS/JS路径并同步到皮肤目录
     */
    protected function extractAssetPathsAndSync(string $themeName, string $baseDir): void
    {
        $skinBase = root_path() . 'public' . DIRECTORY_SEPARATOR . 'skin'
            . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeName;

        $cssPaths = [];
        $jsPaths = [];

        foreach (['pc', 'mobile'] as $device) {
            $htmlDir = $baseDir . DIRECTORY_SEPARATOR . $device;
            if (!is_dir($htmlDir)) continue;

            foreach (glob($htmlDir . '/*.html') as $htmlFile) {
                $content = file_get_contents($htmlFile);
                // 提取CSS路径
                if (preg_match_all('/href=["\']([^"\']+\.css[^"\']*?)["\']/i', $content, $m)) {
                    foreach ($m[1] as $path) {
                        $normalized = $this->normalizeSkinPath($path, $themeName);
                        if ($normalized) $cssPaths[] = $normalized;
                    }
                }
                // 提取JS路径
                if (preg_match_all('/src=["\']([^"\']+\.js[^"\']*?)["\']/i', $content, $m)) {
                    foreach ($m[1] as $path) {
                        $normalized = $this->normalizeSkinPath($path, $themeName);
                        if ($normalized) $jsPaths[] = $normalized;
                    }
                }
            }
        }

        // 创建空占位文件
        foreach (array_unique($cssPaths) as $path) {
            $fullPath = $skinBase . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR . $path;
            $dir = dirname($fullPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (!file_exists($fullPath)) {
                file_put_contents($fullPath, "/* Auto-generated CSS placeholder for {$themeName} */\n", LOCK_EX);
            }
        }
        foreach (array_unique($jsPaths) as $path) {
            $fullPath = $skinBase . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR . $path;
            $dir = dirname($fullPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (!file_exists($fullPath)) {
                file_put_contents($fullPath, "/* Auto-generated JS placeholder for {$themeName} */\n", LOCK_EX);
            }
        }
    }

    /**
     * 规范化皮肤路径（{$skin} → 相对路径）
     */
    protected function normalizeSkinPath(string $path, string $themeName): ?string
    {
        // 处理 {$skin} 变量
        if (strpos($path, '{$skin}') !== false || strpos($path, '{\\$skin}') !== false) {
            $path = preg_replace('/\{\\?\$skin\}/', '', $path);
            $path = ltrim($path, '/');
        }
        // 只保留相对路径
        if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
            return null;
        }
        $path = ltrim($path, '/');
        // 过滤掉 assets/lib/ 等外部资源
        if (strpos($path, 'assets/') === 0 || strpos($path, 'skin/') === 0) {
            return null;
        }
        return $path;
    }

    /**
     * V2.9.8 B-1/A-2: 从HTML class名推导基础CSS骨架（增强版）
     * 集成CssComponentLibrary行业组件模式
     */
    protected function generateSkeletonCss(string $themeName, string $baseDir): void
    {
        // 读取行业类型（从options或theme.json）
        $industryType = 'corporate'; // 默认
        $jsonPath = $baseDir . DIRECTORY_SEPARATOR . 'theme.json';
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if (!empty($json['industry_type'])) {
                $industryType = $json['industry_type'];
            }
        }

        // V2.9.8 A-2: 使用CssComponentLibrary生成行业组件CSS
        $library = new CssComponentLibrary();
        $componentCss = $library->getAllCssForIndustry($industryType);

        // 保留原有的class推导逻辑（作为补充）
        $classes = [];
        foreach (['pc', 'mobile'] as $device) {
            $htmlDir = $baseDir . DIRECTORY_SEPARATOR . $device;
            if (!is_dir($htmlDir)) continue;
            foreach (glob($htmlDir . '/*.html') as $htmlFile) {
                $content = file_get_contents($htmlFile);
                if (preg_match_all('/class=["\']([^"\']+)["\']/i', $content, $m)) {
                    foreach ($m[1] as $classAttr) {
                        foreach (explode(' ', $classAttr) as $c) {
                            $c = trim($c);
                            if ($c && !str_starts_with($c, 'tpl-') && !str_starts_with($c, 'ai-')) {
                                $classes[] = $c;
                            }
                        }
                    }
                }
            }
        }
        $classes = array_unique($classes);

        // 按命名模式分类
        $patterns = [
            'layout'  => '/^(container|wrapper|layout|grid|row|col|main-wrap)/i',
            'header'  => '/^(header|nav|navbar|menu|topbar|site-header)/i',
            'content' => '/^(content|main|article|post|section|page|entry)/i',
            'footer'  => '/^(footer|bottom|copyright|site-footer)/i',
            'widget'  => '/^(widget|sidebar|aside|panel|card|box)/i',
            'button'  => '/^(btn|button|submit)/i',
        ];

        $groups = array_fill_keys(array_keys($patterns), []);
        foreach ($classes as $class) {
            foreach ($patterns as $group => $pattern) {
                if (preg_match($pattern, $class)) {
                    $groups[$group][] = $class;
                    break;
                }
            }
        }

        // 读取themeColor
        $themeColor = '#2563EB';
        if (file_exists($jsonPath)) {
            $json2 = json_decode(file_get_contents($jsonPath), true);
            if (!empty($json2['color'])) $themeColor = $json2['color'];
        }
        $namedColors = ['red'=>'#e53935','green'=>'#43a047','blue'=>'#1a73e8','yellow'=>'#fdd835','orange'=>'#fb8c00','purple'=>'#8e24aa','pink'=>'#d81b60'];
        if (isset($namedColors[$themeColor])) $themeColor = $namedColors[$themeColor];

        // class推导CSS（仅补充组件库未覆盖的动态class名）
        $derivedCssLines = [];
        $added = [];
        $rules = [
            'layout'  => "{ max-width: 1200px; margin: 0 auto; padding: 0 15px; }",
            'header'  => "{ background: {$themeColor}; color: #fff; padding: 15px 0; }",
            'content' => "{ padding: 40px 0; }",
            'footer'  => "{ background: #f8f9fa; padding: 20px 0; text-align: center; color: #666; }",
            'widget'  => "{ background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; }",
            'button'  => "{ display: inline-block; padding: 8px 16px; background: {$themeColor}; color: #fff; border: none; border-radius: 4px; cursor: pointer; transition: all 0.3s; }",
        ];

        foreach ($groups as $group => $groupClasses) {
            if (empty($groupClasses)) continue;
            foreach ($groupClasses as $class) {
                if (isset($added[$class])) continue;
                $added[$class] = true;
                // 仅添加组件库中未定义的class
                $derivedCssLines[] = ".{$class} " . ($rules[$group] ?? "{ }");
            }
        }

        // 合并：组件库CSS + class推导CSS
        $finalCss = $componentCss;
        if (!empty($derivedCssLines)) {
            $finalCss .= "\n/* V2.9.8 A-2: Dynamic class mappings */\n";
            $finalCss .= implode("\n", $derivedCssLines) . "\n";
        }

        // 追加hover和响应式
        $finalCss .= "\n/* V2.9.8: 交互与响应式骨架 */\n";
        $finalCss .= ".btn:hover, .button:hover { opacity: 0.9; transform: translateY(-1px); }\n";
        $finalCss .= ".card:hover, .widget:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }\n";
        $finalCss .= "@media (max-width: 768px) { .container, .wrapper { padding: 0 10px; } }\n";

        $skinBase = root_path() . 'public' . DIRECTORY_SEPARATOR . 'skin'
            . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeName;
        foreach (['pc', 'mobile'] as $device) {
            $cssFile = $skinBase . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css';
            if (file_exists($cssFile)) {
                $existing = file_get_contents($cssFile);
                if (strpos($existing, 'Component Library') === false) {
                    file_put_contents($cssFile, $existing . "\n" . $finalCss, LOCK_EX);
                }
            }
        }
    }

    /**
     * V2.9.8 B-1: 生成1x1透明PNG占位图（替代彩色SVG）
     */
    protected function generateTransparentPlaceholders(string $themeName): void
    {
        // 1x1 transparent PNG (base64 encoded)
        $transparentPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $skinBase = root_path() . 'public' . DIRECTORY_SEPARATOR . 'skin'
            . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeName;

        foreach (['pc', 'mobile'] as $device) {
            $imgDir = $skinBase . DIRECTORY_SEPARATOR . $device . DIRECTORY_SEPARATOR . 'images';
            if (!is_dir($imgDir)) continue;
            foreach (glob($imgDir . '/*') as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['svg', 'png', 'jpg', 'jpeg'])) {
                    // 如果现有占位图是SVG（彩色），替换为透明PNG
                    $content = file_get_contents($file);
                    if (strpos($content, '<svg') !== false || strpos($content, '<SVG') !== false) {
                        $newPath = preg_replace('/\.svg$/i', '.png', $file);
                        file_put_contents($newPath, $transparentPng, LOCK_EX);
                        @unlink($file);
                    }
                }
            }
        }
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
        $industryType = $options['industry_type'] ?? config('theme_styles.default_industry', 'corporate');

        $pagesText = implode('、', $pageTypes);

        // V2.9.8 A-1: 行业风格Prompt
        $industryPrompt = $this->buildIndustryPrompt($industryType);

        // V2.9.8 A-1: 模板变量扩展
        $variableExtensions = $this->buildExtendedVariables();

        return <<<PROMPT
请为AI-CMS内容管理系统生成一套完整的网站前台主题模板。

## 用户需求
{$description}

## 设计参数
- 风格: {$style}
- 色系: {$colorScheme}
- 布局: {$layoutType}
- 需要页面: {$pagesText}
- 行业类型: {$industryType}

{$industryPrompt}

{$variableExtensions}

## 技术约束
1. 使用ThinkPHP模板引擎语法（{volist}循环、{if}条件、{include}引入、{extend}继承、{block}区块）
2. 必须包含独立的 layout.html 作为布局模板
3. 重要：PC端页面模板中使用 {extend name="layout" /} 而非 {extend name="pc/layout" /}（引擎的 view_path 已定位到 pc/ 目录，因此路径无需重复 pc/ 段）；同理 {include file="layout" /} 而非 {include file="pc/layout" /}
4. layout.html中用 {block name="content"}{/block} 标记主要内容区域，子模板用 {block name="content"}...{/block} 覆盖
5. CSS样式写在layout.html的&lt;style&gt;标签内，使用CSS变量（如 --primary, --bg, --text 等）
6. 如需引用外部静态资源（CSS/JS/图片），必须使用 {\$skin} 模板变量前缀！
   正确示例：href="{\$skin}css/style.css"、src="{\$skin}js/main.js"、src="{\$skin}images/logo.png"
   错误示例：href="/assets/css/style.css"、src="/assets/js/main.js"
   {\$skin} 在运行时解析为 /skin/themes/{主题名}/{设备类型}/，对应 public/skin/ 目录
7. 图片占位符使用 {\$skin}images/xxx.png 路径（系统会自动生成占位图）
8. JS使用原生JavaScript，不依赖jQuery等外部库
9. 所有模板文件使用UTF-8编码

## 禁止使用的错误语法（严格遵守！）
以下语法在ThinkPHP模板引擎中无效或会导致编译错误，绝对不能使用：
- ❌ {__CONTENT__} — 用 {block name="content"}{/block} 替代
- ❌ {/elseif condition="..."} — elseif 无闭合标签，正确写法是 {elseif condition="..."}
- ❌ {\$var|date='Y-m-d',###} — 去掉 ,###，正确写法是 {\$var|date='Y-m-d'}
- ❌ {elseif condition="..."/} — 去掉末尾 /，正确写法是 {elseif condition="..."}
- ❌ /assets/css/ 或 /assets/js/ — 用 {\$skin}css/ 或 {\$skin}js/ 替代
- ❌ {include file="pc/xxx"} — 用 {include file="xxx"} 替代（view_path 已含 pc/）

## CMS可用模板变量清单（只使用以下变量，不要自创变量！）
系统已注入以下模板变量，可直接在模板中使用：

### 全局变量（所有页面可用）
- {\$site_name} — 网站名称
- {\$site_keywords} — 网站关键词
- {\$site_description} — 网站描述
- {\$site_logo} — 网站Logo图片URL
- {\$brand_name} — 品牌名称
- {\$skin} — 主题资源路径前缀（如 /skin/themes/xxx/pc/）
- {\$current_lang} — 当前语言
- {\$year} — 当前年份
- {\$current_page} — 当前页面标识（用于导航高亮）

### 内容列表变量（列表页可用，由控制器注入）
- {\$category} — 当前分类信息数组
- {\$category.id} / {\$category.name} / {\$category.subtitle}
- {\$pages} — 分页信息
- {volist name="list" id="vo"} — 内容列表循环
  - {\$vo.id} / {\$vo.title} / {\$vo.subtitle} / {\$vo.image}
  - {\$vo.description} / {\$vo.create_time} / {\$vo.url}

### 内容详情变量（详情页可用）
- {\$info} — 内容详情数组
- {\$info.id} / {\$info.title} / {\$info.content} / {\$info.image}
- {\$info.create_time} / {\$info.author} / {\$info.source}
- {\$info.keywords} / {\$info.description}

### 会员变量
- {\$isMemberLogin} — 是否已登录（布尔值）
- {\$member_info} — 会员信息数组

### SEO变量
- {\$seo_title} / {\$seo_keywords} / {\$seo_description}

### CSS变量（已在layout.html的:root中声明，可直接在CSS中使用）
- var(--primary) — 主色
- var(--secondary) — 辅色
- var(--accent) — 强调色
- var(--bg) — 背景色
- var(--bg-secondary) — 次背景色
- var(--text) — 文字色
- var(--text-secondary) — 次文字色
- var(--border) — 边框色
- var(--radius) — 圆角
- var(--shadow) — 阴影

## V2.9.8 B-3: CSS质量要求（强制性最低标准）
生成的CSS必须满足以下视觉质量标准，否则会被判定为低质量：

1. **CSS变量使用**（至少10次var()引用）
   - :root中声明--primary/--bg/--text等，并在多处使用var()引用
   
2. **过渡动画**（至少3处transition/animation）
   - 按钮:hover时transition: all 0.3s
   - 卡片:hover时transform: translateY(-2px) + box-shadow变化
   - 导航链接:hover时颜色渐变
   
3. **盒子阴影**（至少1处box-shadow）
   - 卡片阴影: box-shadow: 0 2px 8px rgba(0,0,0,0.1)
   - 或按钮悬停发光效果
   
4. **响应式媒体查询**（至少1组@media）
   - @media (max-width: 768px) { ...移动端适配... }
   
5. **颜色层次**（至少4种不同色值）
   - 主色 + 辅色 + 背景色 + 文字色 + 强调色，各有明度变化
   - 避免全站只有1-2种颜色
   
6. **交互伪类**（至少3处:hover/:active/:focus）
   - 按钮必须有:hover（加深/发光）和:active（缩小）
   - 导航必须有:hover + .active高亮
   - 输入框必须有:focus（边框变色）
   
7. **间距体系**（至少5处一致的padding/margin）
   - 使用统一的间距值（如8px/16px/24px/32px）
   - 避免随机杂乱的间距

### CSS禁止项（会导致质量评分降低）
- ❌ 纯色平铺背景无纹理/渐变/卡片分区
- ❌ 按钮无:hover效果
- ❌ 导航栏无当前页面高亮
- ❌ 内容区域无统一间距

## 正确模板示例

### layout.html 示例结构
```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>{\$site_name|default='AI-CMS'}</title>
    <link href="{\$skin}css/style.css" rel="stylesheet">
    <style>:root{{--primary:var(--primary);...}}</style>
</head>
<body>
    <header>导航栏（使用{\$site_name}、{\$site_logo}）</header>
    <main>{block name="content"}{/block}</main>
    <footer>© {\$year} {\$site_name}</footer>
    <script src="{\$skin}js/main.js"></script>
</body>
</html>
```

### index.html 示例结构
```
{extend name="layout" /}
{block name="content"}
<section>首页内容（不使用{volist}循环CMS不存在的变量）</section>
{/block}
```

### list.html 示例结构
```
{extend name="layout" /}
{block name="content"}
<h1>{\$category.name|default='文章列表'}</h1>
{volist name="list" id="vo"}
<article>
    <h2>{\$vo.title}</h2>
    <p>{\$vo.description}</p>
    <time>{\$vo.create_time|date='Y-m-d'}</time>
</article>
{/volist}
{/block}
```

## 输出格式要求
请按以下格式返回每个文件：

```file:路径/文件名.扩展名
文件内容
```

必须包含的文件：
- theme.json（主题元信息，含color字段指定主色调、name字段指定中文主题名）
- pc/layout.html（PC端布局模板，CSS写在&lt;style&gt;标签内）
- pc/index.html（PC端首页，使用{extend}继承layout，{block}覆盖内容区）
- pc/list.html（PC端列表页，使用{volist name="list"}循环内容列表）
- pc/detail.html（PC端详情页，使用{\$info}显示内容详情）
- mobile/layout.html（移动端布局，如需要）

请确保生成完整、可直接运行的模板代码。
PROMPT;
    }

    /**
     * V2.9.8 A-1: 构建行业风格Prompt
     */
    protected function buildIndustryPrompt(string $industryType): string
    {
        $config = config('theme_styles.industries.' . $industryType);
        if (!$config) {
            $config = config('theme_styles.industries.' . config('theme_styles.default_industry', 'corporate'));
        }
        if (!$config) {
            return '';
        }

        $patterns = $config['design_patterns'];
        $colors = $config['color_suggestions'] ?? [];
        $elements = array_keys(array_filter($config['css_key_elements'] ?? []));
        $elementsText = !empty($elements) ? implode('、', $elements) : 'container、card、button';

        // 构建色彩建议
        $colorSuggestion = '';
        if (!empty($colors)) {
            $colorSuggestion = "\n### 建议色值（可微调，保持风格一致）\n";
            foreach ($colors as $key => $value) {
                $label = match($key) {
                    'primary' => '主色',
                    'primary_light' => '浅主色',
                    'primary_dark' => '深主色',
                    'bg' => '背景色',
                    'bg_section' => '区块背景',
                    'text' => '正文色',
                    'text_muted' => '次要文字',
                    default => $key,
                };
                $colorSuggestion .= "- --{$key}: {$value} ({$label})\n";
            }
        }

        return <<<PROMPT

## 行业风格指引：{$config['name']}

### 设计模式（必须遵循）
请严格按照以下行业设计模式生成模板：

**色彩方案**：{$patterns['color_schema']}
**排版规范**：{$patterns['typography']}
**组件要求**：{$patterns['components']}
**整体氛围**：{$patterns['atmosphere']}
{$colorSuggestion}

### 关键CSS元素（必须包含）
以下CSS组件必须在生成模板中出现：{$elementsText}

### CSS变量体系（最少10个）
使用CSS变量定义所有颜色和关键尺寸：
--primary (主色), --primary-light (浅主色), --primary-dark (深主色),
--bg (背景), --bg-secondary (次背景), --text (正文色),
--text-muted (次要文字), --border (边框色), --radius (圆角),
--shadow (阴影), --shadow-hover (悬停阴影), --transition (过渡)
{$this->buildCssComponentPrompt($industryType)}

### 兜底指令
如果无法完全理解上述设计模式指引，至少确保：CSS变量不少于10个、至少3处过渡效果、至少1组媒体查询、按钮有hover效果、卡片有阴影。

PROMPT;
    }

    /**
     * V2.9.8 A-1/A-2: 构建CSS组件模式Prompt
     */
    /**
     * V2.9.9 A-1: 动态构建CSS组件Prompt（从配置读取，消除硬编码）
     */
    protected function buildCssComponentPrompt(string $industryType): string
    {
        // 从theme_styles配置读取行业设计模式
        $industries = config('theme_styles.industries', []);
        $industryConfig = $industries[$industryType] ?? $industries['corporate'] ?? null;

        // 配置优先：取 design_patterns.components 描述
        if ($industryConfig && !empty($industryConfig['design_patterns']['components'])) {
            $components = $industryConfig['design_patterns']['components'];
        } else {
            // 兜底：从CssComponentLibrary获取组件名列表并转描述
            $library = new CssComponentLibrary();
            $compNames = $library->getComponentNamesForIndustry($industryType);
            $components = implode('、', $compNames) ?: 'Hero全宽渐变区、三列卡片特色区、按钮系统、固定导航栏、响应式网格、多列页脚';
        }

        return <<<PROMPT

### 行业专属CSS组件模式
请在CSS中实现以下组件样式：{$components}

PROMPT;
    }

    /**
     * V2.9.8 C-1: 构建扩展模板变量Prompt
     */
    protected function buildExtendedVariables(): string
    {
        return <<<PROMPT

## V2.9.8 C-1: 扩展模板变量（除已有变量外，以下变量也可使用）

### 标签聚合页变量
- {\$tagName} — 标签名称，如"技术"、"生活"
- {\$tagList} — 该标签下的文章列表（HTML格式）

### 栏目页变量
- {\$categoryName} — 栏目名称，如"公司新闻"
- {\$categoryDesc} — 栏目描述文字

### 关于页增强变量
- {\$contactEmail} — 联系邮箱
- {\$contactPhone} — 联系电话

### 建议生成的额外页面
- tag.html（标签聚合页，使用{\$tagName}和{\$tagList}变量）
- category.html（栏目页，使用{\$categoryName}和{\$categoryDesc}变量）

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
