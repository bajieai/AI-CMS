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
        $keyword = $this->request->param('keyword', '');

        $list = Content::where('status', 2)
            ->where('title', 'like', '%' . $keyword . '%')
            ->order('id', 'desc')
            ->paginate(12, false, ['query' => ['keyword' => $keyword]]);

        $this->assign([
            'keyword' => $keyword,
            'list' => $list,
        ]);

        return $this->view('/search');
    }
}
