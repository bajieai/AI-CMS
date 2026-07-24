<?php
declare(strict_types=1);

namespace app\common\model;

use think\model;

/**
 * V2.9.25 L-1: 插件依赖模型
 */
class PluginDependency extends Model
{
    protected $name = 'plugin_dependency';
    protected $autoWriteTimestamp = 'datetime';

    protected $type = [
        'plugin_id' => 'integer',
        'depends_on_plugin_id' => 'integer',
        'is_required' => 'integer',
    ];

    public function plugin()
    {
        return $this->belongsTo(PluginPackage::class, 'plugin_id', 'id');
    }

    public function dependsOn()
    {
        return $this->belongsTo(PluginPackage::class, 'depends_on_plugin_id', 'id');
    }
}