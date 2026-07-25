<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class TemplateAuditReport extends Model
{
    protected $name = 'template_audit_report';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['template_id' => 'integer', 'total_score' => 'float', 'status' => 'integer'];
}
