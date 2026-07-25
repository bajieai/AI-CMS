<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class TemplateUserAction extends Model
{
    protected $name = 'template_user_action';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['user_id' => 'integer', 'template_id' => 'integer'];
}
