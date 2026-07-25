<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.35 PERF: 缓存统计模型
 */
class CacheStats extends Model
{
    protected $name = 'cache_stats';
    protected $autoWriteTimestamp = false;
}
