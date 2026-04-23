<?php
declare(strict_types=1);

namespace app\common\model;

use think\model\Pivot;

/**
 * 内容-标签关联模型
 */
class ContentTag extends Pivot
{
    protected $name = 'content_tag';

    // 不使用自动时间戳
    protected $autoWriteTimestamp = false;

    // 类型转换
    protected $type = [
        'content_id' => 'integer',
        'tag_id' => 'integer',
    ];
}
