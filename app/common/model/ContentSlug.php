<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class ContentSlug extends Model
{
    protected $name = 'content_slug';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['id' => 'integer', 'content_id' => 'integer', 'lang_site_id' => 'integer', 'is_active' => 'integer'];
}
