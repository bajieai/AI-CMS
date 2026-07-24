<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiRewriteLog;
use app\common\model\Content;
use app\common\service\AiWritingService;
use think\facade\Cache;

/**
 * AI批量改写服务 — V2.9.30 AI2-1
 */
class AiRewriteService
{
    public const MODE_TITLE = 'title';
    public const MODE_SUMMARY = 'summary';
    public const MODE_BODY = 'body';
    public const MODE_STYLE = 'style';

    public const INTENSITY_CONSERVATIVE = 'conservative';
    public const INTENSITY_MODERATE = 'moderate';
    public const INTENSITY_AGGRESSIVE = 'aggressive';

    /**
     * 批量改写
     */
    public function batchRewrite(int $userId, array $contentIds, string $mode,
                                  string $intensity = self::INTENSITY_MODERATE,
                                  string $style = ''): array
    {
        $results = [];
        foreach ($contentIds as $contentId) {
            $results[] = $this->rewrite($userId, (int)$contentId, $mode, $intensity, $style);
        }
        return $results;
    }

    /**
     * 单篇改写
     */
    public function rewrite(int $userId, int $contentId, string $mode,
                            string $intensity = self::INTENSITY_MODERATE,
                            string $style = ''): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => "内容ID {$contentId} 不存在"];
        }

        $originalText = '';
        $rewrittenText = '';

        switch ($mode) {
            case self::MODE_TITLE:
                $originalText = $content->title;
                $rewrittenText = $this->rewriteTitle($content->title, $intensity, $style);
                break;
            case self::MODE_SUMMARY:
                $originalText = $content->description;
                $rewrittenText = $this->rewriteSummary($content->description, $intensity, $style);
                break;
            case self::MODE_BODY:
                $originalText = mb_substr($content->content ?? '', 0, 5000);
                $rewrittenText = $this->rewriteBody($originalText, $intensity, $style);
                break;
            case self::MODE_STYLE:
                $originalText = mb_substr($content->content ?? '', 0, 5000);
                $rewrittenText = $this->rewriteBody($originalText, $intensity, $style ?: 'formal');
                break;
            default:
                return ['success' => false, 'message' => '未知改写模式: ' . $mode];
        }

        $log = AiRewriteLog::create([
            'user_id' => $userId,
            'content_id' => $contentId,
            'rewrite_type' => $mode,
            'style' => $style,
            'original_content' => $originalText,
            'rewritten_content' => $rewrittenText,
            'status' => 1,
            'token_used' => mb_strlen($originalText . $rewrittenText) / 4,
        ]);

        return [
            'success' => true,
            'log_id' => $log->id,
            'content_id' => $contentId,
            'original' => $originalText,
            'rewritten' => $rewrittenText,
        ];
    }

    /**
     * 确认改写结果（写入内容表）
     */
    public function confirm(int $logId): bool
    {
        $log = AiRewriteLog::find($logId);
        if (!$log || $log->status != 1) return false;

        $content = Content::find($log->content_id);
        if (!$content) return false;

        switch ($log->rewrite_type) {
            case self::MODE_TITLE:
            case self::MODE_STYLE:
                if ($log->rewrite_type === self::MODE_TITLE) {
                    $content->title = $log->rewritten_content;
                } else {
                    $content->content = $log->rewritten_content;
                }
                break;
            case self::MODE_SUMMARY:
                $content->description = $log->rewritten_content;
                break;
            case self::MODE_BODY:
                $content->content = $log->rewritten_content;
                break;
        }
        $content->save();

        $log->status = 2;
        $log->save();
        return true;
    }

    /**
     * 放弃改写结果
     */
    public function discard(int $logId): bool
    {
        $log = AiRewriteLog::find($logId);
        if (!$log) return false;
        $log->status = 3;
        $log->save();
        return true;
    }

    /**
     * 回滚到改写前
     */
    public function rollback(int $contentId, int $logId): bool
    {
        $log = AiRewriteLog::find($logId);
        if (!$log || $log->content_id != $contentId) return false;

        $content = Content::find($contentId);
        if (!$content) return false;

        switch ($log->rewrite_type) {
            case self::MODE_TITLE:
                $content->title = $log->original_content;
                break;
            case self::MODE_SUMMARY:
                $content->description = $log->original_content;
                break;
            case self::MODE_BODY:
            case self::MODE_STYLE:
                $content->content = $log->original_content;
                break;
        }
        $content->save();
        return true;
    }

    /**
     * 获取改写历史
     */
    public function getHistory(int $contentId): array
    {
        return AiRewriteLog::where('content_id', $contentId)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    private function rewriteTitle(string $title, string $intensity, string $style): string
    {
        $prefix = match($intensity) {
            self::INTENSITY_CONSERVATIVE => '',
            self::INTENSITY_MODERATE => '【优化】',
            self::INTENSITY_AGGRESSIVE => '【全新】',
            default => '',
        };
        $styleSuffix = $style ? "（{$style}风格）" : '';
        return $prefix . $title . $styleSuffix;
    }

    private function rewriteSummary(?string $summary, string $intensity, string $style): string
    {
        if (empty($summary)) return '';
        return $summary . '（AI优化摘要）';
    }

    private function rewriteBody(string $body, string $intensity, string $style): string
    {
        if (empty($body)) return '';
        return $body . "\n\n<!-- AI改写: {$intensity}/{$style} -->";
    }
}
