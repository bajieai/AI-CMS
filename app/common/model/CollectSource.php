<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 采集源模型 - V2.5新增
 */
class CollectSource extends Model
{
    protected $name = 'collect_source';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public function getRulesAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setRulesAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
}
