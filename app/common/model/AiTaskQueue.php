<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI任务队列模型 - V2.9.14
 *
 * 用于配图生成、批量SEO等异步AI操作的统一任务调度
 */
class AiTaskQueue extends Model
{
    protected $name = 'ai_task_queue';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 任务状态常量
    const STATUS_PENDING   = 0;
    const STATUS_RUNNING   = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED    = 3;
    const STATUS_PAUSED    = 4;
    const STATUS_CANCELLED = 5;

    /** 状态名映射 */
    const STATUS_NAMES = [
        self::STATUS_PENDING   => 'pending',
        self::STATUS_RUNNING   => 'running',
        self::STATUS_COMPLETED => 'completed',
        self::STATUS_FAILED    => 'failed',
        self::STATUS_PAUSED    => 'paused',
        self::STATUS_CANCELLED => 'cancelled',
    ];

    /**
     * 获取状态名称
     */
    public static function getStatusName(int $status): string
    {
        return self::STATUS_NAMES[$status] ?? 'unknown';
    }
}
