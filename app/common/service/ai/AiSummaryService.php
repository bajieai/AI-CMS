<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.32 Sprint FIX-4: AI摘要独立Service
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;
use think\facade\Cache;

/**
 * AI摘要独立Service - V2.9.32 FIX-4
 * 从ContentController中提取的摘要生成逻辑
 * 支持3种长度：短摘要(≤50字) / 中摘要(100-150字) / 长摘要(200-300字)
 */
class AiSummaryService
{
    private const string CACHE_TAG = 'ai_summary';

    public const TYPE_SHORT = 'short';
    public const TYPE_MEDIUM = 'medium';
    public const TYPE_LONG = 'long';

    /**
     * 生成摘要
     */
    public function generate(int $contentId, string $type = self::TYPE_MEDIUM): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        $text = $this->extractText($content);
        if (empty($text)) {
            return ['success' => false, 'message' => '内容为空，无法生成摘要'];
        }

        $summary = $this->generateByType($text, $type);

        return [
            'success' => true,
            'summary' => $summary,
            'type' => $type,
            'length' => mb_strlen($summary),
            'message' => '摘要生成成功',
        ];
    }

    /**
     * 批量生成摘要
     */
    public function batchGenerate(array $contentIds, string $type = self::TYPE_MEDIUM): array
    {
        $success = 0;
        $failed = 0;
        $details = [];

        foreach ($contentIds as $id) {
            $result = $this->generate((int) $id, $type);
            if ($result['success']) {
                $success++;
                $details[] = [
                    'content_id' => $id,
                    'status' => 'success',
                    'summary' => $result['summary'],
                    'length' => $result['length'],
                ];
            } else {
                $failed++;
                $details[] = [
                    'content_id' => $id,
                    'status' => 'failed',
                    'message' => $result['message'],
                ];
            }
        }

        return [
            'success' => true,
            'total' => count($contentIds),
            'success_count' => $success,
            'failed_count' => $failed,
            'details' => $details,
        ];
    }

    /**
     * 摘要预览（不保存）
     */
    public function preview(int $contentId, string $type = self::TYPE_MEDIUM): array
    {
        return $this->generate($contentId, $type);
    }

    /**
     * 摘要SEO集成（同步到seo_description字段）
     */
    public function syncToSeo(int $contentId, string $type = self::TYPE_MEDIUM): array
    {
        $result = $this->generate($contentId, $type);
        if (!$result['success']) {
            return $result;
        }

        $content = Content::find($contentId);
        if ($content) {
            // SEO描述最佳长度150-160字符
            $seoDesc = $result['summary'];
            if ($type === self::TYPE_LONG && mb_strlen($seoDesc) > 160) {
                $seoDesc = mb_substr($seoDesc, 0, 160);
            }
            $content->seo_description = $seoDesc;
            $content->save();
        }

        return [
            'success' => true,
            'message' => '摘要已同步到SEO描述字段',
            'summary' => $result['summary'],
            'seo_description' => $content->seo_description ?? '',
        ];
    }

    /**
     * 从内容提取纯文本
     */
    private function extractText(Content $content): string
    {
        $text = $content->title . ' ' . ($content->description ?: '');
        $contentText = strip_tags($content->content ?? '');
        $text .= ' ' . mb_substr($contentText, 0, 2000);
        return trim($text);
    }

    /**
     * 按类型生成摘要
     */
    private function generateByType(string $text, string $type): string
    {
        $limits = [
            self::TYPE_SHORT => 50,
            self::TYPE_MEDIUM => 150,
            self::TYPE_LONG => 300,
        ];

        $limit = $limits[$type] ?? 150;

        // 简易摘要：提取前N个字符，在句子边界截断
        $sentences = preg_split('/(?<=[。！？\.\!\?])\s*/u', $text);
        $summary = '';
        foreach ($sentences as $sentence) {
            if (mb_strlen($summary . $sentence) > $limit) {
                break;
            }
            $summary .= $sentence;
        }

        if (empty($summary)) {
            $summary = mb_substr($text, 0, $limit);
        }

        return trim($summary);
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
