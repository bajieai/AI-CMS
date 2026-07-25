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

namespace app\admin\model;

use think\Model;

/**
 * AI对话会话模型 — V2.9.39 AI-DEEP-1
 *
 * 持久化AI对话会话，支持多会话管理、标题自动生成、历史搜索/导出
 * 表名: i8j_ai_dialog
 */
class AiDialog extends Model
{
    protected $name = 'ai_dialog';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /** 会话状态：活跃 */
    public const STATUS_ACTIVE = 1;
    /** 会话状态：已归档 */
    public const STATUS_ARCHIVED = 2;
    /** 会话状态：已删除 */
    public const STATUS_DELETED = 0;

    /**
     * 生成唯一会话ID
     * @return string
     */
    public static function generateSessionId(): string
    {
        return 'dlg_' . date('YmdHis') . '_' . bin2hex(random_bytes(8));
    }

    /**
     * 关联消息列表
     */
    public function messages()
    {
        return $this->hasMany(AiDialogMessage::class, 'dialog_id', 'id')->order('id', 'asc');
    }

    /**
     * 标题访问器（空值兜底）
     */
    public function getTitleAttr($value): string
    {
        return $value ?: '新对话';
    }

    /**
     * JSON字段自动转换：context_summary
     */
    public function getContextSummaryAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * JSON字段自动转换：context_summary 写入
     */
    public function setContextSummaryAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
    }

    /**
     * JSON字段自动转换：metadata
     */
    public function getMetadataAttr($value): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * JSON字段自动转换：metadata 写入
     */
    public function setMetadataAttr($value): string
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
    }
}
