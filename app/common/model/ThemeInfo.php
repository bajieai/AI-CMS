<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 主题信息模型 - V2.5新增
 */
class ThemeInfo extends Model
{
    protected $name = 'theme_info';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'is_installed' => 'integer',
        'update_available' => 'integer',
    ];
}
