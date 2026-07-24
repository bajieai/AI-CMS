<?php
declare(strict_types=1);

namespace app\common\service\channel;

use app\common\model\ChannelWechat;
use app\common\model\Content;
use think\facade\Cache;
use think\facade\Http;

class WeChatChannelService
{
    private const API_BASE = 'https://api.weixin.qq.com/cgi-bin';
    private const TOKEN_CACHE_PREFIX = 'wechat_token_';

    /**
     * CD-1: 发布内容到微信公众号
     */
    public function publish(int $contentId, int $wechatAccountId): array
    {
        $account = ChannelWechat::find($wechatAccountId);
        if (!$account || !$account->status) return ['success' => false, 'message' => '公众号未启用'];
        $content = Content::find($contentId);
        if (!$content) return ['success' => false, 'message' => '内容不存在'];

        $adapted = $this->adaptContent($contentId);
        $token = $this->getAccessToken($wechatAccountId);
        if (!$token) return ['success' => false, 'message' => '获取access_token失败，请检查AppID和AppSecret配置'];

        // 1. 上传封面图片为永久素材
        $mediaId = '';
        if (!empty($adapted['cover_image'])) {
            $mediaId = $this->uploadMaterial($token, $adapted['cover_image'], $account);
        }

        // 2. 创建图文素材并发布
        $articleData = [
            'articles' => [[
                'title' => $adapted['title'],
                'content' => $adapted['content'],
                'digest' => $adapted['digest'],
                'thumb_media_id' => $mediaId,
                'need_open_comment' => 1,
                'only_fans_can_comment' => 0,
            ]]
        ];

        $response = Http::post(self::API_BASE . '/draft/add?access_token=' . $token, json_encode($articleData, JSON_UNESCAPED_UNICODE));
        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['media_id'])) {
            return ['success' => true, 'message' => '发布成功', 'media_id' => $result['media_id']];
        }

        return ['success' => false, 'message' => '发布失败: ' . ($result['errmsg'] ?? '未知错误'), 'errcode' => $result['errcode'] ?? 0];
    }

    /**
     * 获取access_token（带缓存）
     */
    public function getAccessToken(int $accountId): string
    {
        $account = ChannelWechat::find($accountId);
        if (!$account) return '';

        $cacheKey = self::TOKEN_CACHE_PREFIX . $accountId;
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;

        // 检查数据库中是否有有效token
        if (!empty($account->access_token) && $account->token_expire_time > time() + 300) {
            Cache::set($cacheKey, $account->access_token, $account->token_expire_time - time() - 300);
            return $account->access_token;
        }

        return $this->refreshToken($accountId) ? (Cache::get($cacheKey) ?: '') : '';
    }

    /**
     * 内容适配：HTML转公众号富文本格式
     */
    public function adaptContent(int $contentId): array
    {
        $content = Content::find($contentId);
        return [
            'title' => mb_substr($content->title, 0, 64),
            'content' => $this->htmlToWechatFormat($content->content ?? ''),
            'digest' => mb_substr(strip_tags($content->summary ?: $content->content), 0, 120),
            'cover_image' => $content->cover_image ?? '',
        ];
    }

    /**
     * 刷新access_token（调用微信API）
     */
    public function refreshToken(int $accountId): bool
    {
        $account = ChannelWechat::find($accountId);
        if (!$account || !$account->app_id || !$account->app_secret) return false;

        $url = self::API_BASE . "/token?grant_type=client_credential&appid={$account->app_id}&secret={$account->app_secret}";
        try {
            $response = Http::get($url);
            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['access_token'])) {
                $account->access_token = $result['access_token'];
                $account->token_expire_time = time() + (int)($result['expires_in'] ?? 7200);
                $account->save();
                Cache::set(self::TOKEN_CACHE_PREFIX . $accountId, $result['access_token'], (int)($result['expires_in'] ?? 7200) - 300);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 上传永久素材（封面图）
     */
    private function uploadMaterial(string $token, string $imageUrl, $account): string
    {
        // 下载图片到临时文件
        $tempPath = runtime_path() . 'temp_' . md5($imageUrl) . '.jpg';
        try {
            $imgData = @file_get_contents($imageUrl);
            if (!$imgData) return '';
            file_put_contents($tempPath, $imgData);

            // 调用微信上传素材API
            $url = self::API_BASE . '/material/add_material?access_token=' . $token . '&type=image';
            $postFields = [
                'media' => new \CURLFile($tempPath, 'image/jpeg', 'cover.jpg'),
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            return $result['media_id'] ?? '';
        } catch (\Exception $e) {
            return '';
        } finally {
            if (file_exists($tempPath)) @unlink($tempPath);
        }
    }

    /**
     * HTML转微信公众号格式（过滤不支持的标签）
     */
    private function htmlToWechatFormat(string $html): string
    {
        // 移除不支持的标签
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $html);
        $html = preg_replace('/<form[^>]*>.*?<\/form>/is', '', $html);
        // 转换外部链接为文本
        $html = preg_replace('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', '$2', $html);
        return $html;
    }
}
