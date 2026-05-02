<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * SEO关键词分组模型
 */
class SeoKeywordGroup extends Model
{
    protected $name = 'seo_keyword_group';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'sort' => 'integer',
    ];
}
