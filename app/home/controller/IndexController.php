<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;

/**
 * 前台首页控制器
 */
class IndexController extends FrontBaseController
{
    /**
     * 首页
     * 数据获取已迁移至模板 I8j 标签，控制器保持轻量
     */
    public function index()
    {
        return $this->view('/index');
    }

    /**
     * V2.9.8 C-1: 自定义404页面
     * 路由：GET /404.html
     */
    public function error404()
    {
        $siteName = config('app.site_name', config('app.site_name', 'AI-CMS'));
        $siteUrl = request()->domain();

        // 获取热门文章推荐
        $hotArticles = [];
        try {
            $hotArticles = \app\common\model\Content::where('status', 1)
                ->order('views', 'desc')
                ->limit(5)
                ->field('id,title,url')
                ->select()
                ->toArray();
        } catch (\Exception $e) {
            // 静默处理
        }

        $this->assign('siteName', $siteName);
        $this->assign('siteUrl', $siteUrl);
        $this->assign('hotArticles', $hotArticles);

        return response($this->view('/404'), 404);
    }
}
