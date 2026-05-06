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

        $this->assign([
            'keyword' => $keyword,
            'list' => $result['hits'],
            'total' => $result['total'],
            'page' => $result['page'],
            'cate_id' => $cateId,
            'type' => $type,
        ]);

        return $this->view('/search');
    }
}
