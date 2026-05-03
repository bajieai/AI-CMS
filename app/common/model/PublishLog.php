<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 发布记录模型 - V2.5新增
 */
class PublishLog extends Model
{
    protected $name = 'publish_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'platform_id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 关联发布平台
     */
    public function platform()
    {
        return $this->belongsTo(PublishPlatform::class, 'platform_id');
    }
}
