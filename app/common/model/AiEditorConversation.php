<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI编辑器对话记录模型 — V2.9.28 A-2
 *
 * content_id注释修正（小产v2审核问题1）：
 *   0=未关联内容，仅允许临时对话，不支持跨content对话
 */
class AiEditorConversation extends Model
{
    protected $name = 'ai_editor_conversation';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    const ROLE_USER = 'user';
    const ROLE_ASSISTANT = 'assistant';

    /**
     * 生成会话ID
     */
    public static function generateSessionId(): string
    {
        return 'conv_' . date('YmdHis') . '_' . bin2hex(random_bytes(8));
    }
}
