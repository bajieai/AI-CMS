<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint SEC: 安全审计记录模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 安全审计记录模型 - V2.9.31 SEC-1
 */
class SecurityAudit extends Model
{
    protected $name = 'security_audit';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'line' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_PENDING = 0;
    const STATUS_FIXED = 1;
    const STATUS_IGNORED = 2;

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '未处理',
            self::STATUS_FIXED => '已修复',
            self::STATUS_IGNORED => '已忽略',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 查询作用域 — 指定模板
     */
    public function scopeByTheme($query, string $themeSlug)
    {
        return $query->where('theme_slug', $themeSlug);
    }

    /**
     * 查询作用域 — 严重程度
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}
