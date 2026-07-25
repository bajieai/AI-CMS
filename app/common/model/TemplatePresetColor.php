<?php
/**
 * V2.9.23 C-4: 预设配色方案模型
 * V2.9.24 I-2: 新增 member_id 字段支持用户自定义配色保存
 */

namespace app\common\model;

use think\Model;

class TemplatePresetColor extends Model
{
    protected $name = 'template_preset_color';
    protected $pk = 'id';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $schema = [
        'id' => 'int',
        'name' => 'string',
        'description' => 'string',
        'colors' => 'json',
        'industry_tags' => 'string',
        'is_system' => 'int',
        'member_id' => 'int',
        'sort' => 'int',
        'create_time' => 'int',
    ];

    protected $json = ['colors'];
    protected $jsonAssoc = true;

    protected $type = [
        'is_system' => 'integer',
        'member_id' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 查询作用域 — 系统预设
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', 1);
    }

    /**
     * 查询作用域 — 用户自定义
     */
    public function scopeCustom($query, int $memberId)
    {
        return $query->where('is_system', 0)->where('member_id', $memberId);
    }
}
