<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;

/**
 * 上传服务
 */
class UploadService
{
    /**
     * 上传图片
     * @param \think\file\UploadedFile $file 上传文件对象
     * @return array ['url' => '/uploads/xxx.jpg', 'path' => 'xxx.jpg']
     * @throws \Exception
     */
    public function uploadImage($file): array
    {
        // 文件大小限制（默认 5MB）
        $maxSize = Config::get('upload.max_size', 5 * 1024 * 1024);
        if ($file->getSize() > $maxSize) {
            throw new \Exception('文件大小超过限制，最大允许 ' . ($maxSize / 1024 / 1024) . 'MB');
        }

        // MIME 类型白名单
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];
        $mimeType = $file->getMime();
        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception('不支持的文件类型：' . $mimeType);
        }

        // 使用 finfo 进行真实 MIME 检测（防止伪造）
        $realPath = $file->getRealPath();
        if ($realPath && function_exists('finfo_open')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($realPath);
            if (!in_array($realMime, $allowedMimes)) {
                throw new \Exception('文件内容类型不合法：' . $realMime);
            }
            // 扩展名与真实 MIME 一致性校验
            $mimeType = $realMime;
        }

        // 图片文件内容验证（防止恶意脚本伪装）
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            if (!function_exists('getimagesize') || !getimagesize($realPath)) {
                throw new \Exception('图片文件内容不合法');
            }
        }

        // 扩展名白名单与路径遍历防护
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $ext = strtolower($file->getOriginalExtension());
        if (preg_match('/[\/\\.]/', $ext) || !in_array($ext, $allowedExts)) {
            throw new \Exception('不支持的图片格式或非法扩展名');
        }

        // 扩展名与 MIME 类型一致性校验
        $mimeToExt = [
            'image/jpeg'    => ['jpg', 'jpeg'],
            'image/png'     => ['png'],
            'image/gif'     => ['gif'],
            'image/webp'    => ['webp'],
            'image/svg+xml' => ['svg'],
        ];
        if (!isset($mimeToExt[$mimeType]) || !in_array($ext, $mimeToExt[$mimeType])) {
            throw new \Exception('文件扩展名与内容类型不匹配');
        }

        // 构建上传目录（使用日期）
        $dateDir = date('Ymd');
        $uploadDir = public_path() . 'uploads' . DIRECTORY_SEPARATOR . $dateDir . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 生成 UUID v4 安全文件名，避免并发碰撞
        $filename = self::uuidV4() . '.' . $ext;

        // 移动文件
        $file->move($uploadDir, $filename);

        $url = '/uploads/' . $dateDir . '/' . $filename;

        return ['url' => $url, 'path' => $filename];
    }

    /**
     * 生成 UUID v4 (RFC 4122)
     * 完全零依赖实现，无需额外安装 composer 包
     */
    protected static function uuidV4(): string
    {
        $data = random_bytes(16);
        // 设置版本号 (0100) -> 第7字节高4位
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // 设置变体位 (10) -> 第8字节高2位
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
