<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PERF-4: 静态页面服务
 * 首页/内容页静态化
 */
class StaticPageService
{
    protected PageRenderOptimizeService $renderService;

    public function __construct()
    {
        $this->renderService = new PageRenderOptimizeService();
    }

    /**
     * 生成首页静态HTML
     */
    public function generateHomePage(): bool
    {
        // 获取首页HTML内容（通过内部请求或直接渲染）
        // 实际实现需要根据项目路由结构获取首页内容
        $url = '/';
        $html = $this->fetchInternalPage($url);

        if ($html) {
            $this->renderService->generateStaticPage($url, $html);
            return true;
        }

        return false;
    }

    /**
     * 生成热门内容页静态HTML
     */
    public function generateHotContentPages(int $limit = 10): int
    {
        $hotContents = Db::name('content')
            ->where('status', 1)
            ->order('views', 'desc')
            ->limit($limit)
            ->column('id');

        $count = 0;
        foreach ($hotContents as $contentId) {
            $url = '/info/' . $contentId;
            $html = $this->fetchInternalPage($url);
            if ($html) {
                $this->renderService->generateStaticPage($url, $html);
                $count++;
            }
        }

        return $count;
    }

    /**
     * 清除所有静态页面
     */
    public function clearAll(): int
    {
        return $this->renderService->clearStaticPages();
    }

    /**
     * 内部请求获取页面HTML
     */
    protected function fetchInternalPage(string $url): ?string
    {
        // 使用Guzzle或file_get_contents获取本地页面
        try {
            $baseUrl = $this->request->host(true);
            $fullUrl = $baseUrl . $url;
            $html = @file_get_contents($fullUrl);
            return $html !== false ? $html : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
