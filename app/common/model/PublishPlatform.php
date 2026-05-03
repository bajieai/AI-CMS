<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 发布平台配置模型 - V2.5新增
 */
class PublishPlatform extends Model
{
    protected $name = 'publish_platform';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public function getConfigJsonAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setConfigJsonAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
