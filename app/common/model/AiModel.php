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

namespace app\common\model;

use think\Model;

/**
 * AI模型配置模型
 */
class AiModel extends Model
{
    protected $name = 'ai_model';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'max_tokens'  => 'integer',
        'temperature' => 'float',
        'is_default'  => 'integer',
        'is_enabled'  => 'integer',
        'sort'        => 'integer',
    ];

    /**
     * 获取能力标签数组
     */
    public function getCapabilitiesAttr($value): array
    {
        return $value ? explode(',', $value) : [];
    }

    /**
     * 设置能力标签
     */
    public function setCapabilitiesAttr($value): string
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * API密钥读取时解密
     */
    public function getApiKeyAttr($value): string
    {
        if (empty($value)) {
            return '';
        }
        return self::decryptApiKey($value);
    }

    /**
     * API密钥写入时加密
     */
    public function setApiKeyAttr($value): string
    {
        if (empty($value)) {
            return '';
        }
        return self::encryptApiKey($value);
    }

    /**
     * 加密API Key（AES-256-CBC）
     */
    public static function encryptApiKey(string $plainKey): string
    {
        $appKey = env('APP_KEY', 'default_encryption_key_v24');
        $iv = substr(hash('sha256', $appKey), 0, 16);
        $encrypted = openssl_encrypt($plainKey, 'AES-256-CBC', hash('sha256', $appKey), 0, $iv);
        return base64_encode($encrypted);
    }

    /**
     * 解密API Key
     */
    public static function decryptApiKey(string $encryptedKey): string
    {
        try {
            $appKey = env('APP_KEY', 'default_encryption_key_v24');
            $iv = substr(hash('sha256', $appKey), 0, 16);
            $decrypted = openssl_decrypt(base64_decode($encryptedKey), 'AES-256-CBC', hash('sha256', $appKey), 0, $iv);
            return $decrypted !== false ? $decrypted : '';
        } catch (\Throwable) {
            return '';
        }
    }
}
