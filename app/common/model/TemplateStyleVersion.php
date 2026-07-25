<?php
declare(strict_types=1);
namespace app\common\model;

use think\Model;

/**
 * 模板样式版本历史模型 - V2.9.32 CUS2-4
 */
class TemplateStyleVersion extends Model
{
    protected $name = 'template_style_version';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'member_id' => 'integer', 'template_id' => 'integer', 'version' => 'integer',
        'config_snapshot' => 'json', 'diff' => 'json',
    ];

    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeByTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }
}
