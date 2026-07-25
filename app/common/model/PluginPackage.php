<?php
declare(strict_types=1);

namespace app\common\model;

use think\model;

/**
 * V2.9.25 L-1: 插件包模型
 */
class PluginPackage extends Model
{
    protected $name = 'plugin_package';
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    protected $type = [
        'price' => 'float',
        'is_free' => 'integer',
        'status' => 'integer',
        'download_count' => 'integer',
        'install_count' => 'integer',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'sort' => 'integer',
        'is_recommended' => 'integer',
        'is_hot' => 'integer',
        'file_size' => 'integer',
        'screenshots' => 'json',
        'requirements' => 'json',
    ];

    protected $defaultSoftDelete = null;

    public function category()
    {
        return $this->belongsTo(PluginCategory::class, 'category_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(PluginVersion::class, 'plugin_id', 'id');
    }

    public function dependencies()
    {
        return $this->hasMany(PluginDependency::class, 'plugin_id', 'id');
    }
}