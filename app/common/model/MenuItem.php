<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 后台菜单项模型
 */
class MenuItem extends Model
{
    protected $name = 'menu_item';
    protected $pk = 'id';

    protected $type = [
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * 获取启用的菜单项列表（按分组）
     */
    public static function getActiveItems(): array
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 按分组ID获取菜单项
     */
    public static function getItemsByGroup(int $groupId): array
    {
        return self::where('group_id', $groupId)
            ->where('status', 1)
            ->where('parent_id', 0)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }
}
