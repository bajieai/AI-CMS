<?php
declare(strict_types=1);
namespace app\common\service\social;

use app\common\model\TemplateStore;

class ShareService
{
    public static function generateShareLinks(int $templateId, int $memberId = 0): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return [];
        $url = '/template_store/preview/' . $templateId;
        $title = $template->name . ' - 八界AI-CMS模板商店';
        $desc = $template->description ?? '精选模板，快速建站';
        $img = $template->cover ?? '';
        $utmUrl = $url . '?utm_source=share';
        if ($memberId > 0) $utmUrl .= '&utm_member=' . $memberId;
        return [
            'url' => $utmUrl, 'title' => $title, 'description' => $desc, 'image' => $img,
            'wechat' => $utmUrl,
            'weibo' => 'https://service.weibo.com/share/share.php?url=' . urlencode($utmUrl) . '&title=' . urlencode($title),
            'qq' => 'https://connect.qq.com/widget/shareqq/index.html?url=' . urlencode($utmUrl) . '&title=' . urlencode($title) . '&summary=' . urlencode($desc),
            'qzone' => 'https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=' . urlencode($utmUrl) . '&title=' . urlencode($title),
            'twitter' => 'https://twitter.com/intent/tweet?url=' . urlencode($utmUrl) . '&text=' . urlencode($title),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($utmUrl),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($utmUrl),
        ];
    }

    public static function generateOgMeta(int $templateId): string
    {
        $links = self::generateShareLinks($templateId);
        if (empty($links)) return '';
        return '<meta property="og:title" content="' . htmlspecialchars($links['title']) . '">'
            . '<meta property="og:description" content="' . htmlspecialchars($links['description']) . '">'
            . '<meta property="og:image" content="' . htmlspecialchars($links['image']) . '">'
            . '<meta property="og:url" content="' . htmlspecialchars($links['url']) . '">'
            . '<meta property="og:type" content="website">';
    }
}
