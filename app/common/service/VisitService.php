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
            // V2.9.9: session_id优先从请求传入，否则生成新会话ID
            $sessionId = !empty($data['session_id'])
                ? substr($data['session_id'], 0, 64)
                : self::generateSessionId();

            VisitLog::create([
                'content_id'   => (int) ($data['content_id'] ?? 0),
                'visitor_id'   => (int) ($data['visitor_id'] ?? 0),
                'session_id'   => $sessionId,
                'ip'           => substr($data['ip'] ?? '', 0, 45),
                'ua'           => substr($data['ua'] ?? '', 0, 500),
                'page_url'     => substr($data['page_url'] ?? '', 0, 500),
                'referrer'     => substr($data['referrer'] ?? '', 0, 500),
                'source_type'  => self::detectSource($data['referrer'] ?? ''),
                'event_type'   => substr($data['event_type'] ?? 'visit', 0, 20),
                'share_channel'=> substr($data['share_channel'] ?? '', 0, 20),
                'visit_time'   => time(),
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
     * V2.9.9: 检测来源类型（详细引擎/平台名）
     */
    protected static function detectSource(string $referrer): string
    {
        if (empty($referrer)) return 'direct';
        $host = parse_url($referrer, PHP_URL_HOST) ?: '';

        // 搜索引擎
        if (str_contains($host, 'baidu.com')) return 'baidu';
        if (str_contains($host, 'google.')) return 'google';
        if (str_contains($host, 'bing.com')) return 'bing';
        if (str_contains($host, 'sogou.com')) return 'sogou';
        if (str_contains($host, 'so.com')) return '360';
        if (str_contains($host, 'sm.cn')) return 'shenma';

        // 社交平台（返回具体平台，由detectSourceCategory归入social大类）
        if (str_contains($host, 'weixin.qq.com')) return 'wechat';
        if (str_contains($host, 'zhihu.com')) return 'zhihu';
        if (str_contains($host, 'weibo.com')) return 'weibo';
        if (str_contains($host, 'douyin.com')) return 'douyin';

        return 'referral';
    }

    /**
     * V2.9.9 B-2: 来源大类分类（search/social/referral/direct/other）
     */
    public static function detectSourceCategory(string $referrer): string
    {
        $source = self::detectSource($referrer);
        $searchEngines = ['baidu', 'google', 'bing', 'sogou', '360', 'shenma'];
        $socialPlatforms = ['wechat', 'weibo', 'douyin'];

        if ($source === 'direct') return 'direct';
        if (in_array($source, $searchEngines, true)) return 'search';
        if (in_array($source, $socialPlatforms, true)) return 'social';
        if ($source === 'zhihu') return 'referral'; // 知乎暂归入referral，团队待定
        return 'referral';
    }

    /**
     * V2.9.9 B-2: 生成会话ID（用于跳出率计算）
     */
    public static function generateSessionId(): string
    {
        return md5(uniqid((string) mt_rand(), true) . microtime(true));
    }
}
