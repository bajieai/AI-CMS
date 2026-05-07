<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\VisitLog;
use think\facade\Log;

/**
 * PV统计服务 - V2.7 P0-6
 * 停用缓存PV计数，改用DB持久化+JS打点
 */
class VisitService
{
    /**
     * 记录PV访问
     * @param array $data ['content_id'=>0, 'visitor_id'=>0, 'ip'=>'', 'ua'=>'', 'page_url'=>'', 'referrer'=>'']
     */
    public static function track(array $data): bool
    {
        try {
            VisitLog::create([
                'content_id'  => (int) ($data['content_id'] ?? 0),
                'visitor_id'  => (int) ($data['visitor_id'] ?? 0),
                'ip'          => substr($data['ip'] ?? '', 0, 45),
                'ua'          => substr($data['ua'] ?? '', 0, 500),
                'page_url'    => substr($data['page_url'] ?? '', 0, 500),
                'referrer'    => substr($data['referrer'] ?? '', 0, 500),
                'source_type' => self::detectSource($data['referrer'] ?? ''),
                'visit_time'  => time(),
            ]);

            // 异步更新内容表views（低优先级，失败不影响主流程）
            $contentId = (int) ($data['content_id'] ?? 0);
            if ($contentId > 0) {
                try {
                    Content::where('id', $contentId)->inc('views')->update();
                } catch (\Throwable $e) {
                    Log::warning("PV更新内容views失败: " . $e->getMessage());
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("PV记录失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取内容PV数（近N天）
     */
    public static function getContentPv(int $contentId, int $days = 30): int
    {
        $startTime = time() - $days * 86400;
        return VisitLog::where('content_id', $contentId)
            ->where('visit_time', '>=', $startTime)
            ->count();
    }

    /**
     * 获取热门内容（按PV排序）
     */
    public static function getHotContents(int $limit = 10, int $days = 7): array
    {
        $startTime = time() - $days * 86400;
        return VisitLog::field('content_id, COUNT(*) as pv')
            ->where('content_id', '>', 0)
            ->where('visit_time', '>=', $startTime)
            ->group('content_id')
            ->order('pv', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 检测来源类型
     */
    protected static function detectSource(string $referrer): string
    {
        if (empty($referrer)) return 'direct';
        $host = parse_url($referrer, PHP_URL_HOST) ?: '';
        if (str_contains($host, 'baidu.com')) return 'baidu';
        if (str_contains($host, 'google.')) return 'google';
        if (str_contains($host, 'bing.com')) return 'bing';
        if (str_contains($host, 'weixin.qq.com')) return 'wechat';
        if (str_contains($host, 'zhihu.com')) return 'zhihu';
        return 'referral';
    }
}
