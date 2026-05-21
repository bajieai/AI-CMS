<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
