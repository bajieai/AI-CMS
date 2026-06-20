<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\PluginPackage;
use app\common\model\PluginDownloadLog;

/**
 * V2.9.25 L-4/L-8: 插件安全校验 + 日志服务
 */
class PluginSecurityService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = env('plugin.sign_secret', config('app.app_key', 'default-secret'));
    }

    public function generateSignature(string $filePath): string
    {
        if (!file_exists($filePath)) return '';
        return hash_hmac('sha256', file_get_contents($filePath), $this->secretKey);
    }

    public function verifySignature(string $filePath, string $signature): bool
    {
        if (empty($signature)) return false;
        return hash_equals($this->generateSignature($filePath), $signature);
    }

    public function computeFileHash(string $filePath): string
    {
        if (!file_exists($filePath)) return '';
        return hash_file('sha256', $filePath);
    }

    public function verifyFileHash(string $filePath, string $expectedHash): bool
    {
        if (empty($expectedHash)) return false;
        return hash_equals(strtolower($expectedHash), strtolower($this->computeFileHash($filePath)));
    }

    public function verifyPackage(string $filePath, ?PluginPackage $package): array
    {
        if (!file_exists($filePath)) return ['success' => false, 'msg' => '文件不存在'];
        if ($package && !empty($package->file_hash) && !$this->verifyFileHash($filePath, $package->file_hash)) {
            return ['success' => false, 'msg' => '文件哈希校验失败'];
        }
        if ($package && !empty($package->signature) && !$this->verifySignature($filePath, $package->signature)) {
            return ['success' => false, 'msg' => '签名验证失败'];
        }
        return ['success' => true, 'msg' => '安全校验通过'];
    }

    public function logDownload(int $pluginId, string $version, array $extra = []): void
    {
        PluginDownloadLog::create([
            'plugin_id' => $pluginId,
            'version' => $version,
            'user_id' => $extra['user_id'] ?? 0,
            'ip' => $extra['ip'] ?? request()->ip(),
            'source' => $extra['source'] ?? 'web',
            'status' => $extra['status'] ?? 1,
            'error_msg' => $extra['error_msg'] ?? '',
        ]);
    }
}