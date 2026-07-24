<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\Content;

/**
 * AI智能配图服务 — V2.9.30 AI2-3
 * 采用模板配图库方案（小扣审核决策Q8），不对接第三方API
 */
class AiImageGenerateService
{
    public const STYLE_AUTO = 'auto';
    public const STYLE_TECH = 'tech';
    public const STYLE_BUSINESS = 'business';
    public const STYLE_NATURE = 'nature';
    public const STYLE_CREATIVE = 'creative';

    public const SIZE_LANDSCAPE = '16:9';
    public const SIZE_SQUARE = '1:1';
    public const SIZE_PORTRAIT = '9:16';

    /**
     * 模板配图库（按风格分类的占位图URL）
     */
    private const IMAGE_LIBRARY = [
        'tech' => [
            'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1024&h=576',
            'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1024&h=576',
            'https://images.unsplash.com/photo-1504384308090-c894fd4958e2?w=1024&h=576',
        ],
        'business' => [
            'https://images.unsplash.com/photo-1664575602276-acd073f104c1?w=1024&h=576',
            'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1024&h=576',
            'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1024&h=576',
        ],
        'nature' => [
            'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1024&h=576',
            'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1024&h=576',
            'https://images.unsplash.com/photo-1426604966848-d7adac402bff?w=1024&h=576',
        ],
        'creative' => [
            'https://images.unsplash.com/photo-1561214115-f2f134cc4912?w=1024&h=576',
            'https://images.unsplash.com/photo-1545987796-200677ee1011?w=1024&h=576',
            'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=1024&h=576',
        ],
    ];

    /**
     * 基于内容生成配图
     */
    public function generate(int $contentId, string $style = self::STYLE_AUTO,
                              string $size = self::SIZE_LANDSCAPE): array
    {
        $content = Content::find($contentId);
        if (!$content) {
            return ['success' => false, 'message' => '内容不存在'];
        }

        // 从内容提取关键词作为配图依据
        $prompt = $this->extractPrompt($content, $style);

        // 根据风格从配图库选择图片
        $actualStyle = $style;
        if ($style === self::STYLE_AUTO) {
            $actualStyle = $this->detectStyleFromContent($content);
        }

        $images = self::IMAGE_LIBRARY[$actualStyle] ?? self::IMAGE_LIBRARY['business'];
        $imageUrl = $images[array_rand($images)];

        // 根据尺寸调整URL参数
        $dimensions = $this->parseSize($size);
        if ($dimensions) {
            $imageUrl = preg_replace('/w=\d+&h=\d+/', "w={$dimensions['w']}&h={$dimensions['h']}", $imageUrl);
        }

        return [
            'success' => true,
            'image_url' => $imageUrl,
            'style' => $actualStyle,
            'size' => $size,
            'prompt' => $prompt,
            'cost' => 0,
            'message' => '配图已生成（模板配图库）',
        ];
    }

    /**
     * 从内容提取提示词
     */
    public function extractPrompt(Content $content, string $style): string
    {
        $title = $content->title;
        $desc = mb_substr($content->description ?: '', 0, 100);
        $styleText = $style !== self::STYLE_AUTO ? "{$style}风格" : '自动匹配';
        return "{$styleText}配图 - {$title} - {$desc}";
    }

    /**
     * 从内容自动检测风格
     */
    private function detectStyleFromContent(Content $content): string
    {
        $text = $content->title . ' ' . $content->description . ' ' . mb_substr($content->content ?? '', 0, 500);
        if (preg_match('/科技|技术|AI|互联网|软件|数字化/i', $text)) return self::STYLE_TECH;
        if (preg_match('/商业|企业|商务|管理|营销/i', $text)) return self::STYLE_BUSINESS;
        if (preg_match('/自然|风景|旅行|环境|生态/i', $text)) return self::STYLE_NATURE;
        if (preg_match('/创意|设计|艺术|创意|灵感/i', $text)) return self::STYLE_CREATIVE;
        return self::STYLE_BUSINESS;
    }

    /**
     * 解析尺寸
     */
    private function parseSize(string $size): ?array
    {
        $sizes = [
            self::SIZE_LANDSCAPE => ['w' => 1024, 'h' => 576],
            self::SIZE_SQUARE => ['w' => 800, 'h' => 800],
            self::SIZE_PORTRAIT => ['w' => 576, 'h' => 1024],
        ];
        return $sizes[$size] ?? null;
    }
}
