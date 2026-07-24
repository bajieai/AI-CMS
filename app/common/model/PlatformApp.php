<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 开放平台应用模型
 * V2.9.38 OPEN-PLAT-4
 */
class PlatformApp extends Model
{
    protected $name = 'platform_app';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $type = [
        'app_config' => 'json',
        'required_permissions' => 'json',
        'screenshots' => 'json',
    ];

    const TYPE_WEB = 'web';
    const TYPE_MOBILE = 'mobile';
    const TYPE_PLUGIN = 'plugin';
    const TYPE_INTEGRATION = 'integration';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_OFFLINE = 'offline';
    const STATUS_PUBLISHED = 'published';
}
