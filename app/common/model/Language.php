<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 语言模型 - V2.5新增
 */
class Language extends Model
{
    protected $name = 'language';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'is_default' => 'integer',
        'is_enabled' => 'integer',
        'sort' => 'integer',
    ];
}
