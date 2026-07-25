<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class ContentAuditLog extends Model
{
    protected $name = 'content_audit_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['content_id' => 'integer', 'user_id' => 'integer'];
}
