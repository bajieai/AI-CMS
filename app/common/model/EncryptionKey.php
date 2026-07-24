<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.35 SEC: 加密密钥管理模型
 */
class EncryptionKey extends Model
{
    protected $name = 'encryption_key';
    protected $autoWriteTimestamp = false;

    // 密钥状态常量
    public const STATUS_ACTIVE = 1;       // 当前使用
    public const STATUS_ROTATED = 2;      // 已轮换(仅解密)
    public const STATUS_DEPRECATED = 3;   // 已废弃
}
