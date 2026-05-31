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
 * 模板商店分类模型 - V2.9.12新增
 */
class TemplateStoreCategory extends Model
{
    protected $name = 'template_store_category';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'is_enabled' => 'integer',
    ];

    /**
     * 关联模板
     */
    public function templates()
    {
        return $this->hasMany(TemplateStore::class, 'category_id');
    }

    /**
     * 查询作用域 — 只查询启用的分类
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', 1);
    }
}
