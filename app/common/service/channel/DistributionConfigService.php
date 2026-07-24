<?php
declare(strict_types=1);

namespace app\common\service\channel;

use app\common\model\ChannelWechat;
use app\common\model\ChannelPlatform;
use app\common\model\Content;
use think\facade\Db;
use think\facade\Cache;

class DistributionConfigService
{
    private const CACHE_TAG = 'dist_config';

    public function getChannels(): array
    {
        $wechat = ChannelWechat::select()->toArray();
        $platforms = ChannelPlatform::select()->toArray();
        return ['wechat' => $wechat, 'platforms' => $platforms];
    }

    public function testChannel(int $channelId, string $type): array
    {
        if ($type === 'wechat') {
            $service = new WeChatChannelService();
            $token = $service->getAccessToken($channelId);
            return ['success' => !empty($token), 'message' => !empty($token) ? '连接正常' : '连接失败'];
        }
        $service = new PlatformChannelService();
        $ok = $service->refreshToken($channelId);
        return ['success' => $ok, 'message' => $ok ? '连接正常' : '连接失败'];
    }

    public function getTemplates(): array
    {
        return Cache::remember('templates', function() {
            return Db::name('distribution_template')->order('id', 'desc')->select()->toArray();
        }, 3600);
    }

    public function saveTemplate(array $data): array
    {
        $id = $data['id'] ?? 0;
        $data['update_time'] = time();
        if ($id > 0) { Db::name('distribution_template')->where('id', $id)->update($data); }
        else { $data['create_time'] = time(); $id = Db::name('distribution_template')->insertGetId($data); }
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function renderTemplate(int $templateId, int $contentId): string
    {
        $template = Db::name('distribution_template')->find($templateId);
        $content = Content::find($contentId);
        if (!$template || !$content) return '';
        $body = $template['body'] ?? '';
        $body = str_replace(['{title}', '{summary}', '{content}', '{url}'], [$content->title, $content->summary, $content->content, '/content/' . $content->id], $body);
        return $body;
    }

    public function getStrategies(): array
    {
        return Cache::remember('strategies', function() {
            return Db::name('distribution_strategy')->where('status', 1)->order('sort', 'asc')->select()->toArray();
        }, 3600);
    }

    public function saveStrategy(array $data): array
    {
        $id = $data['id'] ?? 0;
        $data['update_time'] = time();
        if ($id > 0) { Db::name('distribution_strategy')->where('id', $id)->update($data); }
        else { $data['create_time'] = time(); $id = Db::name('distribution_strategy')->insertGetId($data); }
        Cache::clear();
        return ['success' => true, 'id' => $id];
    }

    public function getStats(): array
    {
        return Cache::remember('stats', function() {
            $logService = new DistributionLogService();
            return $logService->getEffectOverview();
        }, 300);
    }
}
