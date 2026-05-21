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
use think\facade\Cache;
use app\common\service\CacheService;

/**
 * 功能模块模型
 */
class Module extends Model
{
    protected $name = 'module';

    protected $type = [
        'is_system'  => 'integer',
        'is_enabled' => 'integer',
        'sort'       => 'integer',
    ];

    protected $autoWriteTimestamp = true;

    /**
     * 获取已启用的模块 code 列表
     */
    public static function getEnabledCodes(): array
    {
        return Cache::tag(CacheService::TAG_CONFIG)->remember('enabled_modules', function () {
            return self::where('is_enabled', 1)->column('code');
        }, 3600);
    }

    /**
     * 获取已禁用的模块 menu_ids 列表
     */
    public static function getDisabledMenuIds(): array
    {
        $disabledModules = Cache::tag(CacheService::TAG_CONFIG)->remember('disabled_modules', function () {
            return self::where('is_enabled', 0)->column('menu_ids');
        }, 3600);

        $hiddenMenuIds = [];
        foreach ($disabledModules as $menuIdsJson) {
            $ids = json_decode($menuIdsJson, true);
            if (is_array($ids)) {
                $hiddenMenuIds = array_merge($hiddenMenuIds, $ids);
            }
        }
        return array_unique($hiddenMenuIds);
    }

    /**
     * 检查模块是否启用
     */
    public static function isEnabled(string $code): bool
    {
        return in_array($code, self::getEnabledCodes(), true);
    }

    /**
     * 清除模块缓存
     */
    public static function clearCache(): void
    {
        Cache::tag(CacheService::TAG_CONFIG)->clear();
    }

    /**
     * 保存后清除缓存
     */
    public static function onAfterWrite(): void
    {
        self::clearCache();
    }
}
