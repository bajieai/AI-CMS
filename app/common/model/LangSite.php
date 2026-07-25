<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class LangSite extends Model
{
    protected $name = 'lang_site';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $type = ['id' => 'integer', 'is_default' => 'integer', 'status' => 'integer', 'template_id' => 'integer'];
}
