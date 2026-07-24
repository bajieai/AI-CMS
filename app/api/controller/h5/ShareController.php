<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\facade\Cache;
use think\response\Json;

/**
 * H5分享统计控制器 - V2.9.40
 * 记录分享行为并获取内容分享统计
 */
class ShareController extends H5BaseController
{
    /**
     * POST记录分享行为
     * 参数：content_id, platform(渠道), member_id
     */
    public function log(): Json
    {
        $contentId = (int)$this->request->param('content_id', 0);
        $platform = $this->request->param('platform', $this->request->param('channel', 'other'));
        if ($contentId <= 0) {
            return $this->error('内容ID不能为空');
        }
        $allowedPlatforms = ['wechat', 'weibo', 'qq', 'twitter', 'facebook', 'copy', 'other'];
        if (!in_array($platform, $allowedPlatforms)) {
            $platform = 'other';
        }
        $memberId = $this->memberId > 0 ? $this->memberId : (int)$this->request->param('member_id', 0);
        // 写入分享日志表
        Db::name('share_log')->insert([
            'content_id' => $contentId,
            'channel'    => $platform,
            'member_id'  => $memberId,
            'ip'         => $this->request->ip(),
            'referer'    => $this->request->header('referer', ''),
            'created_at' => time(),
        ]);
        // 清除缓存
        Cache::clear();
        return $this->success(['recorded' => true], '分享已记录');
    }

    /**
     * GET获取内容分享统计
     */
    public function stats(): Json
    {
        $contentId = (int)$this->request->route('contentId', 0);
        if ($contentId <= 0) {
            $contentId = (int)$this->request->param('contentId', 0);
        }
        if ($contentId <= 0) {
            return $this->error('内容ID不能为空');
        }
        $cacheKey = 'h5_share_stats_' . $contentId;
        $result = Cache::remember($cacheKey, function () use ($contentId) {
            $total = Db::name('share_log')->where('content_id', $contentId)->count();
            $byChannel = Db::name('share_log')
                ->where('content_id', $contentId)
                ->field('channel, COUNT(*) as count')
                ->group('channel')
                ->order('count', 'desc')
                ->select()
                ->toArray();
            // 最近7天趋势
            $sevenDaysAgo = time() - 7 * 86400;
            $trend = Db::name('share_log')
                ->where('content_id', $contentId)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->field('FROM_UNIXTIME(created_at, "%Y-%m-%d") as date, COUNT(*) as count')
                ->group('date')
                ->order('date', 'asc')
                ->select()
                ->toArray();
            return [
                'total'      => $total,
                'by_channel' => $byChannel,
                'trend_7d'   => $trend,
            ];
        }, 300);
        return $this->success($result);
    }
}
