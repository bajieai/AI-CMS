<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * A/B测试模型
 * V2.9.38 OPS-DEEP-1
 */
class AbTest extends Model
{
    protected $name = 'ab_test';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $type = [
        'version_a_config' => 'json',
        'version_b_config' => 'json',
        'target_audience' => 'json',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ARCHIVED = 'archived';

    const TYPE_CONTENT = 'content';
    const TYPE_TEMPLATE = 'template';
    const TYPE_FEATURE = 'feature';
    const TYPE_PRICE = 'price';
}
