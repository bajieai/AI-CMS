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

use think\Model;

/**
 * 模板商店模型 - V2.9.12新增
 */
class TemplateStore extends Model
{
    protected $name = 'template_store';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'category_id' => 'integer',
        'price' => 'float',
        'author_id' => 'integer',
        'status' => 'integer',
        'is_featured' => 'integer',
        'quality_score' => 'integer',
        'install_count' => 'integer',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'file_size' => 'integer',
    ];

    // 状态常量
    const STATUS_PENDING = 0;
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;
    const STATUS_REJECTED = 3;

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_ONLINE => '已上架',
            self::STATUS_OFFLINE => '已下架',
            self::STATUS_REJECTED => '已拒绝',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(TemplateStoreCategory::class, 'category_id');
    }

    /**
     * 关联评论
     */
    public function reviews()
    {
        return $this->hasMany(TemplateReview::class, 'store_id');
    }

    /**
     * 查询作用域 — 已上架
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * 查询作用域 — 推荐
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1);
    }
}
