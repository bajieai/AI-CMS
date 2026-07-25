<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;

/**
 * V2.9.35 SEC-4: 病毒扫描服务
 * 可选ClamAV集成，未安装ClamAV时降级为跳过
 */
class VirusScanService
{
    /**
     * 扫描文件
     */
    public function scan(string $filePath): array
    {
        $config = Config::get('security.file_upload', []);

        if (empty($config['virus_scan'])) {
            return [
                'scanned' => false,
                'clean'   => true,
                'message' => '病毒扫描未启用',
            ];
        }

        $socket = $config['clamav_socket'] ?? '/var/run/clamav/clamd.sock';

        if (!file_exists($socket)) {
            return [
                'scanned' => false,
                'clean'   => true,
                'message' => 'ClamAV未运行，跳过扫描',
            ];
        }

        try {
            $result = $this->scanWithClamav($filePath, $socket);
            return $result;
        } catch (\Throwable $e) {
            return [
                'scanned' => false,
                'clean'   => true,
                'message' => 'ClamAV扫描异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 使用ClamAV守护进程扫描
     */
    protected function scanWithClamav(string $filePath, string $socket): array
    {
        $handle = @fsockopen('unix://' . $socket, 0, $errno, $errstr, 5);
        if (!$handle) {
            return [
                'scanned' => false,
                'clean'   => true,
                'message' => "ClamAV连接失败: {$errstr}",
            ];
        }

        // 发送INSTREAM扫描命令
        fwrite($handle, "zINSTREAM\0");

        $fileContent = file_get_contents($filePath);
        $chunkSize = 8192;

        for ($offset = 0; $offset < strlen($fileContent); $offset += $chunkSize) {
            $chunk = substr($fileContent, $offset, $chunkSize);
            $header = pack('N', strlen($chunk));
            fwrite($handle, $header . $chunk);
        }

        // 发送结束标记
        fwrite($handle, pack('N', 0));

        // 读取响应
        $response = '';
        while (!feof($handle)) {
            $data = fgets($handle, 4096);
            $response .= $data;
            if (str_contains($response, "\0")) {
                break;
            }
        }

        fclose($handle);

        $response = trim(str_replace("\0", '', $response));

        if (str_contains($response, 'OK')) {
            return [
                'scanned' => true,
                'clean'   => true,
                'message' => $response,
            ];
        }

        if (str_contains($response, 'FOUND')) {
            return [
                'scanned' => true,
                'clean'   => false,
                'message' => $response,
                'threat'  => $response,
            ];
        }

        return [
            'scanned' => false,
            'clean'   => true,
            'message' => '未知响应: ' . $response,
        ];
    }
}
