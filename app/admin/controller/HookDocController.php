<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\hook\HookEvents;

/**
 * Hook事件自动文档控制器 — V2.9.28 H-7
 * 自动从HookEvents::getMeta()生成开发者文档
 */
class HookDocController extends AdminBaseController
{
    public function __construct() { parent::__construct(app()); }

    /**
     * 文档首页
     */
    public function index()
    {
        $modules = HookEvents::getByModule();
        $meta = HookEvents::getMeta();
        $totalCount = HookEvents::count();

        // 按模块组装文档数据
        $doc = [];
        foreach ($modules as $moduleName => $events) {
            $moduleData = [
                'name' => $moduleName,
                'event_count' => count($events),
                'events' => [],
            ];
            foreach ($events as $event) {
                $eventMeta = $meta[$event] ?? [];
                $moduleData['events'][] = [
                    'event' => $event,
                    'description' => $eventMeta['description'] ?? '',
                    'since' => $eventMeta['since'] ?? 'unknown',
                    'supports_block' => $eventMeta['supports_block'] ?? false,
                    'parameters' => $eventMeta['parameters'] ?? [],
                ];
            }
            $doc[] = $moduleData;
        }

        $this->assign([
            'doc' => $doc,
            'totalCount' => $totalCount,
            'menuActive' => 'hook_doc',
        ]);

        return $this->view('/hook_debug/doc');
    }

    /**
     * 导出文档为Markdown
     */
    public function exportMarkdown()
    {
        $modules = HookEvents::getByModule();
        $meta = HookEvents::getMeta();

        $md = "# AI-CMS Hook 事件文档\n\n";
        $md .= "> 自动生成时间: " . date('Y-m-d H:i:s') . "\n";
        $md .= "> 事件总数: " . HookEvents::count() . "\n\n";
        $md .= "---\n\n";

        foreach ($modules as $moduleName => $events) {
            $md .= "## 模块: {$moduleName}\n\n";
            foreach ($events as $event) {
                $eventMeta = $meta[$event] ?? [];
                $md .= "### `{$event}`\n\n";
                $md .= "- **描述**: " . ($eventMeta['description'] ?? '-') . "\n";
                $md .= "- **引入版本**: " . ($eventMeta['since'] ?? '-') . "\n";
                $md .= "- **支持阻止**: " . (isset($eventMeta['supports_block']) && $eventMeta['supports_block'] ? '是' : '否') . "\n";

                if (!empty($eventMeta['parameters'])) {
                    $md .= "- **参数**:\n\n";
                    $md .= "| 参数名 | 类型 | 必填 | 说明 |\n";
                    $md .= "|--------|------|------|------|\n";
                    foreach ($eventMeta['parameters'] as $paramName => $paramInfo) {
                        $md .= "| `{$paramName}` | {$paramInfo['type']} | " . ($paramInfo['required'] ? '是' : '否') . " | {$paramInfo['description']} |\n";
                    }
                }

                $md .= "\n**使用示例**:\n```php\n";
                $md .= "Hook::on('{$event}', function(\$context) {\n";
                $md .= "    // 处理逻辑\n";
                $md .= "});\n```\n\n";
            }
        }

        return download($md, 'hook_events_doc.md');
    }
}
