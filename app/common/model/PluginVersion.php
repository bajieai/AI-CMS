<?php
declare(strict_types=1);

namespace app\common\model;

use think\model;

/**
 * V2.9.25 L-1: 插件版本模型
 */
class PluginVersion extends Model
{
    protected $name = 'plugin_version';
    protected $autoWriteTimestamp = 'datetime';

    protected $type = [
        'plugin_id' => 'integer',
        'file_size' => 'integer',
        'status' => 'integer',
        'is_current' => 'integer',
    ];

    public function plugin()
    {
        return $this->belongsTo(PluginPackage::class, 'plugin_id', 'id');
    }
}