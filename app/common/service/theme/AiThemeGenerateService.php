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

            // 4.5 自动创建皮肤目录（public/skin/themes/{theme}/pc/）
            $this->ensureSkinAssets($themeName, $baseDir);

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

        Log::info("[AiThemeGenerate] 皮肤目录初始化完成: theme={$themeName}");
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
