<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Log;

/**
 * 菜单兼容层（MenuBridge）
 * DB优先 → config/menu.php回退
 * 保证后台菜单在任何情况下都能正常渲染
 */
class MenuBridge
{
    /**
     * 获取菜单数据
     * 优先从数据库读取，失败或数据不完整时回退到配置文件
     */
    public static function getMenus(): array
    {
        try {
            $tree = MenuService::getMenuTree();
            if (!empty($tree) && self::isTreeComplete($tree)) {
                return $tree;
            }
            if (!empty($tree)) {
                Log::warning('MenuBridge: 数据库菜单数据不完整（存在无子菜单的分组），回退到配置文件');
            }
        } catch (\Exception $e) {
            Log::warning('MenuBridge: 从数据库读取菜单失败，回退到配置文件。错误: ' . $e->getMessage());
        }

        return Config::get('menu', []);
    }

    /**
     * 检查菜单树是否完整
     * 若任意一级分组缺少 children 则认为不完整
     */
    private static function isTreeComplete(array $tree): bool
    {
        foreach ($tree as $group) {
            if (empty($group['children'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 强制从配置文件读取（用于数据库初始化前）
     */
    public static function getMenusFromConfig(): array
    {
        return Config::get('menu', []);
    }

    /**
     * 强制刷新数据库菜单缓存
     */
    public static function refresh(): void
    {
        MenuService::clearMenuCache();
    }
}
