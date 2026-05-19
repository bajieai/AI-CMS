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
 * SEO死链检测模型
 */
class SeoDeadlinks extends Model
{
    protected $name = 'seo_deadlinks';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = false;
    protected $updateTime = false;

    protected $type = [
        'status_code' => 'integer',
        'check_time' => 'integer',
        'is_fixed' => 'integer',
    ];

    public function getIsFixedTextAttr($value, $data): string
    {
        return $data['is_fixed'] ? '已修复' : '待修复';
    }
}