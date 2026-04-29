<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\Content;
use app\common\model\Cate;
use app\common\service\SeoService;

/**
 * 前台内容控制器
 */
class ContentController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 内容详情页
     * 路由：/product/{id}, /news/{id} 等（通过append传入type参数）
     */
    public function detail(int $id)
    {
        $info = Content::with(['cate', 'user', 'ext', 'tags'])->find($id);

        if (empty($info) || $info->status != 2) {
            abort(404, '内容不存在');
        }

        // 增加浏览量
        $info->inc('views')->update();

        // 获取相关内容
        $related = Content::where('cate_id', $info->cate_id)
            ->where('id', '<>', $id)
            ->where('status', 2)
            ->limit(4)
            ->select();

        $typeMap = [1 => 'product', 2 => 'case', 3 => 'news', 4 => 'download', 5 => 'job', 6 => 'page'];
        $typeUrl = '/' . ($typeMap[$info->type] ?? 'info');

        // V2.3 JSON-LD结构化数据
        $seoService = new SeoService();
        $jsonLd = $seoService->buildJsonLd([
            'type'        => 'Article',
            'title'       => $info->seo_title ?: $info->title,
            'description' => $info->seo_description ?: $info->excerpt,
            'url'         => request()->url(true),
            'cover'       => $info->cover,
            'create_time' => $info->create_time,
            'update_time' => $info->update_time,
        ]);

        $this->assign([
            'info'     => $info,
            'related'  => $related,
            'type_url' => $typeUrl,
            'jsonLd'   => $jsonLd,
        ]);

        return $this->view('/detail');
    }
}
