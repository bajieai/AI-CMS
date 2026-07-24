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

namespace app\common\command;

use app\common\model\Content;
use app\common\model\SeoDeadlinks;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class SeoCheckDeadlinks extends Command
{
    protected function configure()
    {
        $this->setName('seo:check-deadlinks')
            ->setDescription('检测SEO死链');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('死链检测开始...');

        $contents = Content::where('status', 2)->field('id,title,content')->select();
        $checked = 0;
        $deadCount = 0;

        foreach ($contents as $content) {
            $urls = $this->extractUrls($content->content);
            foreach ($urls as $url) {
                $statusCode = $this->checkUrl($url);
                if ($statusCode >= 400 || $statusCode === 0) {
                    SeoDeadlinks::create([
                        'url'        => $url,
                        'status_code'=> $statusCode,
                        'source'     => '/content/' . $content->id,
                        'check_time' => time(),
                        'is_fixed'   => 0,
                    ]);
                    $deadCount++;
                    $output->writeln("  [死链] {$url} (HTTP {$statusCode}) 来源:内容#{$content->id}");
                }
                $checked++;
            }
        }

        // 清理30天前的已修复记录
        SeoDeadlinks::where('is_fixed', 1)
            ->where('check_time', '<', time() - 86400 * 30)
            ->delete();

        $output->writeln("检测完成: 共检查{$checked}个链接，发现{$deadCount}个死链");
        return 0;
    }

    /**
     * 从HTML内容中提取链接
     */
    protected function extractUrls(?string $html): array
    {
        if (empty($html)) {
            return [];
        }
        preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * 检查URL可访问性
     */
    protected function checkUrl(string $url): int
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_NOBODY         => true,
                CURLOPT_USERAGENT      => 'AI-CMS-SEOBot/2.3',
            ]);
            curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $statusCode;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}