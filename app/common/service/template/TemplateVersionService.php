<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板版本管理Service - V2.9.32 T4-2
 */
class TemplateVersionService
{
    private const CACHE_TAG = 'template_version';

    public function createVersion(int $templateId, string $version, string $changelog): array
    {
        $store = TemplateStore::find($templateId);
        if (!$store) return ['success' => false, 'message' => '模板不存在'];
        $oldVersion = $store->current_version ?: 'v1.0.0';
        $store->current_version = $version;
        $store->has_update = 0;
        $store->save();
        Cache::clear();
        return ['success' => true, 'message' => "版本已发布: {$oldVersion} → {$version}", 'old_version' => $oldVersion, 'new_version' => $version];
    }

    public function publishVersion(int $templateId, string $version, string $changelog = ''): array
    {
        return $this->createVersion($templateId, $version, $changelog);
    }

    public function upgrade(int $templateId, int $memberId): array
    {
        $store = TemplateStore::find($templateId);
        if (!$store) return ['success' => false, 'message' => '模板不存在'];
        $installService = new TemplateStoreService();
        try {
            $result = $installService->activateTheme($templateId, $memberId);
            return ['success' => true, 'message' => '升级成功', 'version' => $store->current_version];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '升级失败: ' . $e->getMessage()];
        }
    }

    public function rollback(int $templateId, int $memberId, string $targetVersion): array
    {
        $store = TemplateStore::find($templateId);
        if (!$store) return ['success' => false, 'message' => '模板不存在'];
        $store->current_version = $targetVersion;
        $store->save();
        Cache::clear();
        return ['success' => true, 'message' => "已回滚到 {$targetVersion}"];
    }

    public function getHistory(int $templateId): array
    {
        $store = TemplateStore::find($templateId);
        if (!$store) return [];
        return [['version' => $store->current_version ?: 'v1.0.0', 'date' => date('Y-m-d H:i', $store->update_time ?? time())]];
    }

    public function compareVersions(int $templateId, string $v1, string $v2): array
    {
        return ['template_id' => $templateId, 'version_1' => $v1, 'version_2' => $v2, 'diff' => '版本对比功能'];
    }
}
