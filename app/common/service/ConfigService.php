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

use app\common\model\Config as ConfigModel;
use think\facade\Cache;

/**
 * 配置服务
 * 封装i8j_config表的读写操作
 */
class ConfigService
{
    /**
     * 获取配置值
     * 优先从已加载的ThinkPHP Config中读取，否则查库
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 尝试从ThinkPHP已加载配置中读取
        // key格式: group.name 或直接name
        if (strpos($key, '.') !== false) {
            $value = config($key);
            if ($value !== null) {
                return $value;
            }
        }

        // 尝试直接用name查库
        try {
            $config = ConfigModel::where('name', $key)->find();
            if ($config) {
                return $config->value;
            }

            // 尝试将点分格式转换为下划线格式 (points_signin → points.signin)
            $dbName = str_replace('_', '_', $key);
            $config = ConfigModel::where('name', $dbName)->find();
            if ($config) {
                return $config->value;
            }
        } catch (\Throwable) {
            // 表不存在时降级
        }

        return $default;
    }

    /**
     * 设置配置值
     */
    public static function set(string $key, mixed $value, string $group = '', string $description = ''): bool
    {
        try {
            $config = ConfigModel::where('name', $key)->find();
            if ($config) {
                $config->value = $value;
                if ($group) $config->group = $group;
                if ($description) $config->description = $description;
                $config->save();
            } else {
                ConfigModel::create([
                    'name'        => $key,
                    'value'       => $value,
                    'group'       => $group ?: 'system',
                    'description' => $description,
                ]);
            }

            // 清除配置缓存
            Cache::tag(CacheService::TAG_CONFIG)->clear();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 批量获取某组配置
     */
    public static function getGroup(string $group): array
    {
        try {
            return ConfigModel::where('group', $group)->column('value', 'name');
        } catch (\Throwable) {
            return [];
        }
    }
}
