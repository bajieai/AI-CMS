<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\MeilisearchService;

/**
 * 前台搜索控制器 - V2.6
 */
class SearchController extends FrontBaseController
{
    /**
     * 搜索结果页
     */
    public function index()
    {
        $keyword = trim($this->request->get('keyword', ''));
        $page = (int) $this->request->get('page', 1);
        $cateId = (int) $this->request->get('cate_id', 0);
        $type = (int) $this->request->get('type', 0);

        $filters = [];
        if ($cateId > 0) {
            $filters['cate_id'] = $cateId;
        }
        if ($type > 0) {
            $filters['type'] = $type;
        }

        $result = ['hits' => [], 'total' => 0, 'page' => $page];

        if (!empty($keyword)) {
            $result = MeilisearchService::search($keyword, $filters, $page, 20);
        }

        // 预处理高亮字段
        $hits = $result['hits'];
        foreach ($hits as &$hit) {
            if (!empty($hit['_formatted']['title'])) {
                $hit['title_highlight'] = $hit['_formatted']['title'];
            }
            if (!empty($hit['_formatted']['content'])) {
                $hit['content_highlight'] = $hit['_formatted']['content'];
            }
        }
        unset($hit);

        $this->assign([
            'keyword' => $keyword,
            'list' => $hits,
            'total' => $result['total'],
            'page' => $result['page'],
            'cate_id' => $cateId,
            'type' => $type,
        ]);

        return $this->view('/search');
    }

    /**
     * 搜索关键词联想建议
     */
    public function suggest()
    {
        $keyword = trim($this->request->get('keyword', ''));
        if (empty($keyword) || mb_strlen($keyword) < 2) {
            return json(['code' => 0, 'data' => []]);
        }

        // 优先从热门搜索词匹配
        $suggestions = \app\common\model\SearchKeyword::where('keyword', 'like', $keyword . '%')
            ->order('count', 'desc')
            ->limit(8)
            ->column('keyword');

        return json(['code' => 0, 'data' => array_values($suggestions)]);
    }
}
