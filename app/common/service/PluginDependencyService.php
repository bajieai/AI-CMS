<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\PluginPackage;
use app\common\model\PluginDependency;
use app\common\model\Plugin as InstalledPlugin;

/**
 * V2.9.25 L-5: 插件依赖解析服务
 */
class PluginDependencyService
{
    public function getDependencyTree(int $pluginId): array
    {
        $deps = PluginDependency::with('dependsOn')->where('plugin_id', $pluginId)->select();
        $tree = [];
        foreach ($deps as $dep) {
            $tree[] = [
                'id' => $dep->id,
                'depends_on' => [
                    'id' => $dep->depends_on_plugin_id,
                    'name' => $dep->dependsOn ? $dep->dependsOn->name : '未知',
                    'code' => $dep->dependsOn ? $dep->dependsOn->code : '',
                ],
                'min_version' => $dep->min_version,
                'max_version' => $dep->max_version,
                'is_required' => $dep->is_required,
                'is_installed' => $this->isInstalled($dep->depends_on_plugin_id),
                'is_satisfied' => $this->isVersionSatisfied($dep->depends_on_plugin_id, $dep->min_version, $dep->max_version),
            ];
        }
        return $tree;
    }

    public function checkDependencies(int $pluginId): array
    {
        $tree = $this->getDependencyTree($pluginId);
        $missing = [];
        foreach ($tree as $dep) {
            if (!$dep['is_installed']) {
                if ($dep['is_required']) $missing[] = $dep['depends_on']['name'] . ' (>= ' . $dep['min_version'] . ')';
            } elseif (!$dep['is_satisfied']) {
                $missing[] = $dep['depends_on']['name'] . ' 版本不满足';
            }
        }
        return ['success' => empty($missing), 'missing' => $missing, 'all' => $tree];
    }

    public function isInstalled(int $pluginId): bool
    {
        $plugin = PluginPackage::find($pluginId);
        if (!$plugin) return false;
        return InstalledPlugin::where('code', $plugin->code)->where('status', 1)->exists();
    }

    public function isVersionSatisfied(int $pluginId, string $min, string $max): bool
    {
        $plugin = PluginPackage::find($pluginId);
        if (!$plugin) return false;
        $installed = InstalledPlugin::where('code', $plugin->code)->where('status', 1)->find();
        if (!$installed) return false;
        $cv = $installed->version ?? '0.0.0';
        return $this->versionCompare($cv, $min) >= 0 && ($max === '*' || $this->versionCompare($cv, $max) <= 0);
    }

    public function versionCompare(string $v1, string $v2): int
    {
        $a = array_map('intval', explode('.', $v1));
        $b = array_map('intval', explode('.', $v2));
        for ($i = 0; $i < max(count($a), count($b)); $i++) {
            $va = $a[$i] ?? 0; $vb = $b[$i] ?? 0;
            if ($va !== $vb) return $va <=> $vb;
        }
        return 0;
    }
}