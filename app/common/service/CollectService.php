<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\CollectSource;
use app\common\model\CollectLog;
use app\common\model\Content;
use think\facade\Log;

/**
 * 内容采集服务 - V2.5新增
 * RSS采集 + 网页采集 + AI改写 + 去重
 */
class CollectService
{
    /**
     * 执行采集任务（别名）
     */
    public static function runCollect(int $sourceId, bool $rewrite = false): array
    {
        $count = self::executeCollect($sourceId);
        return ['added' => $count, 'skipped' => 0];
    }

    /**
     * 执行采集任务
     */
    public static function executeCollect(int $sourceId): int
    {
        $source = CollectSource::find($sourceId);
        if (!$source || !$source->is_enabled) return 0;

        $count = 0;
        try {
            if ($source->type === 'rss') {
                $count = self::collectRss($source);
            } elseif ($source->type === 'webpage') {
                $count = self::collectWebpage($source);
            }

            $source->last_collect_time = time();
            $source->save();
        } catch (\Exception $e) {
            Log::error("采集任务失败 #{$sourceId}: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * RSS采集
     */
    protected static function collectRss(CollectSource $source): int
    {
        $count = 0;
        try {
            $xml = @simplexml_load_file($source->url);
            if (!$xml) return 0;

            $items = $xml->channel->item ?? [];
            foreach ($items as $item) {
                $title = (string) ($item->title ?? '');
                $url = (string) ($item->link ?? '');
                $content = (string) ($item->description ?? '');
                $pubTime = strtotime((string) ($item->pubDate ?? '')) ?: time();

                if (empty($url) || self::isUrlCollected($url)) continue;

                $count += self::saveCollected($source, $title, $url, $content, $pubTime);
            }
        } catch (\Exception $e) {
            Log::error("RSS采集失败: " . $e->getMessage());
        }
        return $count;
    }

    /**
     * 网页采集（使用file_get_contents + DOMDocument）
     */
    protected static function collectWebpage(CollectSource $source): int
    {
        $count = 0;
        try {
            $html = @file_get_contents($source->url);
            if (empty($html)) return 0;

            $rules = $source->rules;
            $dom = new \DOMDocument();
            @$dom->loadHTML($html, LIBXML_NOERROR);
            $xpath = new \DOMXPath($dom);

            // 提取链接列表
            $linkSelector = $rules['link_selector'] ?? 'a';
            $links = $xpath->query($linkSelector);

            foreach ($links as $link) {
                $url = $link->getAttribute('href');
                if (empty($url) || self::isUrlCollected($url)) continue;

                // 补全相对URL
                if (!str_starts_with($url, 'http')) {
                    $parsed = parse_url($source->url);
                    $url = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? '') . '/' . ltrim($url, '/');
                }

                // 采集详情页
                $detailHtml = @file_get_contents($url);
                if (empty($detailHtml)) continue;

                $detailDom = new \DOMDocument();
                @$detailDom->loadHTML($detailHtml, LIBXML_NOERROR);
                $detailXpath = new \DOMXPath($detailDom);

                $titleSelector = $rules['title_selector'] ?? '//h1';
                $contentSelector = $rules['content_selector'] ?? '//article';

                $titleNodes = $detailXpath->query($titleSelector);
                $contentNodes = $detailXpath->query($contentSelector);

                $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
                $content = '';
                if ($contentNodes->length > 0) {
                    $content = $detailDom->saveHTML($contentNodes->item(0));
                }

                if (empty($title)) continue;
                $count += self::saveCollected($source, $title, $url, $content);
            }
        } catch (\Exception $e) {
            Log::error("网页采集失败: " . $e->getMessage());
        }
        return $count;
    }

    /**
     * AI改写内容
     */
    public static function rewriteWithAi(int $logId): ?array
    {
        $log = CollectLog::find($logId);
        if (!$log) return null;

        try {
            $provider = \app\common\service\ai\AiProviderFactory::getDefault();
            $prompt = "请对以下内容进行深度改写，要求：\n1. 换用不同的表达方式\n2. 重组文章结构\n3. 补充你的观点和分析\n4. 确保改写后内容具有原创性\n\n标题：{$log->title}\n\n原文：\n" . mb_substr($log->content ?? '', 0, 2000);
            $rewritten = $provider->write($prompt, [
                'system_prompt' => '你是一位专业的内容编辑，擅长改写文章使其具有原创性。',
                'max_tokens' => 2000,
            ]);

            return ['title' => $log->title, 'content' => $rewritten];
        } catch (\Exception $e) {
            Log::error("AI改写失败 #{$logId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 将采集内容导入为草稿
     */
    public static function importAsDraft(int $logId, ?string $content = null): bool
    {
        $log = CollectLog::find($logId);
        if (!$log) return false;

        $source = CollectSource::find($log->source_id);
        $articleContent = $content ?? $log->content ?? '';

        $contentModel = Content::create([
            'title' => $log->title,
            'content' => $articleContent,
            'cate_id' => $source ? $source->cate_id : 0,
            'status' => 0,
            'source' => 'collect',
            'create_time' => time(),
            'update_time' => time(),
        ]);

        $log->status = 1;
        $log->content_id = $contentModel->id;
        $log->save();

        return true;
    }

    /**
     * 检查URL是否已采集
     */
    protected static function isUrlCollected(string $url): bool
    {
        $hash = md5($url);
        return CollectLog::where('url_hash', $hash)->count() > 0;
    }

    /**
     * 保存采集记录
     */
    protected static function saveCollected(CollectSource $source, string $title, string $url, string $content, int $pubTime = 0): int
    {
        $urlHash = md5($url);
        $existing = CollectLog::where('url_hash', $urlHash)->find();
        if ($existing) return 0;

        CollectLog::create([
            'source_id' => $source->id,
            'title' => mb_substr($title, 0, 500),
            'url' => $url,
            'url_hash' => $urlHash,
            'content' => $content,
            'pub_time' => $pubTime,
            'status' => 0,
        ]);

        return 1;
    }

    /**
     * 获取采集来源列表
     */
    public static function getSources(int $page = 1, int $limit = 20): array
    {
        return CollectSource::order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取采集日志
     */
    public static function getLogs(int $sourceId = 0, int $page = 1, int $limit = 20): array
    {
        $query = CollectLog::order('id', 'desc');
        if ($sourceId > 0) $query->where('source_id', $sourceId);
        return $query->page($page, $limit)->select()->toArray();
    }
}
