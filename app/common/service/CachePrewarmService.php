<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;
use think\facade\Db;

/**
 * V2.9.35 PERF-1: 缓存预热服务
 * 启动预热 + 定时预热 + 手动预热
 */
class CachePrewarmService
{
    /**
     * 系统启动预热（核心数据）
     */
    public function startupPrewarm(): array
    {
        $results = [];

        // 1. 系统配置
        $results['config'] = $this->prewarmConfig();

        // 2. 菜单
        $results['menu'] = $this->prewarmMenu();

        // 3. 分类
        $results['category'] = $this->prewarmCategory();

        // 4. 语言
        $results['language'] = $this->prewarmLanguage();

        return $results;
    }

    /**
     * 预热系统配置
     */
    protected function prewarmConfig(): int
    {
        $configs = Db::name('system_config')->select()->toArray();
        $count = 0;
        foreach ($configs as $config) {
            Cache::set('config_' . $config['name'], $config['value'], 3600);
            $count++;
        }
        return $count;
    }

    /**
     * 预热菜单
     */
    protected function prewarmMenu(): int
    {
        $menus = Db::name('menu_item')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        Cache::set('menu_items_all', $menus, 3600);
        return count($menus);
    }

    /**
     * 预热分类
     */
    protected function prewarmCategory(): int
    {
        $categories = Db::name('cate')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        Cache::set('cate_all', $categories, 3600);
        return count($categories);
    }

    /**
     * 预热语言
     */
    protected function prewarmLanguage(): int
    {
        $languages = Db::name('language')->where('status', 1)->select()->toArray();
        Cache::set('language_all', $languages, 3600);
        return count($languages);
    }

    /**
     * 手动预热指定模块
     */
    public function prewarmModule(string $module): array
    {
        $method = 'prewarm' . ucfirst($module);
        if (method_exists($this, $method)) {
            $count = $this->$method();
            return ['module' => $module, 'count' => $count, 'status' => 'success'];
        }

        return ['module' => $module, 'count' => 0, 'status' => 'unknown_module'];
    }

    /**
     * 标记预热状态
     */
    public function markPrewarmStatus(string $module, bool $success): void
    {
        $today = date('Y-m-d');
        Db::name('cache_stats')->replace([
            'stat_date'       => $today,
            'cache_key'       => 'prewarm_' . $module,
            'prewarm_status'  => $success ? 1 : 0,
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
