<?php
declare(strict_types=1);

namespace app\common\service\channel;

use app\common\model\ChannelPlatform;
use app\common\model\Content;
use think\facade\Http;
use think\facade\Cache;

class PlatformChannelService
{
    /**
     * CD-2: 发布内容到头条/知乎/微博
     */
    public function publish(int $contentId, int $platformAccountId): array
    {
        $account = ChannelPlatform::find($platformAccountId);
        if (!$account || !$account->status) return ['success' => false, 'message' => '平台账号未启用'];

        $token = $this->getAccessToken($platformAccountId);
        if (!$token) return ['success' => false, 'message' => '获取access_token失败，请先完成OAuth授权'];

        $adapted = $this->adaptContent($contentId, $account->platform_type);

        switch ($account->platform_type) {
            case 'toutiao':
                return $this->publishToToutiao($token, $adapted, $account);
            case 'zhihu':
                return $this->publishToZhihu($token, $adapted, $account);
            case 'weibo':
                return $this->publishToWeibo($token, $adapted, $account);
            default:
                return ['success' => false, 'message' => '不支持的平台类型'];
        }
    }

    public function adaptContent(int $contentId, string $platformType): array
    {
        $content = Content::find($contentId);
        switch ($platformType) {
            case 'toutiao':
                return ['title' => mb_substr($content->title, 0, 30), 'content' => $content->content, 'tags' => $this->extractTags($content), 'cover_image' => $content->cover_image ?? ''];
            case 'zhihu':
                return ['title' => $content->title, 'content' => $this->htmlToZhihuFormat($content->content), 'column' => ''];
            case 'weibo':
                $text = strip_tags($content->content);
                return ['text' => mb_substr($content->title . ' ' . $text, 0, 140), 'images' => [$content->cover_image ?? '']];
            default:
                return ['title' => $content->title, 'content' => $content->content];
        }
    }

    public function refreshToken(int $accountId): bool
    {
        $account = ChannelPlatform::find($accountId);
        if (!$account) return false;
        switch ($account->platform_type) {
            case 'toutiao': return $this->refreshToutiaoToken($account);
            case 'zhihu': return $this->refreshZhihuToken($account);
            case 'weibo': return $this->refreshWeiboToken($account);
            default: return false;
        }
    }

    private function getAccessToken(int $accountId): string
    {
        $account = ChannelPlatform::find($accountId);
        if (!$account) return '';
        $cacheKey = "platform_token_{$accountId}";
        $cached = Cache::get($cacheKey);
        if ($cached) return $cached;
        if (!empty($account->access_token) && $account->token_expire_time > time() + 300) {
            Cache::set($cacheKey, $account->access_token, $account->token_expire_time - time() - 300);
            return $account->access_token;
        }
        return $this->refreshToken($accountId) ? (Cache::get($cacheKey) ?: '') : '';
    }

    private function publishToToutiao(string $token, array $data, $account): array
    {
        $url = 'https://open.snssdk.com/auth/post/article/?access_token=' . $token;
        try {
            $response = Http::post($url, ['title' => $data['title'], 'content' => $data['content'], 'cover_image' => $data['cover_image'] ?? '', 'save_type' => 1]);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['data']['post_id'])) return ['success' => true, 'message' => '头条发布成功', 'post_id' => $result['data']['post_id']];
            return ['success' => false, 'message' => '头条发布失败: ' . ($result['message'] ?? '未知错误')];
        } catch (\Exception $e) { return ['success' => false, 'message' => '头条发布异常: ' . $e->getMessage()]; }
    }

    private function publishToZhihu(string $token, array $data, $account): array
    {
        $url = 'https://api.zhihu.com/articles';
        $postData = json_encode(['title' => $data['title'], 'content' => $data['content']], JSON_UNESCAPED_UNICODE);
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true]);
            $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            $result = json_decode($response, true);
            if ($httpCode === 200 && isset($result['id'])) return ['success' => true, 'message' => '知乎发布成功', 'article_id' => $result['id']];
            return ['success' => false, 'message' => '知乎发布失败: ' . ($result['error']['message'] ?? '未知错误')];
        } catch (\Exception $e) { return ['success' => false, 'message' => '知乎发布异常: ' . $e->getMessage()]; }
    }

    private function publishToWeibo(string $token, array $data, $account): array
    {
        $url = 'https://api.weibo.com/2/statuses/share.json';
        $postData = ['access_token' => $token, 'status' => $data['text']];
        if (!empty($data['images'][0])) {
            $picId = $this->uploadWeiboImage($token, $data['images'][0]);
            if ($picId) $postData['pic_id'] = $picId;
        }
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $postData, CURLOPT_RETURNTRANSFER => true]);
            $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            $result = json_decode($response, true);
            if ($httpCode === 200 && isset($result['id'])) return ['success' => true, 'message' => '微博发布成功', 'weibo_id' => $result['id']];
            return ['success' => false, 'message' => '微博发布失败: ' . ($result['error'] ?? '未知错误')];
        } catch (\Exception $e) { return ['success' => false, 'message' => '微博发布异常: ' . $e->getMessage()]; }
    }

    private function uploadWeiboImage(string $token, string $imageUrl): string
    {
        $tempPath = runtime_path() . 'temp_weibo_' . md5($imageUrl) . '.jpg';
        try {
            $imgData = @file_get_contents($imageUrl);
            if (!$imgData) return '';
            file_put_contents($tempPath, $imgData);
            $ch = curl_init('https://api.weibo.com/2/media/upload.json');
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => ['access_token' => $token, 'media' => new \CURLFile($tempPath, 'image/jpeg', 'image.jpg')], CURLOPT_RETURNTRANSFER => true]);
            $response = curl_exec($ch); curl_close($ch);
            $result = json_decode($response, true);
            return $result['media_id'] ?? '';
        } catch (\Exception $e) { return ''; } finally { if (file_exists($tempPath)) @unlink($tempPath); }
    }

    private function refreshToutiaoToken($account): bool
    {
        if (!$account->refresh_token) return false;
        try {
            $url = 'https://open.snssdk.com/auth/refresh_token/?refresh_token=' . $account->refresh_token . '&client_key=' . $account->app_id . '&client_secret=' . $account->app_secret;
            $response = Http::get($url);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['data']['access_token'])) {
                $account->access_token = $result['data']['access_token'];
                $account->token_expire_time = time() + (int)($result['data']['expires_in'] ?? 86400);
                $account->save();
                Cache::set("platform_token_{$account->id}", $result['data']['access_token'], (int)($result['data']['expires_in'] ?? 86400) - 300);
                return true;
            }
            return false;
        } catch (\Exception $e) { return false; }
    }

    private function refreshZhihuToken($account): bool
    {
        if (!$account->refresh_token) return false;
        try {
            $response = Http::post('https://api.zhihu.com/oauth/token', ['grant_type' => 'refresh_token', 'refresh_token' => $account->refresh_token, 'client_id' => $account->app_id, 'client_secret' => $account->app_secret]);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['access_token'])) {
                $account->access_token = $result['access_token'];
                $account->token_expire_time = time() + (int)($result['expires_in'] ?? 86400);
                $account->save();
                Cache::set("platform_token_{$account->id}", $result['access_token'], (int)($result['expires_in'] ?? 86400) - 300);
                return true;
            }
            return false;
        } catch (\Exception $e) { return false; }
    }

    private function refreshWeiboToken($account): bool
    {
        if (!$account->refresh_token) return false;
        try {
            $response = Http::post('https://api.weibo.com/oauth2/access_token', ['grant_type' => 'refresh_token', 'refresh_token' => $account->refresh_token, 'client_id' => $account->app_id, 'client_secret' => $account->app_secret]);
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['access_token'])) {
                $account->access_token = $result['access_token'];
                $account->token_expire_time = time() + (int)($result['expires_in'] ?? 86400);
                $account->save();
                Cache::set("platform_token_{$account->id}", $result['access_token'], (int)($result['expires_in'] ?? 86400) - 300);
                return true;
            }
            return false;
        } catch (\Exception $e) { return false; }
    }

    private function extractTags($content): array
    {
        $tags = [];
        if (!empty($content->seo_keywords)) $tags = array_slice(explode(',', $content->seo_keywords), 0, 5);
        return $tags;
    }

    private function htmlToZhihuFormat(string $html): string
    {
        return strip_tags($html, '<p><br><strong><em><h2><h3><ul><ol><li><blockquote><a><img>');
    }
}
