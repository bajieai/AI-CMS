<?php
declare(strict_types=1);

namespace app\common\service\plugin;

use app\common\model\PluginVersion;
use app\common\model\Plugin;

/**
 * 插件版本管理服务
 * V2.9.37 PLUG-ECO-2
 */
class PluginVersionService
{
    /**
     * 版本列表
     */
    public function getVersionList(int $pluginId): array
    {
        return PluginVersion::where('plugin_id', $pluginId)
            ->order('create_time', 'desc')
            ->select()->toArray();
    }

    /**
     * 创建版本
     */
    public function createVersion(int $pluginId, array $data): int
    {
        $model = PluginVersion::create([
            'plugin_id'        => $pluginId,
            'version'          => $data['version'],
            'version_tag'      => $data['version_tag'] ?? 'stable',
            'version_desc'     => $data['version_desc'] ?? '',
            'changelog'        => $data['changelog'] ?? '',
            'min_cms_version'  => $data['min_cms_version'] ?? '2.9.36',
            'max_cms_version'  => $data['max_cms_version'] ?? '2.9.99',
            'min_php_version'  => $data['min_php_version'] ?? '8.0',
            'min_mysql_version' => $data['min_mysql_version'] ?? '5.7',
            'dependencies'     => $data['dependencies'] ?? null,
            'file_hash'        => $data['file_hash'] ?? hash('sha256', uniqid()),
            'file_size'        => $data['file_size'] ?? 0,
            'publish_status'   => 'draft',
        ]);
        return (int) $model->id;
    }

    /**
     * 灰度发布
     */
    public function grayscalePublish(int $versionId, float $ratio): bool
    {
        $version = PluginVersion::find($versionId);
        if (!$version) return false;
        $version->publish_status = 'grayscale';
        $version->grayscale_ratio = $ratio;
        $version->publish_time = date('Y-m-d H:i:s');
        return $version->save();
    }

    /**
     * 全量发布
     */
    public function fullPublish(int $versionId): bool
    {
        $version = PluginVersion::find($versionId);
        if (!$version) return false;
        $version->publish_status = 'released';
        $version->grayscale_ratio = 1.0;
        $version->publish_time = date('Y-m-d H:i:s');
        // 更新插件主版本号
        $plugin = Plugin::find($version['plugin_id']);
        if ($plugin) {
            $plugin->version = $version['version'];
            $plugin->save();
        }
        return $version->save();
    }

    /**
     * 回滚版本
     */
    public function rollbackVersion(int $versionId): bool
    {
        $version = PluginVersion::find($versionId);
        if (!$version) return false;
        $version->publish_status = 'rolled_back';
        return $version->save();
    }

    /**
     * 兼容性检查
     */
    public function checkCompatibility(int $versionId): array
    {
        $version = PluginVersion::find($versionId);
        if (!$version) return [];
        $issues = [];
        $currentCms = '2.9.37';
        $currentPhp = PHP_VERSION;
        if (version_compare($currentCms, $version['min_cms_version'], '<')) {
            $issues[] = "AI-CMS版本过低: 需要{$version['min_cms_version']}+，当前{$currentCms}";
        }
        if (version_compare($currentPhp, $version['min_php_version'], '<')) {
            $issues[] = "PHP版本过低: 需要{$version['min_php_version']}+，当前{$currentPhp}";
        }
        return ['compatible' => empty($issues), 'issues' => $issues];
    }

    /**
     * 版本历史
     */
    public function getVersionHistory(int $pluginId): array
    {
        return $this->getVersionList($pluginId);
    }
}
