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

namespace app\common\service\storage;

use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 腾讯云COS存储驱动 - V2.6
 * 支持通过GuzzleHttp直传（无需安装官方SDK）
 */
class TencentCosDriver implements StorageDriverInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function upload(string $localPath, string $savePath, array $options = []): array
    {
        if (!$this->validateConfig($this->config)) {
            return ['success' => false, 'url' => '', 'path' => '', 'error' => '腾讯云COS配置不完整'];
        }

        $bucket = $this->config['bucket'];
        $region = $this->config['region']; // 如 ap-guangzhou
        $secretId = $this->config['secret_id'];
        $secretKey = $this->config['secret_key'];
        $cdnDomain = $this->config['cdn_domain'] ?? '';

        $objectKey = ltrim($savePath, '/');
        $contentType = $options['content_type'] ?? 'application/octet-stream';

        // COS使用签名URL方式（临时密钥签名较复杂，这里使用永久密钥的简化签名）
        $host = "{$bucket}.cos.{$region}.myqcloud.com";
        $date = gmdate('D, d M Y H:i:s \G\M\T');

        // 构造COS签名（简化版，基于Key的签名）
        $signTime = time() . ';' . (time() + 3600);
        $keyTime = $signTime;
        $signKey = hash_hmac('sha1', $keyTime, $secretKey);
        $httpString = "put\n/{$objectKey}\n\nhost={$host}\n";
        $stringToSign = "sha1\n{$signTime}\n" . sha1($httpString) . "\n";
        $signature = hash_hmac('sha1', $stringToSign, $signKey);
        $authorization = "q-sign-algorithm=sha1&q-ak={$secretId}&q-sign-time={$signTime}&q-key-time={$keyTime}&q-header-list=host&q-url-param-list=&q-signature={$signature}";

        $client = new Client(['timeout' => 60]);
        $url = "https://{$host}/{$objectKey}";

        try {
            $client->put($url, [
                'body' => fopen($localPath, 'r'),
                'headers' => [
                    'Host' => $host,
                    'Date' => $date,
                    'Content-Type' => $contentType,
                    'Authorization' => $authorization,
                ],
            ]);

            $publicUrl = $cdnDomain ? "https://{$cdnDomain}/{$objectKey}" : "https://{$host}/{$objectKey}";
            return ['success' => true, 'url' => $publicUrl, 'path' => $objectKey, 'error' => ''];
        } catch (\Exception $e) {
            Log::error('腾讯云COS上传失败: ' . $e->getMessage());
            return ['success' => false, 'url' => '', 'path' => '', 'error' => 'COS上传失败: ' . $e->getMessage()];
        }
    }

    public function delete(string $path): bool
    {
        if (!$this->validateConfig($this->config)) return false;

        $bucket = $this->config['bucket'];
        $region = $this->config['region'];
        $secretId = $this->config['secret_id'];
        $secretKey = $this->config['secret_key'];
        $objectKey = ltrim($path, '/');

        $host = "{$bucket}.cos.{$region}.myqcloud.com";
        $signTime = time() . ';' . (time() + 3600);
        $keyTime = $signTime;
        $signKey = hash_hmac('sha1', $keyTime, $secretKey);
        $httpString = "delete\n/{$objectKey}\n\nhost={$host}\n";
        $stringToSign = "sha1\n{$signTime}\n" . sha1($httpString) . "\n";
        $signature = hash_hmac('sha1', $stringToSign, $signKey);
        $authorization = "q-sign-algorithm=sha1&q-ak={$secretId}&q-sign-time={$signTime}&q-key-time={$keyTime}&q-header-list=host&q-url-param-list=&q-signature={$signature}";

        $client = new Client(['timeout' => 30]);
        $url = "https://{$host}/{$objectKey}";

        try {
            $client->delete($url, [
                'headers' => [
                    'Host' => $host,
                    'Authorization' => $authorization,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('腾讯云COS删除失败: ' . $e->getMessage());
            return false;
        }
    }

    public function getUrl(string $path): string
    {
        $cdnDomain = $this->config['cdn_domain'] ?? '';
        $bucket = $this->config['bucket'];
        $region = $this->config['region'];
        $objectKey = ltrim($path, '/');

        if ($cdnDomain) {
            return "https://{$cdnDomain}/{$objectKey}";
        }
        return "https://{$bucket}.cos.{$region}.myqcloud.com/{$objectKey}";
    }

    public function exists(string $path): bool
    {
        if (!$this->validateConfig($this->config)) return false;

        $bucket = $this->config['bucket'];
        $region = $this->config['region'];
        $objectKey = ltrim($path, '/');

        $client = new Client(['timeout' => 10]);
        $url = "https://{$bucket}.cos.{$region}.myqcloud.com/{$objectKey}";

        try {
            $response = $client->head($url, ['http_errors' => false]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'cos';
    }

    public function getDisplayName(): string
    {
        return '腾讯云COS';
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'secret_id', 'label' => 'SecretId', 'type' => 'text', 'required' => true],
            ['name' => 'secret_key', 'label' => 'SecretKey', 'type' => 'password', 'required' => true],
            ['name' => 'bucket', 'label' => 'Bucket名称', 'type' => 'text', 'required' => true],
            ['name' => 'region', 'label' => 'Region(地域)', 'type' => 'text', 'required' => true, 'placeholder' => '如: ap-guangzhou'],
            ['name' => 'cdn_domain', 'label' => 'CDN加速域名(可选)', 'type' => 'text', 'required' => false, 'placeholder' => '如: cdn.example.com'],
        ];
    }

    public function validateConfig(array $config): bool
    {
        return !empty($config['secret_id'])
            && !empty($config['secret_key'])
            && !empty($config['bucket'])
            && !empty($config['region']);
    }
}
