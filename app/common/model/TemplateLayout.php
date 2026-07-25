<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint CUS: 模板布局自定义模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板布局自定义模型 - V2.9.31 CUS-1
 */
class TemplateLayout extends Model
{
    protected $name = 'template_layout';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'member_id' => 'integer',
        'sections' => 'json',
    ];

    /**
     * 查询作用域 — 指定用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 — 指定模板
     */
    public function scopeByTheme($query, string $themeSlug)
    {
        return $query->where('theme_slug', $themeSlug);
    }
}
