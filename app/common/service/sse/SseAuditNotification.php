<?php
declare(strict_types=1);
namespace app\common\service\sse;

use app\common\service\sse\SseEngine;
use app\common\model\SseMessageQueue;
use app\common\model\SseClient;

/**
 * V2.9.27 T-3: SSE审核流程实时通告
 */
class SseAuditNotification
{
    public static function notifyApproved(int $userId, string $title, int $contentId): int
    {
        return SseEngine::push(SseMessageQueue::CHANNEL_AUDIT, [
            'action' => 'approved', 'title' => $title, 'content_id' => $contentId,
            'message' => '您的文章《' . $title . '》已审核通过', 'time' => date('Y-m-d H:i:s'),
        ], $userId, 'audit_approved');
    }

    public static function notifyRejected(int $userId, string $title, int $contentId, string $reason = ''): int
    {
        return SseEngine::push(SseMessageQueue::CHANNEL_AUDIT, [
            'action' => 'rejected', 'title' => $title, 'content_id' => $contentId, 'reason' => $reason,
            'message' => '您的文章《' . $title . '》审核未通过' . ($reason ? '：' . $reason : ''), 'time' => date('Y-m-d H:i:s'),
        ], $userId, 'audit_rejected');
    }

    public static function notifyPendingReview(string $title, int $contentId, string $author = ''): void
    {
        SseEngine::push(SseMessageQueue::CHANNEL_AUDIT, [
            'action' => 'pending', 'title' => $title, 'content_id' => $contentId, 'author' => $author,
            'message' => '新内容待审核：《' . $title . '》', 'time' => date('Y-m-d H:i:s'),
        ], 0, 'audit_pending');
    }

    public static function notifyCommentAudit(int $userId, string $action, string $commentPreview): int
    {
        return SseEngine::push(SseMessageQueue::CHANNEL_AUDIT, [
            'action' => 'comment_' . $action, 'preview' => $commentPreview,
            'message' => '您的评论' . ($action === 'approved' ? '已通过' : '未通过'), 'time' => date('Y-m-d H:i:s'),
        ], $userId, 'comment_audit');
    }
}
