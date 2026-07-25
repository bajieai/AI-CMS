<?php
declare(strict_types=1);
namespace app\common\service\template;

use think\facade\Db;
use think\facade\Cache;

/**
 * 模板样式导出/导入Service - V2.9.32 CUS2-3
 */
class TemplateStyleExportService
{
    public function export(int $memberId, int $templateId): array
    {
        $customizeService = new TemplateCustomizeService();
        $styleConfig = $customizeService->getStyleConfig($memberId, "template_{$templateId}");
        $layoutConfig = $customizeService->getLayoutConfig($memberId, "template_{$templateId}");

        $cssJsService = new TemplateCustomCssJsService();
        $customCssJs = $cssJsService->loadCustom($memberId, "template_{$templateId}");

        return [
            'success' => true,
            'data' => [
                'template_id' => $templateId,
                'export_time' => date('Y-m-d H:i:s'),
                'style' => $styleConfig,
                'layout' => $layoutConfig,
                'custom_css' => $customCssJs['css'],
                'custom_js' => $customCssJs['js'],
            ],
            'filename' => "template_{$templateId}_style_" . date('Ymd') . ".json",
        ];
    }

    public function import(int $memberId, int $templateId, array $data): array
    {
        $validated = $this->validateImport($data);
        if (!$validated['valid']) return ['success' => false, 'message' => $validated['message']];

        // 导入前自动备份
        $backup = $this->export($memberId, $templateId);

        $customizeService = new TemplateCustomizeService();
        if (!empty($data['style'])) $customizeService->saveStyleConfig($memberId, "template_{$templateId}", $data['style']);
        if (!empty($data['layout'])) $customizeService->saveLayoutConfig($memberId, "template_{$templateId}", $data['layout']['sections'] ?? []);

        $cssJsService = new TemplateCustomCssJsService();
        if (!empty($data['custom_css'])) $cssJsService->saveCss($memberId, "template_{$templateId}", $data['custom_css']);
        if (!empty($data['custom_js'])) $cssJsService->saveJs($memberId, "template_{$templateId}", $data['custom_js']);

        return ['success' => true, 'message' => '样式导入成功', 'backup' => $backup];
    }

    public function validateImport(array $data): array
    {
        if (empty($data)) return ['valid' => false, 'message' => '数据为空'];
        $requiredKeys = ['style', 'layout', 'custom_css', 'custom_js'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) return ['valid' => false, 'message' => "缺少字段: {$key}"];
        }
        return ['valid' => true, 'message' => '验证通过'];
    }

    public function generateShareLink(array $styleData): array
    {
        $shareId = uniqid('style_');
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        try {
            Db::table($prefix . 'template_custom_config')->insert([
                'member_id' => 0, 'theme_slug' => 'style_share',
                'config_key' => $shareId, 'config_value' => json_encode($styleData, JSON_UNESCAPED_UNICODE),
                'config_type' => 'style_share', 'create_time' => time(), 'update_time' => time() + 7 * 86400,
            ]);
            return ['success' => true, 'share_id' => $shareId, 'expires' => date('Y-m-d H:i', time() + 7 * 86400)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '分享链接生成失败'];
        }
    }

    public function applyShareLink(string $shareId): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $data = Db::table($prefix . 'template_custom_config')->where('config_key', $shareId)->where('config_type', 'style_share')->find();
        if (!$data) return ['success' => false, 'message' => '分享链接无效或已过期'];
        if (!empty($data['update_time']) && $data['update_time'] < time()) return ['success' => false, 'message' => '分享链接已过期'];
        return ['success' => true, 'data' => json_decode($data['config_value'], true)];
    }
}
