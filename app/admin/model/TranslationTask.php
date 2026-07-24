<?php
declare(strict_types=1);

namespace app\admin\model;

use think\Model;

/**
 * 翻译任务模型
 * V2.9.39 I18N-V2-1
 * 对应表: i8j_translation_task
 */
class TranslationTask extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    /** 任务类型 */
    public const TYPE_CONTENT   = 'content';
    public const TYPE_TEMPLATE  = 'template';
    public const TYPE_PLUGIN    = 'plugin';
    public const TYPE_SYSTEM    = 'system';

    /** 状态 */
    public const STATUS_PENDING     = 'pending';      // 待翻译
    public const STATUS_TRANSLATING = 'translating';  // 翻译中
    public const STATUS_REVIEWING   = 'reviewing';    // 审核中
    public const STATUS_COMPLETED   = 'completed';    // 已完成
    public const STATUS_REJECTED    = 'rejected';     // 已驳回

    /** 优先级 */
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW    = 'low';

    /** 状态标签映射 */
    public const STATUS_LABELS = [
        self::STATUS_PENDING     => '待翻译',
        self::STATUS_TRANSLATING => '翻译中',
        self::STATUS_REVIEWING   => '审核中',
        self::STATUS_COMPLETED   => '已完成',
        self::STATUS_REJECTED    => '已驳回',
    ];

    /** 状态颜色映射 */
    public const STATUS_COLORS = [
        self::STATUS_PENDING     => 'default',
        self::STATUS_TRANSLATING => 'primary',
        self::STATUS_REVIEWING   => 'warning',
        self::STATUS_COMPLETED   => 'success',
        self::STATUS_REJECTED    => 'danger',
    ];

    protected $type = [
        'id'                  => 'integer',
        'source_content_id'   => 'integer',
        'translator_id'       => 'integer',
        'reviewer_id'         => 'integer',
        'translation_quality' => 'float',
    ];

    /**
     * 关联源内容
     */
    public function content()
    {
        return $this->belongsTo(\app\common\model\Content::class, 'source_content_id', 'id');
    }

    /**
     * 翻译人员关联
     */
    public function translator()
    {
        return $this->belongsTo(\app\common\model\Admin::class, 'translator_id', 'id');
    }

    /**
     * 审核人员关联
     */
    public function reviewer()
    {
        return $this->belongsTo(\app\common\model\Admin::class, 'reviewer_id', 'id');
    }

    /**
     * 获取状态标签
     */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '未知';
    }

    /**
     * 获取状态颜色
     */
    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'default';
    }
}
