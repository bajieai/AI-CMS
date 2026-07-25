<?php
declare(strict_types=1);

namespace app\common\model;

use think\model;

/**
 * V2.9.25 L-1: 插件下载日志模型
 */
class PluginDownloadLog extends Model
{
    protected $name = 'plugin_download_log';
    protected $autoWriteTimestamp = 'datetime';

    protected $type = [
        'plugin_id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];

    public function plugin()
    {
        return $this->belongsTo(PluginPackage::class, 'plugin_id', 'id');
    }
}