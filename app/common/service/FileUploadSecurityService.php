<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Db;

/**
 * V2.9.35 SEC-4: 文件上传安全增强服务
 * 扩展现有UploadSecurityService(V2.9.5 7层校验)
 * 新增: 图片二次渲染(GD清除EXIF) + SVG安全过滤 + SHA256哈希 + 扫描状态 + 隔离机制
 */
class FileUploadSecurityService
{
    /**
     * 计算文件SHA256哈希
     */
    public function calculateHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * 图片二次渲染（清除EXIF和嵌入的恶意代码）
     */
    public function reprocessImage(string $filePath): bool
    {
        $config = Config::get('security.file_upload', []);
        if (empty($config['image_reprocess'])) {
            return true;
        }

        $info = @getimagesize($filePath);
        if ($info === false) {
            return true;
        }

        $imageType = $info[2];

        try {
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $img = imagecreatefromjpeg($filePath);
                    if ($img !== false) {
                        imagejpeg($img, $filePath, 85);
                        imagedestroy($img);
                    }
                    break;
                case IMAGETYPE_PNG:
                    $img = imagecreatefrompng($filePath);
                    if ($img !== false) {
                        imagepng($img, $filePath, 6);
                        imagedestroy($img);
                    }
                    break;
                case IMAGETYPE_GIF:
                    $img = imagecreatefromgif($filePath);
                    if ($img !== false) {
                        imagegif($img, $filePath);
                        imagedestroy($img);
                    }
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $img = imagecreatefromwebp($filePath);
                        if ($img !== false) {
                            imagewebp($img, $filePath, 85);
                            imagedestroy($img);
                        }
                    }
                    break;
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * SVG安全过滤
     */
    public function filterSvg(string $filePath): bool
    {
        $config = Config::get('security.file_upload', []);
        if (empty($config['svg_filter'])) {
            return true;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return true;
        }

        // 移除script标签
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        // 移除事件属性
        $content = preg_replace('/\son\w+\s*=\s*["\']?[^"\'>\s]*/i', '', $content);
        // 移除JS伪协议
        $content = preg_replace('/javascript:/i', '', $content);
        // 移除外部实体引用（防XXE）
        $content = preg_replace('/<!ENTITY[^>]*>/i', '', $content);
        $content = preg_replace('/<!DOCTYPE[^>]*\[/i', '', $content);

        @file_put_contents($filePath, $content);
        return true;
    }

    /**
     * 记录文件扫描状态
     */
    public function recordScanStatus(int $attachmentId, string $hash, string $status = 'pending', ?array $result = null): void
    {
        Db::name('attachment')->where('id', $attachmentId)->update([
            'file_hash'      => $hash,
            'scan_status'    => $status,
            'scan_result'    => $result ? json_encode($result, JSON_UNESCAPED_UNICODE) : null,
            'is_quarantined' => $status === 'malicious' ? 1 : 0,
        ]);
    }

    /**
     * 隔离文件
     */
    public function quarantine(string $filePath, int $attachmentId): string
    {
        $config = Config::get('security.file_upload', []);
        $quarantineDir = $config['quarantine_dir'] ?? runtime_path() . 'quarantine/';

        if (!is_dir($quarantineDir)) {
            @mkdir($quarantineDir, 0755, true);
        }

        $quarantinePath = $quarantineDir . 'att_' . $attachmentId . '_' . basename($filePath);

        if (file_exists($filePath)) {
            @rename($filePath, $quarantinePath);
        }

        Db::name('attachment')->where('id', $attachmentId)->update([
            'is_quarantined' => 1,
            'scan_status'    => 'quarantined',
        ]);

        return $quarantinePath;
    }

    /**
     * 检查文件哈希是否在已知恶意列表中
     */
    public function checkMaliciousHash(string $hash): bool
    {
        // 查询隔离区是否有相同哈希的文件
        $count = Db::name('attachment')
            ->where('file_hash', $hash)
            ->where('scan_status', 'malicious')
            ->count();

        return $count > 0;
    }

    /**
     * 获取附件安全状态统计
     */
    public function getScanStats(): array
    {
        $stats = Db::name('attachment')
            ->field('scan_status, COUNT(*) as count')
            ->group('scan_status')
            ->select()
            ->toArray();

        $result = [
            'pending'     => 0,
            'clean'       => 0,
            'malicious'   => 0,
            'quarantined' => 0,
        ];

        foreach ($stats as $row) {
            $result[$row['scan_status']] = $row['count'];
        }

        $result['total'] = array_sum($result);
        return $result;
    }
}
