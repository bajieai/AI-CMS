<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use GuzzleHttp\Client;
use think\facade\Config;
use think\facade\Log;

/**
 * Meilisearch全站搜索服务 - V2.6
 * 基于GuzzleHttp直接调用Meilisearch REST API，无需安装官方SDK
 */
class MeilisearchService
{
    private static ?Client $client = null;
    private static string $indexName = 'i8j_content';

    /**
     * 获取HTTP客户端
     */
    private static function getClient(): Client
    {
        if (!self::$client) {
            $host = Config::get('meilisearch.host', 'http://localhost:7700');
            $apiKey = Config::get('meilisearch.api_key', '');
            $headers = ['Content-Type' => 'application/json'];
            if ($apiKey) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }
            self::$client = new Client([
                'base_uri' => rtrim($host, '/') . '/',
                'timeout' => 30,
                'headers' => $headers,
            ]);
        }
        return self::$client;
    }

    /**
     * 检查Meilisearch是否可用
     */
    public static function isAvailable(): bool
    {
        try {
            $response = self::getClient()->get('health');
            $data = json_decode((string) $response->getBody(), true);
            return ($data['status'] ?? '') === 'available';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 创建或更新索引设置
     */
    public static function setupIndex(): bool
    {
        try {
            $client = self::getClient();

            // 创建索引
            try {
                $client->post('indexes', [
                    'json' => [
                        'uid' => self::$indexName,
                        'primaryKey' => 'id',
                    ],
                ]);
            } catch (\Exception $e) {
                // 索引已存在，忽略
                if (!str_contains($e->getMessage(), 'already exists')) {
                    throw $e;
                }
            }

            // 设置可搜索字段和过滤字段
            $client->put('indexes/' . self::$indexName . '/settings', [
                'json' => [
                    'searchableAttributes' => ['title', 'content', 'excerpt', 'cate_name'],
                    'filterableAttributes' => ['type', 'cate_id', 'status', 'is_paid'],
                    'sortableAttributes' => ['create_time', 'views'],
                    'rankingRules' => [
                        'words',
                        'typo',
                        'proximity',
                        'attribute',
                        'sort',
                        'exactness',
                    ],
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Meilisearch索引设置失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 同步单条内容到索引
     */
    public static function syncDocument(Content $content): bool
    {
        if (!self::isAvailable()) return false;

        try {
            $doc = [
                'id' => (string) $content->id,
                'title' => $content->title,
                'content' => strip_tags($content->content),
                'excerpt' => $content->excerpt,
                'cate_id' => $content->cate_id,
                'cate_name' => $content->cate->name ?? '',
                'type' => $content->type,
                'status' => $content->status,
                'is_paid' => $content->is_paid,
                'cover' => $content->cover,
                'views' => $content->views,
                'create_time' => $content->create_time,
                'url' => $content->url ?? '',
            ];

            self::getClient()->put('indexes/' . self::$indexName . '/documents', [
                'json' => [$doc],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Meilisearch同步文档失败 [id:{$content->id}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量同步内容到索引
     */
    public static function syncAll(int $batchSize = 500): array
    {
        if (!self::isAvailable()) {
            return ['success' => false, 'msg' => 'Meilisearch服务不可用'];
        }

        self::setupIndex();

        $total = 0;
        $success = 0;
        $page = 1;

        try {
            do {
                $list = Content::where('status', 2)
                    ->with('cate')
                    ->page($page, $batchSize)
                    ->select();

                if ($list->isEmpty()) break;

                $docs = [];
                foreach ($list as $content) {
                    $docs[] = [
                        'id' => (string) $content->id,
                        'title' => $content->title,
                        'content' => strip_tags($content->content),
                        'excerpt' => $content->excerpt,
                        'cate_id' => $content->cate_id,
                        'cate_name' => $content->cate->name ?? '',
                        'type' => $content->type,
                        'status' => $content->status,
                        'is_paid' => $content->is_paid,
                        'cover' => $content->cover,
                        'views' => $content->views,
                        'create_time' => $content->create_time,
                        'url' => $content->url ?? '',
                    ];
                }

                self::getClient()->put('indexes/' . self::$indexName . '/documents', [
                    'json' => $docs,
                ]);

                $count = count($docs);
                $total += $count;
                $success += $count;
                $page++;
            } while ($count === $batchSize);

            CacheService::clearByTag(CacheService::TAG_SEARCH);
            return ['success' => true, 'msg' => "同步完成，共{$total}条", 'total' => $total];
        } catch (\Exception $e) {
            Log::error('Meilisearch批量同步失败: ' . $e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage(), 'total' => $total];
        }
    }

    /**
     * 从索引删除文档
     */
    public static function deleteDocument(int $contentId): bool
    {
        if (!self::isAvailable()) return false;

        try {
            self::getClient()->delete('indexes/' . self::$indexName . '/documents/' . $contentId);
            return true;
        } catch (\Exception $e) {
            Log::error("Meilisearch删除文档失败 [id:{$contentId}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 执行搜索
     */
    public static function search(string $keyword, array $filters = [], int $page = 1, int $limit = 20): array
    {
        if (!self::isAvailable() || empty($keyword)) {
            return ['hits' => [], 'total' => 0, 'page' => $page];
        }

        try {
            $body = [
                'q' => $keyword,
                'limit' => $limit,
                'offset' => ($page - 1) * $limit,
                'attributesToHighlight' => ['title', 'content'],
                'highlightPreTag' => '<mark>',
                'highlightPostTag' => '</mark>',
            ];

            if (!empty($filters)) {
                $filterParts = [];
                foreach ($filters as $key => $val) {
                    if (is_array($val)) {
                        $filterParts[] = $key . ' IN [' . implode(',', $val) . ']';
                    } else {
                        $filterParts[] = $key . ' = ' . $val;
                    }
                }
                $body['filter'] = implode(' AND ', $filterParts);
            }

            $response = self::getClient()->post('indexes/' . self::$indexName . '/search', [
                'json' => $body,
            ]);

            $result = json_decode((string) $response->getBody(), true);

            // 记录搜索关键词
            if (!empty($keyword)) {
                self::recordKeyword($keyword);
            }

            return [
                'hits' => $result['hits'] ?? [],
                'total' => $result['totalHits'] ?? ($result['estimatedTotalHits'] ?? 0),
                'page' => $page,
                'processingTimeMs' => $result['processingTimeMs'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Meilisearch搜索失败: ' . $e->getMessage());
            return ['hits' => [], 'total' => 0, 'page' => $page];
        }
    }

    /**
     * 记录搜索关键词（用于热词统计）
     */
    protected static function recordKeyword(string $keyword): void
    {
        try {
            $keyword = trim(mb_substr($keyword, 0, 100));
            if (empty($keyword)) return;

            $exists = \app\common\model\SearchKeyword::where('keyword', $keyword)->find();
            if ($exists) {
                $exists->inc('count')->update();
                $exists->last_search_time = time();
                $exists->save();
            } else {
                \app\common\model\SearchKeyword::create([
                    'keyword' => $keyword,
                    'count' => 1,
                    'last_search_time' => time(),
                    'create_time' => time(),
                ]);
            }
        } catch (\Exception $e) {
            // 忽略关键词记录错误
        }
    }
}
