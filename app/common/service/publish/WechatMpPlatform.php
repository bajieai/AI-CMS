<?php
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
     * 格式化内容为微信公众号格式
     */
    protected function formatContent(string $html): string
    {
        $html = preg_replace('/<h([1-6])>/', '<h$1 style="font-weight:bold;margin:1em 0 0.5em;">', $html);
        $html = preg_replace('/<p>/', '<p style="margin:0.5em 0;line-height:1.8;">', $html);
        $html = preg_replace('/<img/', '<img style="max-width:100%;height:auto;"', $html);
        return $html;
    }
}
