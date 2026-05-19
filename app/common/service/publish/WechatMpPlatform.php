<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\publish;

use app\common\model\Content;
use app\common\model\PublishPlatform;
use app\common\service\CacheService;
use app\common\service\ConfigService;
use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 微信公众号发布适配器 - V2.5
 */
class WechatMpPlatform implements PublishPlatformInterface
{
    public function getName(): string
    {
        return 'wechat_mp';
    }

    public function getDisplayName(): string
    {
        return '微信公众号';
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'appid', 'label' => 'AppID', 'type' => 'text', 'required' => true],
            ['name' => 'secret', 'label' => 'AppSecret', 'type' => 'password', 'required' => true],
        ];
    }

    public function validateConfig(PublishPlatform $platform): bool
    {
        $config = $platform->config_json;
        return !empty($config['appid']) && !empty($config['secret']);
    }

    public function publish(Content $content, PublishPlatform $platform): array
    {
        if (!$this->validateConfig($platform)) {
            throw new \Exception('公众号AppID/Secret未配置');
        }

        $config = $platform->config_json;
        $appid = $config['appid'];
        $secret = $config['secret'];

        $accessToken = $this->getAccessToken($appid, $secret);

        $client = new Client(['timeout' => 30]);

        $article = [
            'title' => $content->title,
            'author' => $content->author ?? '',
            'digest' => mb_substr(strip_tags($content->content), 0, 120),
            'content' => $this->formatContent($content->content),
            'content_source_url' => ConfigService::get('site_url', '') . '/detail/' . $content->id,
            'need_open_comment' => 1,
            'only_fans_can_comment' => 0,
        ];

        $response = $client->post("https://api.weixin.qq.com/cgi-bin/draft/add?access_token={$accessToken}", [
            'json' => ['articles' => [$article]],
        ]);

        $result = json_decode((string) $response->getBody(), true);

        if (isset($result['errcode']) && $result['errcode'] !== 0) {
            Log::error('公众号发布失败: ' . ($result['errmsg'] ?? '未知错误'));
            throw new \Exception('公众号发布失败: ' . ($result['errmsg'] ?? '未知错误'));
        }

        return ['media_id' => $result['media_id'] ?? ''];
    }

    /**
     * 获取AccessToken（带缓存）
     */
    protected function getAccessToken(string $appid, string $secret): string
    {
        $cacheKey = "wechat_mp_access_token_{$appid}";

        return CacheService::remember($cacheKey, function () use ($appid, $secret) {
            $client = new Client(['timeout' => 10]);
            $response = $client->get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}");
            $result = json_decode((string) $response->getBody(), true);

            if (isset($result['errcode'])) {
                throw new \Exception('获取AccessToken失败: ' . ($result['errmsg'] ?? ''));
            }

            return $result['access_token'] ?? '';
        }, 7000);
    }

    /**
     * V2.9.3 M28: 格式化内容为微信公众号格式（增强版）
     */
    protected function formatContent(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 1. 段落样式
        $html = preg_replace('/<h([1-6])>/i', '<h$1 style="font-weight:bold;margin:1em 0 0.5em;">', $html);
        $html = preg_replace('/<p>/i', '<p style="margin:0.5em 0;line-height:1.8;">', $html);

        // 2. 图片自适应（保留src，增加样式）
        $html = preg_replace('/<img([^>]*)src="([^"]*)"([^>]*)>/i', '<img$1src="$2"$3 style="max-width:100%;height:auto;display:block;margin:0.5em 0;">', $html);

        // 3. 视频标签转公众号视频卡片（简化处理：保留视频封面链接提示）
        $html = preg_replace('/<video[^>]*src="([^"]*)"[^>]*>.*?<\/video>/i', '<p style="color:#999;font-size:14px;">[视频内容] 请前往原文查看视频</p>', $html);

        // 4. 表格转简单段落（公众号对table支持有限）
        $html = preg_replace('/<table[^>]*>.*?<\/table>/is', '<p style="color:#999;">[表格内容] 请前往原文查看</p>', $html);

        // 5. 清理不兼容标签
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><b><em><i><u><s><blockquote><pre><code><ul><ol><li><a><img><span><div><section>';
        $html = strip_tags($html, $allowedTags);

        return $html;
    }
}
