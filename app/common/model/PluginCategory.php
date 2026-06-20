<?php
declare(strict_types=1);

namespace app\common\model;

use think\model;

/**
 * V2.9.25 L-1: 插件分类模型
 */
class PluginCategory extends Model
{
    protected $name = 'plugin_category';
    protected $autoWriteTimestamp = 'datetime';

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

    public function plugins()
    {
        return $this->hasMany(PluginPackage::class, 'category_id', 'id');
    }
}