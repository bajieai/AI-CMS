<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\SeoService;
use think\Response;

/**
 * Sitemap控制器 - V2.9.2 M19b
 */
class SitemapController extends FrontBaseController
{
    /**
     * 主Sitemap
     */
    public function index(): Response
    {
        $service = new SeoService();
        $xml = $service->generateSitemap();
        return Response::create($xml)->contentType('application/xml');
    }

    /**
     * Sitemap分页子文件
     */
    public function chunk(int $page = 1): Response
    {
        $service = new SeoService();
        $xml = $service->generateSitemapChunk($page);
        return Response::create($xml)->contentType('application/xml');
    }

    /**
     * 多语言Sitemap
     */
    public function lang(string $langCode = ''): Response
    {
        if (empty($langCode)) {
            $langCode = \app\common\service\LanguageService::getCurrentLang();
        }

        $service = new SeoService();
        $xml = $service->generateLangSitemap($langCode);
        return Response::create($xml)->contentType('application/xml');
    }

    /**
     * robots.txt
     */
    public function robots(): Response
    {
        $service = new SeoService();
        $content = $service->generateRobots();
        return Response::create($content)->contentType('text/plain');
    }
}
