<?php
declare(strict_types=1);
namespace app\common\service\home;

use app\common\model\Content;
use app\common\model\Config;

class RssFeedService
{
    public static function generateFeed(string $type = 'news', int $limit = 20): string
    {
        $typeMap = ['product' => 1, 'case' => 2, 'news' => 3, 'download' => 4, 'job' => 5];
        $contentType = $typeMap[$type] ?? 3;
        $items = Content::where('status', 2)->where('type', $contentType)->order('id', 'desc')->limit($limit)->select();
        $siteName = Config::getValue('site_name', '八界AI-CMS');
        $siteUrl = Config::getValue('site_url', 'http://localhost');
        $siteDesc = Config::getValue('site_description', '八界AI-CMS内容管理系统');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>' . "\n";
        $xml .= '<title>' . htmlspecialchars($siteName) . '</title><link>' . htmlspecialchars($siteUrl) . '</link>';
        $xml .= '<description>' . htmlspecialchars($siteDesc) . '</description><language>zh-CN</language>';
        $xml .= '<lastBuildDate>' . date('r') . '</lastBuildDate>';
        $xml .= '<atom:link href="' . htmlspecialchars($siteUrl . '/rss/' . $type) . '" rel="self" type="application/rss+xml" />';
        foreach ($items as $item) {
            $xml .= '<item><title>' . htmlspecialchars($item->title) . '</title>';
            $xml .= '<link>' . htmlspecialchars($siteUrl . $item->url) . '</link>';
            $xml .= '<description>' . htmlspecialchars(mb_substr(strip_tags($item->content ?? ''), 0, 200)) . '</description>';
            $xml .= '<pubDate>' . date('r', (int)$item->create_time) . '</pubDate>';
            $xml .= '<guid isPermaLink="true">' . htmlspecialchars($siteUrl . $item->url) . '</guid></item>' . "\n";
        }
        $xml .= '</channel></rss>';
        return $xml;
    }
}
