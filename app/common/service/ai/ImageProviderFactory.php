<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Config;

/**
 * AI配图工厂类 - V2.8新增
 * 仿照AiProviderFactory实现
 */
class ImageProviderFactory
{
    protected static ?ImageProviderInterface $defaultProvider = null;

    /**
     * 获取默认配图Provider
     */
    public static function getDefault(): ImageProviderInterface
    {
        if (self::$defaultProvider !== null) {
            return self::$defaultProvider;
        }

        $config = Config::get('ai.image', []);
        $providerName = $config['default_provider'] ?? 'tongyi_wanxiang';
        
        self::$defaultProvider = self::createProvider($providerName, $config);
        
        return self::$defaultProvider;
    }

    /**
     * 创建指定配图Provider
     */
    public static function createProvider(string $name, array $config = []): ImageProviderInterface
    {
        $config = $config ?: Config::get('ai.image', []);
        
        switch ($name) {
            case 'tongyi_wanxiang':
                return new image\TongyiWanxiangProvider($config);
            case 'flux':
                return new image\FluxProvider($config);
            case 'dalle':
                return new image\DalleProvider($config);
            default:
                throw new \Exception('不支持的配图Provider: ' . $name);
        }
    }

    /**
     * 获取备用配图Provider（降级）
     */
    public static function getFallbackProvider(?string $currentProvider = null): ?ImageProviderInterface
    {
        $config = Config::get('ai.image', []);
        $fallbackName = $config['fallback_provider'] ?? '';
        
        if (empty($fallbackName) || $fallbackName === $currentProvider) {
            return null;
        }
        
        try {
            return self::createProvider($fallbackName, $config);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取所有可用配图Provider
     */
    public static function getAvailableProviders(): array
    {
        $config = Config::get('ai.image', []);
        $providers = [];
        
        if (!empty($config['providers']['tongyi_wanxiang']['enabled'])) {
            $providers[] = 'tongyi_wanxiang';
        }
        if (!empty($config['providers']['flux']['enabled'])) {
            $providers[] = 'flux';
        }
        if (!empty($config['providers']['dalle']['enabled'])) {
            $providers[] = 'dalle';
        }
        
        return $providers;
    }
}
