<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 表单提交数据模型
 */
class FormData extends Model
{
    protected $name = 'form_data';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'form_id' => 'integer',
        'is_read' => 'integer',
    ];

    /**
     * 获取提交数据
     */
    public function getFieldsDataAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置提交数据
     */
    public function setFieldsDataAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
