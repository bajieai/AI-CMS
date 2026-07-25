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
 * AI对话消息模型 — V2.9.39 AI-DEEP-1
 *
 * 存储每条对话消息（用户消息/AI回复/系统消息）
 * 表名: i8j_ai_dialog_message
 */
class AiDialogMessage extends Model
{
    protected $name = 'ai_dialog_message';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /** 消息角色：用户 */
    public const ROLE_USER = 'user';
    /** 消息角色：AI助手 */
    public const ROLE_ASSISTANT = 'assistant';
    /** 消息角色：系统 */
    public const ROLE_SYSTEM = 'system';

    /** 消息状态：正常 */
    public const STATUS_NORMAL = 1;
    /** 消息状态：已删除 */
    public const STATUS_DELETED = 0;

    /**
     * 关联会话
     */
    public function dialog()
    {
        return $this->belongsTo(AiDialog::class, 'dialog_id', 'id');
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
