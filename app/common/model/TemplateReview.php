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
 * 模板评分评论模型 - V2.9.12新增
 */
class TemplateReview extends Model
{
    protected $name = 'template_review';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'store_id' => 'integer',
        'member_id' => 'integer',
        'rating' => 'integer',
        'is_audited' => 'integer',
    ];

    // 审核状态常量
    const AUDIT_PENDING = 0;
    const AUDIT_PASS = 1;
    const AUDIT_REJECT = 2;

    /**
     * 获取审核状态文本
     */
    public function getAuditTextAttr($value, $data): string
    {
        $map = [
            self::AUDIT_PENDING => '待审核',
            self::AUDIT_PASS => '已通过',
            self::AUDIT_REJECT => '已拒绝',
        ];
        return $map[$data['is_audited']] ?? '未知';
    }

    /**
     * 关联商店模板
     */
    public function store()
    {
        return $this->belongsTo(TemplateStore::class, 'store_id');
    }

    /**
     * 查询作用域 — 已通过审核
     */
    public function scopeAudited($query)
    {
        return $query->where('is_audited', self::AUDIT_PASS);
    }
}
