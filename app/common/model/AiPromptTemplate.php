<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint AI3: AI Prompt模板模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI Prompt模板模型 - V2.9.31 AI3-2
 */
class AiPromptTemplate extends Model
{
    protected $name = 'ai_prompt_template';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'is_system' => 'integer',
        'status' => 'integer',
        'variables' => 'json',
    ];

    /**
     * 查询作用域 — 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 查询作用域 — 系统内置
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', 1);
    }
}
