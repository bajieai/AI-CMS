<?php
declare(strict_types=1);
namespace app\common\model;
use think\Model;
class TemplateCategoryV2 extends Model
{
    protected $name = 'template_category_v2';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['parent_id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'template_count' => 'integer'];
}
