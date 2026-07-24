<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint AI3: SEO诊断记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * SEO诊断记录模型 - V2.9.31 AI3-1
 */
class SeoDiagnosis extends Model
{
    protected $name = 'seo_diagnosis';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'content_id' => 'integer',
        'score' => 'integer',
        'issues' => 'json',
        'stats' => 'json',
        'suggestions' => 'json',
    ];

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * 查询作用域 — 指定内容
     */
    public function scopeByContent($query, int $contentId)
    {
        return $query->where('content_id', $contentId);
    }

    /**
     * 查询作用域 — 评分范围
     */
    public function scopeScoreRange($query, int $min, int $max)
    {
        return $query->where('score', '>=', $min)->where('score', '<=', $max);
    }
}
