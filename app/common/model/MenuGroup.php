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
 * 后台菜单分组模型
 */
class MenuGroup extends Model
{
    protected $name = 'menu_group';
    protected $pk = 'id';

    protected $type = [
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
    ];

    /**
     * 获取启用的分组列表
     */
    public static function getActiveGroups(): array
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->column('*', 'id');
    }
}
