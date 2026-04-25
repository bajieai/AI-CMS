<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Cate;
use app\common\model\Content;
use app\common\service\CateService;

/**
 * 前台分类控制器
 */
class CateController extends FrontBaseController
{
    /**
     * 分类列表页
     * 路由：/product, /news 等（通过append传入type参数）
     */
    public function listing()
    {
        $typeSlug = $this->request->param('type', 'product');
        $typeMap = ['product' => 1, 'case' => 2, 'news' => 3, 'download' => 4, 'job' => 5, 'page' => 6];
        $type = $typeMap[$typeSlug] ?? 1;
        $cateId = (int) $this->request->param('cate_id', 0);

        // 获取分类列表（树形结构）
        $cateService = new CateService();
        $cateList = $cateService->getCatelist($typeSlug, 100, 0);
        $cates = $cateService->getTree($cateList->toArray());

        // 获取内容列表
        $query = Content::where('status', 2)->where('type', $type);
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        $list = $query->order('id', 'desc')->paginate(12);

        // 获取当前分类SEO信息
        $currentCate = null;
        if ($cateId > 0) {
            $currentCate = Cate::find($cateId);
        }

        $this->assign([
            'type' => $type,
            'cate_id' => $cateId,
            'cates' => $cates,
            'list' => $list,
            'type_slug' => $typeSlug,
            'current_cate' => $currentCate,
        ]);

        return $this->view('/list');
    }
}
