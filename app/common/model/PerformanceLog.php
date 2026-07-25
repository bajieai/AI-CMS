<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.35 PERF: 性能日志模型
 */
class PerformanceLog extends Model
{
    protected $name = 'performance_log';
    protected $autoWriteTimestamp = false;
}
