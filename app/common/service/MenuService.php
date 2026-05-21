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

use app\common\model\MenuGroup;
use app\common\model\MenuItem;
use think\facade\Cache;

/**
 * 菜单核心服务
 * 负责菜单树的组装、权限过滤、缓存管理
 */
class MenuService
{
    const CACHE_TAG = 'i8j_menu';
    const CACHE_KEY_TREE = 'menu_tree';

    /**
     * 获取完整菜单树（从数据库）
     * 结果格式与 config/menu.php 一致，供 AdminBaseController 使用
     */
    public static function getMenuTree(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember(self::CACHE_KEY_TREE, function () {
            $groups = MenuGroup::getActiveGroups();
            $items  = MenuItem::getActiveItems();

            // 按 group_id 分组菜单项
            $itemMap = [];
            foreach ($items as $item) {
                $itemMap[$item['group_id']][] = [
                    'id'         => (int) $item['id'],
                    'name'       => $item['name'],
                    'url'        => $item['url'] ?? '',
                    'permission' => $item['permission'] ?? '',
                    'active'     => $item['active'] ?? '',
                    'icon'       => $item['icon'] ?? '',
                ];
            }

            $tree = [];
            foreach ($groups as $group) {
                $tree[] = [
                    'id'       => (int) $group['id'],
                    'name'     => $group['name'],
                    'icon'     => $group['icon'] ?? '',
                    'url'      => '',
                    'children' => $itemMap[$group['id']] ?? [],
                ];
            }

            return $tree;
        }, 3600);
    }

    /**
     * 清除菜单缓存
     */
    public static function clearMenuCache(): void
    {
        Cache::tag(self::CACHE_TAG)->clear();
    }

    /**
     * 保存分组排序
     */
    public static function saveGroupSort(array $orders): bool
    {
        try {
            foreach ($orders as $index => $id) {
                MenuGroup::where('id', $id)->update(['sort' => ($index + 1) * 10]);
            }
            self::clearMenuCache();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 保存菜单项排序
     */
    public static function saveItemSort(array $orders): bool
    {
        try {
            foreach ($orders as $index => $id) {
                MenuItem::where('id', $id)->update(['sort' => ($index + 1) * 10]);
            }
            self::clearMenuCache();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 更新分组状态
     */
    public static function updateGroupStatus(int $id, int $status): bool
    {
        try {
            MenuGroup::where('id', $id)->update(['status' => $status]);
            self::clearMenuCache();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 更新菜单项状态
     */
    public static function updateItemStatus(int $id, int $status): bool
    {
        try {
            MenuItem::where('id', $id)->update(['status' => $status]);
            self::clearMenuCache();
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
