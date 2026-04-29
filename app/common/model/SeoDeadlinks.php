<?php
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