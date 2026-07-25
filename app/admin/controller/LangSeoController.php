<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ml\LangSeoService;
use app\common\service\ml\LangSlugService;

/**
 * 多语言SEO管理控制器 — V2.9.34 ML-2
 */
class LangSeoController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $langSiteId = (int)$this->request->param('lang_site_id', 0);
        $service = new LangSeoService();
        $hreflang = $service->generateHreflang(0);
        $this->assign('hreflang', $hreflang);
        $this->assign('lang_site_id', $langSiteId);
        $this->assign('menuActive', 'lang_seo');
        return $this->view('/lang_seo/index');
    }

    public function save()
    {
        $data = $this->request->post();
        $service = new LangSlugService();
        $result = $service->saveSlug(
            (int)($data['content_id'] ?? 0),
            (string)($data['lang_code'] ?? ''),
            (string)($data['slug'] ?? '')
        );
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function generateSitemap()
    {
        $langSiteId = (int)$this->request->param('lang_site_id', 0);
        $service = new LangSeoService();
        $result = $service->generateSitemap($langSiteId);
        if ($result['success'] ?? false) {
            return $this->success('Sitemap生成成功', $result);
        }
        return $this->error($result['message'] ?? '生成失败');
    }
}
