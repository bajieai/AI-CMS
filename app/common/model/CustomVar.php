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
 * 自定义变量模型
 */
class CustomVar extends Model
{
    protected $name = 'custom_var';

    protected $type = [
        'sort' => 'integer',
    ];

    protected $autoWriteTimestamp = true;

    /**
     * 获取单个变量值
     */
    public static function getValue(string $name, $default = null)
    {
        $all = self::getAll();
        return $all[$name] ?? $default;
    }

    /**
     * 获取所有变量（name => value 关联数组，带缓存）
     */
    public static function getAll(): array
    {
        return Cache::remember('custom_vars', function () {
            return self::order('sort', 'asc')->column('value', 'name');
        }, 3600);
    }

    /**
     * 清除自定义变量缓存
     */
    public static function clearCache(): void
    {
        Cache::delete('custom_vars');
    }

    /**
     * 保存后清除缓存
     */
    public static function onAfterWrite(): void
    {
        self::clearCache();
    }

    /**
     * 删除后清除缓存
     */
    public static function onAfterDelete(): void
    {
        self::clearCache();
    }
}
