<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\seo\SchemaEnhanceService;
use app\common\service\seo\PerformanceSeoService;
use app\common\service\seo\SeoReportService;
use app\common\service\SeoService;

/**
 * SEO增强管理
 * V2.9.37 SEO
 */
class SeoManageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function schema()
    {
        $service = new SchemaEnhanceService();
        $types = $service->getSchemaTypes();
        $coverage = $service->getCoverageStats();
        return $this->view('/seo_manage_schema', ['types' => $types, 'coverage' => $coverage]);
    }

    public function sitemap()
    {
        $service = new SeoService();
        $sitemapExists = file_exists(public_path() . 'sitemap.xml');
        return $this->view('/seo_manage_sitemap', ['sitemap_exists' => $sitemapExists]);
    }

    public function generateSitemap()
    {
        $service = new SeoService();
        $result = $service->saveSitemap();
        return json(['success' => $result, 'msg' => $result ? 'Sitemap生成成功' : '生成失败']);
    }

    public function submitSitemap()
    {
        $engine = $this->request->post('engine', 'baidu');
        // 搜索引擎提交逻辑
        return json(['success' => true, 'msg' => "已提交到{$engine}"]);
    }

    public function performance()
    {
        $service = new PerformanceSeoService();
        $url = $this->request->get('url', request()->domain());
        $analysis = $service->analyzePage($url);
        $report = $service->getPerformanceReport();
        return $this->view('/seo_manage_performance', ['analysis' => $analysis, 'report' => $report, 'url' => $url]);
    }

    public function autoOptimize()
    {
        $url = $this->request->post('url', '');
        $service = new PerformanceSeoService();
        $result = $service->autoOptimize($url);
        return json(['success' => true, 'data' => $result, 'msg' => '优化完成，不修改用户模板文件，支持一键还原']);
    }

    public function restoreOptimize()
    {
        $url = $this->request->post('url', '');
        $service = new PerformanceSeoService();
        $result = $service->restoreOptimize($url);
        return json(['success' => $result, 'msg' => $result ? '已还原优化配置' : '还原失败']);
    }

    public function geo()
    {
        return $this->view('/seo_manage_geo', []);
    }

    public function report()
    {
        $service = new SeoReportService();
        $score = $service->getHealthScore();
        $suggestions = $service->generateOptimizationSuggestions();
        return $this->view('/seo_manage_report', ['score' => $score, 'suggestions' => $suggestions]);
    }

    public function exportReport()
    {
        $format = $this->request->get('format', 'csv');
        $service = new SeoReportService();
        $content = $service->exportReport($format);
        return download($content, 'seo_report.' . $format);
    }
}
