<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AiModel;
use think\facade\Cache;

/**
 * AI模型管理服务 - V2.5增强
 * 新增：密钥加密存储、速率限制、脱敏展示
 */
class AiModelService
{
    /**
     * 加密密钥（从.env的APP_KEY派生）
     */
    protected static function getEncryptKey(): string
    {
        return hash('sha256', env('APP_KEY', 'default_encryption_key_v25'));
    }

    /**
     * 加密IV（固定16字节）
     */
    protected static function getEncryptIv(): string
    {
        return substr(hash('sha256', env('APP_KEY', 'default_encryption_key_v25')), 0, 16);
    }

    /**
     * 加密API Key（AES-256-CBC）
     */
    public static function encryptApiKey(string $plainKey): string
    {
        if (empty($plainKey)) return '';
        $encrypted = openssl_encrypt($plainKey, 'AES-256-CBC', self::getEncryptKey(), 0, self::getEncryptIv());
        return base64_encode($encrypted);
    }

    /**
     * 解密API Key
     */
    public static function decryptApiKey(string $encryptedKey): string
    {
        if (empty($encryptedKey)) return '';
        try {
            $decrypted = openssl_decrypt(base64_decode($encryptedKey), 'AES-256-CBC', self::getEncryptKey(), 0, self::getEncryptIv());
            return $decrypted !== false ? $decrypted : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 获取解密后的API Key（供Provider调用时使用）
     */
    public static function getDecryptedApiKey(AiModel $model): string
    {
        $apiKey = $model->getAttr('api_key'); // 获取原始值，不触发获取器
        if (empty($apiKey)) {
            return '';
        }

        // 如果已标记加密，则解密
        if (!empty($model->api_key_encrypted)) {
            return self::decryptApiKey($apiKey);
        }

        // 未标记但可能是已加密的（兼容v2.4旧数据）
        // 尝试解密，如果解密失败则认为是明文
        $decrypted = self::decryptApiKey($apiKey);
        if (!empty($decrypted) && !str_starts_with($apiKey, 'sk-')) {
            return $decrypted;
        }

        return $apiKey;
    }

    /**
     * 加密并保存模型（写入时自动加密api_key）
     */
    public static function encryptAndSave(AiModel $model): bool
    {
        $apiKey = $model->getAttr('api_key');
        if (!empty($apiKey) && empty($model->api_key_encrypted)) {
            $model->setAttr('api_key', self::encryptApiKey($apiKey));
            $model->api_key_encrypted = 1;
        }
        return $model->save();
    }

    /**
     * 批量迁移：将所有明文api_key加密
     * @return array ['encrypted' => int, 'skipped' => int, 'errors' => int]
     */
    public static function migrateEncryptAll(): array
    {
        $result = ['encrypted' => 0, 'skipped' => 0, 'errors' => 0];
        $models = AiModel::select();

        foreach ($models as $model) {
            $apiKey = $model->getAttr('api_key');
            if (empty($apiKey)) {
                $result['skipped']++;
                continue;
            }

            if (!empty($model->api_key_encrypted)) {
                $result['skipped']++;
                continue;
            }

            try {
                $model->setAttr('api_key', self::encryptApiKey($apiKey));
                $model->api_key_encrypted = 1;
                $model->save();
                $result['encrypted']++;
            } catch (\Throwable) {
                $result['errors']++;
            }
        }

        return $result;
    }

    /**
     * 脱敏展示API Key（sk-****xxxx）
     */
    public static function maskApiKey(string $apiKey): string
    {
        if (empty($apiKey)) return '';
        if (strlen($apiKey) <= 8) return '****';
        return substr($apiKey, 0, 3) . '****' . substr($apiKey, -4);
    }

    /**
     * 获取所有模型列表
     */
    public static function getList(): array
    {
        return AiModel::order('sort', 'asc')->select()->toArray();
    }

    /**
     * 获取已启用的模型列表
     */
    public static function getEnabledList(): array
    {
        return AiModel::where('is_enabled', 1)->order('sort', 'asc')->select()->toArray();
    }

    /**
     * 创建或更新模型
     */
    public static function save(array $data): AiModel
    {
        if (!empty($data['id'])) {
            $model = AiModel::find($data['id']);
            if (!$model) {
                throw new \Exception('模型不存在');
            }
        } else {
            $model = new AiModel();
        }

        // 如果设为默认，先取消其他默认
        if (!empty($data['is_default'])) {
            AiModel::where('is_default', 1)->update(['is_default' => 0]);
        }

        $model->save($data);
        return $model;
    }

    /**
     * 删除模型
     */
    public static function delete(int $id): bool
    {
        $model = AiModel::find($id);
        if (!$model) {
            throw new \Exception('模型不存在');
        }
        if ($model->is_default) {
            throw new \Exception('不能删除默认模型');
        }
        return $model->delete();
    }

    /**
     * 设置默认模型
     */
    public static function setDefault(int $id): bool
    {
        $model = AiModel::find($id);
        if (!$model || !$model->is_enabled) {
            throw new \Exception('模型不存在或未启用');
        }

        AiModel::where('is_default', 1)->update(['is_default' => 0]);
        $model->is_default = 1;
        return $model->save();
    }

    /**
     * 切换启用状态
     */
    public static function toggleEnabled(int $id): bool
    {
        $model = AiModel::find($id);
        if (!$model) {
            throw new \Exception('模型不存在');
        }
        if ($model->is_default && $model->is_enabled) {
            throw new \Exception('不能禁用默认模型');
        }
        $model->is_enabled = $model->is_enabled ? 0 : 1;
        return $model->save();
    }

    /**
     * 测试模型连接
     */
    public static function testConnection(int $id): array
    {
        $model = AiModel::find($id);
        if (!$model) {
            throw new \Exception('模型不存在');
        }

        $providerClass = "\\app\\common\\service\\ai\\" . ucfirst($model->provider) . "Provider";
        if (!class_exists($providerClass)) {
            return ['success' => false, 'message' => "Provider {$model->provider} 不存在"];
        }

        try {
            $provider = new $providerClass($model);
            $result = $provider->write('你好，请回复"连接成功"');
            return [
                'success' => true,
                'message' => '连接成功',
                'response' => mb_substr($result, 0, 100),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '连接失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 检查速率限制
     * @return bool true=允许, false=超限
     */
    public static function checkRateLimit(int $modelId, int $rpm = 60, int $rph = 1000): bool
    {
        if ($rpm <= 0 && $rph <= 0) return true;

        $now = time();
        $minuteKey = "ai_rate_rpm_{$modelId}_" . intval($now / 60);
        $hourKey = "ai_rate_rph_{$modelId}_" . intval($now / 3600);

        // 检查每分钟限制
        if ($rpm > 0) {
            $rpmCount = (int) Cache::get($minuteKey, 0);
            if ($rpmCount >= $rpm) return false;
            Cache::set($minuteKey, $rpmCount + 1, 120);
        }

        // 检查每小时限制
        if ($rph > 0) {
            $rphCount = (int) Cache::get($hourKey, 0);
            if ($rphCount >= $rph) return false;
            Cache::set($hourKey, $rphCount + 1, 7200);
        }

        return true;
    }
}
