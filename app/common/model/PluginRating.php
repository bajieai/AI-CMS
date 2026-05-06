<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 插件评分模型 - V2.6
 */
class PluginRating extends Model
{
    protected $name = 'plugin_rating';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'plugin_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
    ];
}
