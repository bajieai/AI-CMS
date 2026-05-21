<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\SeoService;
use think\Request;

/**
 * SEO管理
 */
class SeoController extends AdminBaseController
{
    protected SeoService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new SeoService;
    }

    public function index()
    {
        $sitemapExists = file_exists(public_path() . 'sitemap.xml');
        $robotsContent = $this->service->generateRobots();
        return $this->view('/seo_dashboard', [
            'sitemap_exists' => $sitemapExists,
            'robots_content' => $robotsContent,
        ]);
    }

    public function generateSitemap()
    {
        $success = $this->service->saveSitemap();
        return json(['success' => $success, 'msg' => $success ? 'Sitemap生成成功' : '生成失败']);
    }

    public function saveRobots(Request $request)
    {
        $content = $request->post('content', '');
        // 更新config表
        \app\common\model\Config::where('name', 'seo_robots_txt')->update(['value' => $content]);
        $success = $this->service->saveRobots();
        return json(['success' => $success, 'msg' => $success ? 'robots.txt保存成功' : '保存失败']);
    }
}