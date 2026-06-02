<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

/**
 * V2.9.16: SEO诊断引擎
 *
 * 提供站点SEO健康度综合检测，包括：
 *   - 基础配置检测（标题/描述/关键词/Sitemap/Robots）
 *   - 内容质量检测（标题缺失/重复/描述缺失/图片ALT缺失）
 *   - 技术SEO检测（HTTPS/响应速度/移动端适配）
 *   - 综合评分与改进建议
 */
class SeoDiagnoseController extends AdminBaseController
{
    /**
     * 诊断结果页
     */
    public function index()
    {
        $report = $this->runDiagnosis();
        return $this->view('/seo_diagnose', [
            'report' => $report,
        ]);
    }

    /**
     * AJAX执行诊断
     */
    public function run()
    {
        $report = $this->runDiagnosis();
        return json(['success' => true, 'data' => $report]);
    }

    /**
     * 执行完整SEO诊断
     */
    protected function runDiagnosis(): array
    {
        $checks = [];
        $score  = 0;
        $total  = 0;

        // ========== 1. 基础配置检测 ==========
        $basicChecks = $this->checkBasicConfig();
        foreach ($basicChecks as $c) {
            $checks[] = $c;
            $total += $c['weight'];
            if ($c['pass']) $score += $c['weight'];
        }

        // ========== 2. 内容质量检测 ==========
        $contentChecks = $this->checkContentQuality();
        foreach ($contentChecks as $c) {
            $checks[] = $c;
            $total += $c['weight'];
            if ($c['pass']) $score += $c['weight'];
        }

        // ========== 3. 技术SEO检测 ==========
        $techChecks = $this->checkTechnicalSeo();
        foreach ($techChecks as $c) {
            $checks[] = $c;
            $total += $c['weight'];
            if ($c['pass']) $score += $c['weight'];
        }

        // ========== 4. 社交/结构化检测 ==========
        $socialChecks = $this->checkSocialStructured();
        foreach ($socialChecks as $c) {
            $checks[] = $c;
            $total += $c['weight'];
            if ($c['pass']) $score += $c['weight'];
        }

        $finalScore = $total > 0 ? min(100, intval(($score / $total) * 100)) : 0;

        return [
            'score'       => $finalScore,
            'grade'       => $this->getGrade($finalScore),
            'checked_at'  => date('Y-m-d H:i:s'),
            'total_items' => count($checks),
            'passed'      => count(array_filter($checks, fn($c) => $c['pass'])),
            'failed'      => count(array_filter($checks, fn($c) => !$c['pass'])),
            'checks'      => $checks,
        ];
    }

    /**
     * 基础配置检测
     */
    protected function checkBasicConfig(): array
    {
        $checks = [];

        // 站点标题
        $siteTitle = Config::get('site.site_name', '');
        $checks[] = [
            'category' => '基础配置',
            'name'     => '站点标题已设置',
            'pass'     => !empty($siteTitle) && mb_strlen($siteTitle) <= 60,
            'weight'   => 5,
            'value'    => $siteTitle ?: '未设置',
            'suggest'  => empty($siteTitle) ? '请在系统设置中配置站点标题' : (mb_strlen($siteTitle) > 60 ? '标题长度建议控制在60字符以内' : ''),
        ];

        // 站点描述
        $siteDesc = Config::get('site.site_description', '');
        $checks[] = [
            'category' => '基础配置',
            'name'     => '站点描述已设置',
            'pass'     => !empty($siteDesc) && mb_strlen($siteDesc) <= 160,
            'weight'   => 5,
            'value'    => $siteDesc ? mb_substr($siteDesc, 0, 50) . '...' : '未设置',
            'suggest'  => empty($siteDesc) ? '请在系统设置中配置站点描述（SEO重要指标）' : (mb_strlen($siteDesc) > 160 ? '描述长度建议控制在160字符以内' : ''),
        ];

        // SEO关键词
        $siteKeywords = Config::get('site.site_keywords', '');
        $checks[] = [
            'category' => '基础配置',
            'name'     => '站点关键词已设置',
            'pass'     => !empty($siteKeywords),
            'weight'   => 3,
            'value'    => $siteKeywords ?: '未设置',
            'suggest'  => empty($siteKeywords) ? '建议配置站点核心关键词' : '',
        ];

        // Sitemap
        $sitemapPath = public_path() . 'sitemap.xml';
        $sitemapExists = file_exists($sitemapPath);
        $checks[] = [
            'category' => '基础配置',
            'name'     => 'Sitemap已生成',
            'pass'     => $sitemapExists,
            'weight'   => 5,
            'value'    => $sitemapExists ? ('已生成，更新于 ' . date('Y-m-d H:i', filemtime($sitemapPath))) : '未生成',
            'suggest'  => !$sitemapExists ? '请在SEO管理页面生成Sitemap' : '',
        ];

        // robots.txt
        $robotsPath = public_path() . 'robots.txt';
        $robotsExists = file_exists($robotsPath);
        $checks[] = [
            'category' => '基础配置',
            'name'     => 'robots.txt已配置',
            'pass'     => $robotsExists,
            'weight'   => 3,
            'value'    => $robotsExists ? '已配置' : '未配置',
            'suggest'  => !$robotsExists ? '请在SEO管理页面配置robots.txt' : '',
        ];

        return $checks;
    }

    /**
     * 内容质量检测
     */
    protected function checkContentQuality(): array
    {
        $checks = [];

        try {
            // 内容标题缺失
            $missingTitle = Db::name('content')
                ->where('status', 2)
                ->where(function ($query) {
                    $query->whereNull('title')->whereOr('title', '');
                })
                ->count();

            $totalContent = Db::name('content')->where('status', 2)->count();

            $checks[] = [
                'category' => '内容质量',
                'name'     => '内容标题无缺失',
                'pass'     => $missingTitle === 0,
                'weight'   => 8,
                'value'    => $missingTitle > 0 ? "{$missingTitle} 篇缺失" : '全部正常',
                'suggest'  => $missingTitle > 0 ? "建议为 {$missingTitle} 篇内容补充标题" : '',
            ];

            // 内容描述缺失
            $missingDesc = Db::name('content')
                ->where('status', 2)
                ->where(function ($query) {
                    $query->whereNull('seo_description')
                        ->whereOr('seo_description', '');
                })
                ->count();

            $checks[] = [
                'category' => '内容质量',
                'name'     => 'SEO描述覆盖率',
                'pass'     => $totalContent === 0 || ($missingDesc / $totalContent) < 0.3,
                'weight'   => 6,
                'value'    => $totalContent > 0 ? sprintf('%.1f%%', (1 - $missingDesc / $totalContent) * 100) : '无内容',
                'suggest'  => $totalContent > 0 && ($missingDesc / $totalContent) >= 0.3
                    ? "{$missingDesc} 篇内容缺少SEO描述，建议使用AI批量生成"
                    : '',
            ];

            // 重复标题检测
            $duplicates = Db::name('content')
                ->where('status', 2)
                ->where('title', '<>', '')
                ->field('title, COUNT(*) as count')
                ->group('title')
                ->having('count > 1')
                ->select();

            $dupCount = count($duplicates);
            $checks[] = [
                'category' => '内容质量',
                'name'     => '无重复标题',
                'pass'     => $dupCount === 0,
                'weight'   => 5,
                'value'    => $dupCount > 0 ? "发现 {$dupCount} 组重复标题" : '无重复',
                'suggest'  => $dupCount > 0 ? '重复标题会影响搜索引擎排名，建议修改' : '',
            ];

            // 图片ALT缺失统计（从内容表中扫描）
            $contentsWithImages = Db::name('content')
                ->where('status', 2)
                ->where('content', 'like', '%<img%')
                ->column('id, title, content');

            $totalImages = 0;
            $missingAlt  = 0;
            foreach ($contentsWithImages as $item) {
                $html = $item['content'] ?? '';
                preg_match_all('/<img[^>]*>/i', $html, $matches);
                foreach ($matches[0] as $imgTag) {
                    $totalImages++;
                    if (!preg_match('/alt\s*=\s*["\'][^"\']+["\']/i', $imgTag)) {
                        $missingAlt++;
                    }
                }
            }

            $checks[] = [
                'category' => '内容质量',
                'name'     => '图片ALT标签完整',
                'pass'     => $totalImages === 0 || ($missingAlt / $totalImages) < 0.3,
                'weight'   => 5,
                'value'    => $totalImages > 0 ? "{$totalImages}张图片，{$missingAlt}张缺失ALT" : '无图片',
                'suggest'  => $totalImages > 0 && ($missingAlt / $totalImages) >= 0.3
                    ? "建议为 {$missingAlt} 张图片添加ALT描述"
                    : '',
            ];

            // H1标签缺失（简化检测：内容中是否包含<h1>）
            $missingH1 = Db::name('content')
                ->where('status', 2)
                ->where(function ($query) {
                    $query->where('content', 'not like', '%<h1%')
                        ->whereOr('content', 'not like', '%<H1%');
                })
                ->count();

            $checks[] = [
                'category' => '内容质量',
                'name'     => '内容包含H1标签',
                'pass'     => $totalContent === 0 || ($missingH1 / $totalContent) < 0.5,
                'weight'   => 4,
                'value'    => $totalContent > 0 ? sprintf('%.1f%%包含H1', (1 - $missingH1 / $totalContent) * 100) : '无内容',
                'suggest'  => $totalContent > 0 && ($missingH1 / $totalContent) >= 0.5
                    ? '建议在内容模板中确保每篇文章包含H1标签'
                    : '',
            ];
        } catch (\Throwable $e) {
            // 数据库异常时返回友好提示
            $checks[] = [
                'category' => '内容质量',
                'name'     => '内容检测',
                'pass'     => false,
                'weight'   => 0,
                'value'    => '检测失败: ' . $e->getMessage(),
                'suggest'  => '请检查数据库连接',
            ];
        }

        return $checks;
    }

    /**
     * 技术SEO检测
     */
    protected function checkTechnicalSeo(): array
    {
        $checks = [];

        // HTTPS检测
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $checks[] = [
            'category' => '技术SEO',
            'name'     => 'HTTPS已启用',
            'pass'     => $isHttps,
            'weight'   => 5,
            'value'    => $isHttps ? '已启用' : '未启用',
            'suggest'  => !$isHttps ? '强烈建议启用HTTPS，Google已将HTTPS作为排名因素' : '',
        ];

        // 响应速度（模拟检测首页）
        $responseTime = $this->measureResponseTime();
        $checks[] = [
            'category' => '技术SEO',
            'name'     => '首页响应速度',
            'pass'     => $responseTime < 2000,
            'weight'   => 5,
            'value'    => $responseTime > 0 ? sprintf('%.0f ms', $responseTime) : '检测失败',
            'suggest'  => $responseTime >= 2000 ? '首页响应较慢，建议优化图片、启用缓存或CDN' : '',
        ];

        // 站点地图索引文件检测
        $sitemapIndexExists = file_exists(public_path() . 'sitemap.xml');
        $checks[] = [
            'category' => '技术SEO',
            'name'     => 'Sitemap可访问',
            'pass'     => $sitemapIndexExists,
            'weight'   => 3,
            'value'    => $sitemapIndexExists ? '可访问' : '不可访问',
            'suggest'  => !$sitemapIndexExists ? '请确保 /sitemap.xml 可通过HTTP访问' : '',
        ];

        return $checks;
    }

    /**
     * 社交/结构化数据检测
     */
    protected function checkSocialStructured(): array
    {
        $checks = [];

        // OG标签配置检测
        $ogEnabled = Config::get('seo.og_enabled', false);
        $checks[] = [
            'category' => '社交优化',
            'name'     => 'Open Graph标签',
            'pass'     => $ogEnabled,
            'weight'   => 3,
            'value'    => $ogEnabled ? '已启用' : '未启用',
            'suggest'  => !$ogEnabled ? '建议开启OG标签，优化社交媒体分享效果' : '',
        ];

        // Twitter Card检测
        $twitterCard = Config::get('seo.twitter_card', '');
        $checks[] = [
            'category' => '社交优化',
            'name'     => 'Twitter Card配置',
            'pass'     => !empty($twitterCard),
            'weight'   => 2,
            'value'    => $twitterCard ?: '未配置',
            'suggest'  => empty($twitterCard) ? '建议配置Twitter Card类型（summary/summary_large_image）' : '',
        ];

        return $checks;
    }

    /**
     * 测量首页响应时间
     */
    protected function measureResponseTime(): float
    {
        try {
            $url = (string) url('/', [], true, true);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_NOBODY         => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $start = microtime(true);
            curl_exec($ch);
            $time = (microtime(true) - $start) * 1000;
            curl_close($ch);
            return $time;
        } catch (\Throwable $e) {
            Log::warning('[SeoDiagnose] 响应时间测量失败: ' . $e->getMessage());
            return -1;
        }
    }

    /**
     * 根据分数获取评级
     */
    protected function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A（优秀）',
            $score >= 80 => 'B（良好）',
            $score >= 60 => 'C（合格）',
            $score >= 40 => 'D（需改进）',
            default      => 'F（紧急优化）',
        };
    }
}
