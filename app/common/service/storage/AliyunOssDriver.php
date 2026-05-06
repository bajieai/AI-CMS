<?php
declare(strict_types=1);

namespace app\common\service\storage;

use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 阿里云OSS存储驱动 - V2.6
 * 支持通过GuzzleHttp直传（无需安装官方SDK）
 */
class AliyunOssDriver implements StorageDriverInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function upload(string $localPath, string $savePath, array $options = []): array
    {
        if (!$this->validateConfig($this->config)) {
            return ['success' => false, 'url' => '', 'path' => '', 'error' => '阿里云OSS配置不完整'];
        }

        $bucket = $this->config['bucket'];
        $endpoint = $this->config['endpoint']; // 如 oss-cn-hangzhou.aliyuncs.com
        $accessKeyId = $this->config['access_key_id'];
        $accessKeySecret = $this->config['access_key_secret'];
        $cdnDomain = $this->config['cdn_domain'] ?? '';

        $objectKey = ltrim($savePath, '/');
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $contentType = $options['content_type'] ?? 'application/octet-stream';
        $contentMd5 = base64_encode(md5_file($localPath, true));

        // 构造签名
        $canonicalResource = '/' . $bucket . '/' . $objectKey;
        $signStr = "PUT\n{$contentMd5}\n{$contentType}\n{$date}\n" . $canonicalResource;
        $signature = base64_encode(hash_hmac('sha1', $signStr, $accessKeySecret, true));
        $authorization = "OSS {$accessKeyId}:{$signature}";

        $client = new Client(['timeout' => 60]);
        $url = "https://{$bucket}.{$endpoint}/{$objectKey}";

        try {
            $client->put($url, [
                'body' => fopen($localPath, 'r'),
                'headers' => [
                    'Date' => $date,
                    'Content-Type' => $contentType,
                    'Content-MD5' => $contentMd5,
                    'Authorization' => $authorization,
                ],
            ]);

            $publicUrl = $cdnDomain ? "https://{$cdnDomain}/{$objectKey}" : "https://{$bucket}.{$endpoint}/{$objectKey}";
            return ['success' => true, 'url' => $publicUrl, 'path' => $objectKey, 'error' => ''];
        } catch (\Exception $e) {
            Log::error('阿里云OSS上传失败: ' . $e->getMessage());
            return ['success' => false, 'url' => '', 'path' => '', 'error' => 'OSS上传失败: ' . $e->getMessage()];
        }
    }

    public function delete(string $path): bool
    {
        if (!$this->validateConfig($this->config)) return false;

        $bucket = $this->config['bucket'];
        $endpoint = $this->config['endpoint'];
        $accessKeyId = $this->config['access_key_id'];
        $accessKeySecret = $this->config['access_key_secret'];
        $objectKey = ltrim($path, '/');

        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $canonicalResource = '/' . $bucket . '/' . $objectKey;
        $signStr = "DELETE\n\n\n{$date}\n" . $canonicalResource;
        $signature = base64_encode(hash_hmac('sha1', $signStr, $accessKeySecret, true));
        $authorization = "OSS {$accessKeyId}:{$signature}";

        $client = new Client(['timeout' => 30]);
        $url = "https://{$bucket}.{$endpoint}/{$objectKey}";

        try {
            $client->delete($url, [
                'headers' => [
                    'Date' => $date,
                    'Authorization' => $authorization,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('阿里云OSS删除失败: ' . $e->getMessage());
            return false;
        }
    }

    public function getUrl(string $path): string
    {
        $cdnDomain = $this->config['cdn_domain'] ?? '';
        $bucket = $this->config['bucket'];
        $endpoint = $this->config['endpoint'];
        $objectKey = ltrim($path, '/');

        if ($cdnDomain) {
            return "https://{$cdnDomain}/{$objectKey}";
        }
        return "https://{$bucket}.{$endpoint}/{$objectKey}";
    }

    public function exists(string $path): bool
    {
        if (!$this->validateConfig($this->config)) return false;

        $bucket = $this->config['bucket'];
        $endpoint = $this->config['endpoint'];
        $objectKey = ltrim($path, '/');

        $client = new Client(['timeout' => 10]);
        $url = "https://{$bucket}.{$endpoint}/{$objectKey}";

        try {
            $response = $client->head($url, ['http_errors' => false]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getName(): string
    {
        return 'oss';
    }

    public function getDisplayName(): string
    {
        return '阿里云OSS';
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'access_key_id', 'label' => 'AccessKey ID', 'type' => 'text', 'required' => true],
            ['name' => 'access_key_secret', 'label' => 'AccessKey Secret', 'type' => 'password', 'required' => true],
            ['name' => 'bucket', 'label' => 'Bucket名称', 'type' => 'text', 'required' => true],
            ['name' => 'endpoint', 'label' => 'Endpoint(地域节点)', 'type' => 'text', 'required' => true, 'placeholder' => '如: oss-cn-hangzhou.aliyuncs.com'],
            ['name' => 'cdn_domain', 'label' => 'CDN加速域名(可选)', 'type' => 'text', 'required' => false, 'placeholder' => '如: cdn.example.com'],
        ];
    }

    public function validateConfig(array $config): bool
    {
        return !empty($config['access_key_id'])
            && !empty($config['access_key_secret'])
            && !empty($config['bucket'])
            && !empty($config['endpoint']);
    }
}
