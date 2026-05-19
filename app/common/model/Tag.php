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
 * 标签模型
 */
class Tag extends Model
{
    protected $name = 'tag';

    // 自动时间戳（仅create_time）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'sort' => 'integer',
    ];

    /**
     * 关联内容（多对多）
     */
    public function contents()
    {
        return $this->belongsToMany(Content::class, ContentTag::class, 'content_id', 'tag_id');
    }

    /**
     * 获取内容数量
     */
    public function getContentCountAttr($value, $data): int
    {
        return ContentTag::where('tag_id', $data['id'])->count();
    }
}
