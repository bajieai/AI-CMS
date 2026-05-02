<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * SEO关键词模型
 */
class SeoKeyword extends Model
{
    protected $name = 'seo_keyword';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'group_id'      => 'integer',
        'search_volume' => 'integer',
        'difficulty'    => 'integer',
        'is_sensitive'  => 'integer',
        'status'        => 'integer',
    ];
}
