<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 搜索关键词统计模型 - V2.6
 */
class SearchKeyword extends Model
{
    protected $name = 'search_keyword';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'count' => 'integer',
        'last_search_time' => 'integer',
    ];
}
