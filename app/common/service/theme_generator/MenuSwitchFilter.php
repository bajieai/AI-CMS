<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme_generator;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;

/**
 * AI-THEME 适配器菜单开关过滤器
 *
 * 关：返回库里 url 含 theme_generator 的菜单 id，交给菜单渲染链路剔除；
 * 开：返回空数组，不动任何菜单。
 *
 * 缓存只存 id 列表，不存开关状态——开关状态每次实时读配置，避免切换后滞后。
 */
class MenuSwitchFilter
{
    private const CACHE_KEY = 'theme_generator_menu_ids';

    /**
     * 当前应隐藏的菜单 id（开关关闭时）
     *
     * @return int[]
     */
    public static function hiddenMenuIds(): array
    {
        if ((bool) Config::get('theme_generator.enabled', false)) {
            return [];
        }

        return self::menuIds();
    }

    /**
     * 库中所有 AI-THEME 适配器入口的菜单 id（与开关无关，带缓存）
     *
     * @return int[]
     */
    public static function menuIds(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $ids = Db::name('menu_item')
                ->whereLike('url', '%theme_generator%')
                ->column('id');
        } catch (\Throwable) {
            // 菜单表尚不存在时降级：不影响后台导航
            return [];
        }

        $ids = array_values(array_map('intval', $ids));
        Cache::set(self::CACHE_KEY, $ids, 3600);

        return $ids;
    }

    /**
     * 菜单行变更后清缓存
     */
    public static function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY);
    }
}
