<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 表单定义模型
 */
class Form extends Model
{
    protected $name = 'form';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'is_enabled'   => 'integer',
        'sort'         => 'integer',
        'anti_spam'    => 'integer',
        'fields_config'=> 'json',
    ];

    /**
     * 获取字段配置（JSON转数组）
     */
    public function getFieldsAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置字段配置（数组转JSON）
     */
    public function setFieldsAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
