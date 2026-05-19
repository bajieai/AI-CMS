<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 内容扩展模型
 */
class ContentExt extends Model
{
    protected $name = 'content_ext';

    // 不使用自动时间戳
    protected $autoWriteTimestamp = false;

    // JSON字段
    protected $json = ['data'];

    // 类型转换
    protected $type = [
        'content_id' => 'integer',
        'type' => 'integer',
    ];

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
