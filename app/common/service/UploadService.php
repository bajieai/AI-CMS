<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

/**
 * 上传服务 - V2.6增强：支持对象存储(CDN)上传
 * V2.9.5 安全加固：委托UploadSecurityService进行统一安全校验
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
        // V2.9.5 安全加固：委托UploadSecurityService进行统一安全校验
        $validate = UploadSecurityService::validate($file, 'image');
        if (!$validate['valid']) {
            throw new \Exception($validate['error']);
        }

        $ext = $validate['ext'];
        $mimeType = $validate['mime'];

        // 生成 UUID v4 安全文件名，避免并发碰撞
        $filename = UploadSecurityService::generateSecureFilename($ext);
        $dateDir = date('Ymd');
        $savePath = $dateDir . '/' . $filename;

        // V2.6: 根据存储配置选择上传方式
        $driverName = StorageService::getDefaultDriver();
        if ($driverName === 'local') {
            $uploadDir = public_path() . 'uploads' . DIRECTORY_SEPARATOR . $dateDir . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file->move($uploadDir, $filename);
            $url = '/uploads/' . $savePath;
        } else {
            // 云存储：使用临时文件路径直传
            $tmpPath = $file->getRealPath();
            if (!$tmpPath || !file_exists($tmpPath)) {
                throw new \Exception('上传文件临时路径无效');
            }
            $result = StorageService::upload($tmpPath, $savePath, ['content_type' => $mimeType]);
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? '云存储上传失败');
            }
            $url = $result['url'];
        }

        return ['url' => $url, 'path' => $savePath, 'storage_driver' => $driverName];
    }

    /**
     * 上传媒体文件（图片/视频/文件）
     * @param \think\file\UploadedFile $file 上传文件对象
     * @param string $type 媒体类型 image|video|file
     * @return array ['url'=>'', 'path'=>'', 'filename'=>'', 'mimetype'=>'', 'filesize'=>0]
     * @throws \Exception
     */
    public function uploadMedia($file, string $type = 'image'): array
    {
        // V2.9.5 安全加固：委托UploadSecurityService进行统一安全校验
        $validate = UploadSecurityService::validate($file, $type);
        if (!$validate['valid']) {
            throw new \Exception($validate['error']);
        }

        $ext = $validate['ext'];
        $mimeType = $validate['mime'];

        $filename = UploadSecurityService::generateSecureFilename($ext);
        $dateDir = date('Ymd');
        $savePath = $dateDir . '/' . $filename;

        // V2.6: 根据存储配置选择上传方式
        $driverName = StorageService::getDefaultDriver();
        if ($driverName === 'local') {
            $uploadDir = public_path() . 'uploads' . DIRECTORY_SEPARATOR . $dateDir . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file->move($uploadDir, $filename);
            $url = '/uploads/' . $savePath;
        } else {
            $tmpPath = $file->getRealPath();
            if (!$tmpPath || !file_exists($tmpPath)) {
                throw new \Exception('上传文件临时路径无效');
            }
            $result = StorageService::upload($tmpPath, $savePath, ['content_type' => $mimeType]);
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? '云存储上传失败');
            }
            $url = $result['url'];
        }

        return [
            'url' => $url,
            'path' => $savePath,
            'storage_driver' => $driverName,
            'filename' => $file->getOriginalName(),
            'mimetype' => $mimeType,
            'filesize' => $file->getSize(),
        ];
    }

    /**
     * 生成 UUID v4 (RFC 4122)
     * V2.9.5 弃用：请使用 UploadSecurityService::generateSecureFilename()
     */
    protected static function uuidV4(): string
    {
        return UploadSecurityService::generateSecureFilename('tmp');
    }
}
