<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Content;

/**
 * 前台搜索控制器
 */
class SearchController extends FrontBaseController
{
    /**
     * 搜索页
     */
    public function index()
    {
        $keyword = trim($this->request->param('keyword', ''));

        if (empty($keyword)) {
            $this->assign(['keyword' => '', 'list' => []]);
            return $this->view('/search');
        }

        // 尝试使用 MySQL FULLTEXT 索引搜索，失败时降级到 LIKE
        try {
            $safeKeyword = preg_replace('/[+\-<>()@~*"\']+/', ' ', $keyword);
            $words = array_filter(explode(' ', $safeKeyword));
            $matchKeyword = implode(' ', array_map(fn($w) => '+' . $w . '*', $words));

            $list = Content::where('status', 2)
                ->whereRaw("MATCH(title, excerpt) AGAINST(? IN BOOLEAN MODE)", [$matchKeyword])
                ->order('id', 'desc')
                ->paginate(12, false, ['query' => ['keyword' => $keyword]]);
        } catch (\Exception) {
            // FULLTEXT 不可用，降级到 LIKE
            $list = Content::where('status', 2)
                ->where('title', 'like', '%' . $keyword . '%')
                ->order('id', 'desc')
                ->paginate(12, false, ['query' => ['keyword' => $keyword]]);
        }

        $this->assign([
            'keyword' => $keyword,
            'list' => $list,
        ]);

        return $this->view('/search');
    }
}
