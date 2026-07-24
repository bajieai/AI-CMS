<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class ContentArchive extends Model
{
    protected $name = 'content_archive';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $type = ['id' => 'integer', 'content_id' => 'integer', 'archived_by' => 'integer'];
}
