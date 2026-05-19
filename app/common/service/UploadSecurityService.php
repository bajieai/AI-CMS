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

namespace app\common\service;

use think\facade\Config;

/**
 * V2.9.5 文件上传安全服务
 * 封装 MIME幻数校验 + 文件名随机化 + 白名单扩展 + 内容完整性校验
 */
class UploadSecurityService
{
    /**
     * 预定义的上传类型配置
     */
    protected static array $typeConfigs = [];

    /**
     * 初始化类型配置（合并用户自定义配置）
     */
    protected static function initConfigs(): void
    {
        if (!empty(self::$typeConfigs)) {
            return;
        }

        self::$typeConfigs = [
            'image' => [
                'maxSize'   => Config::get('upload.max_size', 5 * 1024 * 1024),
                'mimes'     => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
                'exts'      => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
                'mimeToExt' => [
                    'image/jpeg'    => ['jpg', 'jpeg'],
                    'image/png'     => ['png'],
                    'image/gif'     => ['gif'],
                    'image/webp'    => ['webp'],
                    'image/svg+xml' => ['svg'],
                ],
                'contentVerify' => true, // 使用 getimagesize 验证
            ],
            'video' => [
                'maxSize'   => 50 * 1024 * 1024,
                'mimes'     => ['video/mp4', 'video/webm', 'video/ogg'],
                'exts'      => ['mp4', 'webm', 'ogg'],
                'mimeToExt' => [
                    'video/mp4'  => ['mp4'],
                    'video/webm' => ['webm'],
                    'video/ogg'  => ['ogg'],
                ],
                'contentVerify' => false,
            ],
            'file' => [
                'maxSize'   => 20 * 1024 * 1024,
                'mimes'     => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/zip',
                    'application/x-rar-compressed',
                    'application/x-zip-compressed',
                    'application/octet-stream',
                ],
                'exts'      => ['pdf', 'doc', 'docx', 'zip', 'rar'],
                'mimeToExt' => [
                    'application/pdf'                                                        => ['pdf'],
                    'application/msword'                                                     => ['doc'],
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
                    'application/zip'                                                        => ['zip'],
                    'application/x-rar-compressed'                                           => ['rar'],
                    'application/x-zip-compressed'                                           => ['zip'],
                    'application/octet-stream'                                               => ['zip', 'rar'],
                ],
                'contentVerify' => false,
            ],
        ];

        // 加载用户自定义安全配置并合并
        $custom = Config::get('upload_security.types', []);
        foreach ($custom as $type => $cfg) {
            if (isset(self::$typeConfigs[$type])) {
                self::$typeConfigs[$type] = array_merge(self::$typeConfigs[$type], $cfg);
            } else {
                self::$typeConfigs[$type] = $cfg;
            }
        }
    }

    /**
     * 校验上传文件安全性
     * @param \think\file\UploadedFile $file
     * @param string $type image|video|file|自定义
     * @return array ['valid'=>bool, 'mime'=>string, 'ext'=>string, 'error'=>string]
     */
    public static function validate($file, string $type = 'image'): array
    {
        self::initConfigs();

        if (!isset(self::$typeConfigs[$type])) {
            return ['valid' => false, 'mime' => '', 'ext' => '', 'error' => '不支持的媒体类型: ' . $type];
        }

        $cfg = self::$typeConfigs[$type];

        // 1. 文件大小检查
        if ($file->getSize() > $cfg['maxSize']) {
            return [
                'valid' => false,
                'error' => '文件大小超过限制，最大允许 ' . ($cfg['maxSize'] / 1024 / 1024) . 'MB',
            ];
        }

        $realPath = $file->getRealPath();
        $declaredMime = $file->getMime();
        $ext = strtolower($file->getOriginalExtension());

        // 2. 扩展名基础校验（路径遍历防护）
        if (preg_match('/[\/\\\\\.]/', $ext) || !in_array($ext, $cfg['exts'], true)) {
            return ['valid' => false, 'error' => '不支持的文件格式或非法扩展名: ' . $ext];
        }

        // 3. 声明MIME白名单校验
        if (!in_array($declaredMime, $cfg['mimes'], true)) {
            return ['valid' => false, 'error' => '不支持的文件类型: ' . $declaredMime];
        }

        // 4. MIME幻数校验（防止伪造）
        $realMime = $declaredMime;
        if ($realPath && function_exists('finfo_open')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($realPath);
            if ($detected !== false) {
                $realMime = $detected;
            }
            if (!in_array($realMime, $cfg['mimes'], true)) {
                return ['valid' => false, 'error' => '文件内容类型不合法: ' . $realMime];
            }
        }

        // 5. 扩展名与真实MIME一致性校验
        if (!isset($cfg['mimeToExt'][$realMime]) || !in_array($ext, $cfg['mimeToExt'][$realMime], true)) {
            return ['valid' => false, 'error' => '文件扩展名与内容类型不匹配: ' . $ext . ' vs ' . $realMime];
        }

        // 6. 图片内容完整性校验
        if (!empty($cfg['contentVerify']) && in_array($realMime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            if (!function_exists('getimagesize') || !getimagesize($realPath)) {
                return ['valid' => false, 'error' => '图片文件内容不合法或已损坏'];
            }
        }

        // 7. 二次黑名单校验（防御性编程）
        $blacklistExts = Config::get('upload_security.blacklist_exts', ['php', 'php3', 'php4', 'php5', 'phtml', 'jsp', 'asp', 'aspx', 'sh', 'bat', 'exe', 'dll']);
        if (in_array($ext, $blacklistExts, true)) {
            return ['valid' => false, 'error' => '该文件类型已被系统禁止上传'];
        }

        return ['valid' => true, 'mime' => $realMime, 'ext' => $ext, 'error' => ''];
    }

    /**
     * 生成安全随机文件名（UUID v4）
     */
    public static function generateSecureFilename(string $ext): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return $uuid . '.' . $ext;
    }

    /**
     * 获取类型配置
     */
    public static function getTypeConfig(string $type): ?array
    {
        self::initConfigs();
        return self::$typeConfigs[$type] ?? null;
    }
}
