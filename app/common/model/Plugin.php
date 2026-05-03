<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 插件注册表模型 - V2.5新增
 */
class Plugin extends Model
{
    protected $name = 'plugin';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'is_enabled' => 'integer',
    ];

    public function getHooksAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setHooksAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getConfigAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setConfigAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
