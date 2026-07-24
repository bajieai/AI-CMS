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
 * 模板安装记录模型 - V2.9.12新增
 */
class TemplateInstall extends Model
{
    protected $name = 'template_install';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'store_id' => 'integer',
        'member_id' => 'integer',
        'is_active' => 'integer',
    ];

    /**
     * 关联商店模板
     */
    public function store()
    {
        return $this->belongsTo(TemplateStore::class, 'store_id');
    }

    /**
     * 查询作用域 — 指定用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 — 当前激活的模板
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
