<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;

class PluginVersion extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $json = ['dependencies'];
    protected $jsonAssoc = true;

    protected $type = [
        'plugin_id'        => 'integer',
        'file_size'        => 'integer',
        'download_count'   => 'integer',
        'grayscale_ratio'  => 'float',
    ];

    public function scopePlugin($query, int $pluginId) { return $query->where('plugin_id', $pluginId); }
    public function scopeReleased($query) { return $query->where('publish_status', 'released'); }
}
