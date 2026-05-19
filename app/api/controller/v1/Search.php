<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\api\controller\v1;

use app\common\service\MeilisearchService;
use app\common\model\SearchKeyword;
use think\Request;

/**
 * 搜索API - V2.7增强：联想搜索+热门关键词
 */
class Search
{
    /**
     * 搜索联想（前缀匹配）
     */
    public function suggest(Request $request)
    {
        $keyword = trim($request->get('keyword', ''));
        $limit = min((int) $request->get('limit', 10), 20);

        if (empty($keyword) || mb_strlen($keyword) < 1) {
            return json(['code' => 0, 'data' => []]);
        }

        // 优先Meilisearch搜索建议
        $suggestions = [];
        if (MeilisearchService::isAvailable()) {
            $result = MeilisearchService::search($keyword, [], 1, $limit);
            foreach ($result['hits'] as $hit) {
                $suggestions[] = [
                    'title' => $hit['title'] ?? '',
                    'id' => $hit['id'] ?? 0,
                    'type' => 'content',
                ];
            }
        }

        // 补充历史热词前缀匹配
        $hotPrefix = SearchKeyword::where('keyword', 'like', $keyword . '%')
            ->order('count', 'desc')
            ->limit($limit)
            ->column('keyword');

        foreach ($hotPrefix as $word) {
            $exists = false;
            foreach ($suggestions as $s) {
                if ($s['title'] === $word) { $exists = true; break; }
            }
            if (!$exists) {
                $suggestions[] = ['title' => $word, 'id' => 0, 'type' => 'keyword'];
            }
        }

        return json(['code' => 0, 'data' => array_slice($suggestions, 0, $limit)]);
    }

    /**
     * 热门搜索关键词
     */
    public function hot(Request $request)
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $days = (int) $request->get('days', 7);

        $since = time() - $days * 86400;
        $list = SearchKeyword::where('last_search_time', '>=', $since)
            ->order('count', 'desc')
            ->limit($limit)
            ->select();

        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * 执行搜索（兼容无Meilisearch时回退MySQL）
     */
    public function index(Request $request)
    {
        $keyword = trim($request->get('keyword', ''));
        $page = (int) $request->get('page', 1);
        $limit = min((int) $request->get('limit', 20), 50);
        $cateId = (int) $request->get('cate_id', 0);

        if (empty($keyword)) {
            return json(['code' => 0, 'data' => ['hits' => [], 'total' => 0, 'page' => $page]]);
        }

        $filters = [];
        if ($cateId > 0) {
            $filters['cate_id'] = $cateId;
        }

        if (MeilisearchService::isAvailable()) {
            $result = MeilisearchService::search($keyword, $filters, $page, $limit);
        } else {
            // MySQL回退
            $result = $this->fallbackMysqlSearch($keyword, $cateId, $page, $limit);
        }

        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * MySQL回退搜索
     */
    protected function fallbackMysqlSearch(string $keyword, int $cateId, int $page, int $limit): array
    {
        $query = \app\common\model\Content::where('status', 2)
            ->where(function ($q) use ($keyword) {
                $q->whereLike('title', "%{$keyword}%")
                  ->whereOrLike('content', "%{$keyword}%");
            });
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        $total = $query->count();
        $list = $query->page($page, $limit)->select();

        $hits = [];
        foreach ($list as $item) {
            $hits[] = [
                'id' => (string) $item->id,
                'title' => $item->title,
                'excerpt' => $item->excerpt,
                'cover' => $item->cover,
                'views' => $item->views,
                'create_time' => $item->create_time,
            ];
        }

        return ['hits' => $hits, 'total' => $total, 'page' => $page];
    }
}
