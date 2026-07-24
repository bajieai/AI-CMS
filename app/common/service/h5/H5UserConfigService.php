<?php
declare(strict_types=1);

namespace app\common\service\h5;

use think\facade\Db;
use think\facade\Cache;

/**
 * H5用户配置管理服务 - V2.9.40
 * 存储用户级别偏好（通知设置、布局偏好、安全选项等）
 */
class H5UserConfigService
{
    protected static string $table = 'h5_user_config';

    /**
     * 获取配置
     *
     * @param int $memberId 会员ID
     * @param string $key 配置键
     * @return mixed 配置值（未找到返回null）
     */
    public static function getConfig(int $memberId, string $key): mixed
    {
        $cacheKey = 'h5_uc_' . $memberId . '_' . $key;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        $row = Db::name(self::$table)
            ->where('member_id', $memberId)
            ->where('config_key', $key)
            ->find();
        if ($row && $row['config_value']) {
            $value = is_string($row['config_value']) ? json_decode($row['config_value'], true) : $row['config_value'];
            Cache::set($cacheKey, $value, 3600);
            return $value;
        }
        return null;
    }

    /**
     * 设置配置
     *
     * @param int $memberId 会员ID
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @param string $type 配置类型(preference/notification/security/layout)
     * @return void
     */
    public static function setConfig(int $memberId, string $key, mixed $value, string $type = 'preference'): void
    {
        $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        $exists = Db::name(self::$table)
            ->where('member_id', $memberId)
            ->where('config_key', $key)
            ->find();
        $now = date('Y-m-d H:i:s');
        if ($exists) {
            Db::name(self::$table)
                ->where('member_id', $memberId)
                ->where('config_key', $key)
                ->update([
                    'config_value' => $jsonValue,
                    'config_type'  => $type,
                    'update_time'  => $now,
                ]);
        } else {
            Db::name(self::$table)->insert([
                'member_id'    => $memberId,
                'config_key'   => $key,
                'config_value' => $jsonValue,
                'config_type'  => $type,
                'create_time'  => $now,
                'update_time'  => $now,
            ]);
        }
        Cache::clear();
    }

    /**
     * 获取用户所有配置
     *
     * @param int $memberId 会员ID
     * @return array 配置数组（键值对格式）
     */
    public static function getAllConfig(int $memberId): array
    {
        $rows = Db::name(self::$table)
            ->where('member_id', $memberId)
            ->select()
            ->toArray();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['config_key']] = $row['config_value']
                ? (is_string($row['config_value']) ? json_decode($row['config_value'], true) : $row['config_value'])
                : null;
        }
        return $result;
    }

    /**
     * 删除配置
     *
     * @param int $memberId 会员ID
     * @param string $key 配置键
     * @return void
     */
    public static function deleteConfig(int $memberId, string $key): void
    {
        Db::name(self::$table)
            ->where('member_id', $memberId)
            ->where('config_key', $key)
            ->delete();
        Cache::clear();
    }

    /**
     * 批量设置配置
     *
     * @param int $memberId 会员ID
     * @param array $configs 键值对配置
     * @param string $type 配置类型
     * @return void
     */
    public static function batchSetConfig(int $memberId, array $configs, string $type = 'preference'): void
    {
        foreach ($configs as $key => $value) {
            self::setConfig($memberId, $key, $value, $type);
        }
    }

    /**
     * 获取用户配置列表（按类型过滤）
     *
     * @param int $memberId 会员ID
     * @param string $type 配置类型
     * @return array
     */
    public static function getConfigByType(int $memberId, string $type): array
    {
        $rows = Db::name(self::$table)
            ->where('member_id', $memberId)
            ->where('config_type', $type)
            ->select()
            ->toArray();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['config_key']] = $row['config_value']
                ? (is_string($row['config_value']) ? json_decode($row['config_value'], true) : $row['config_value'])
                : null;
        }
        return $result;
    }

    /**
     * 清除用户所有缓存
     *
     * @param int $memberId 会员ID
     * @return void
     */
    public static function clearCache(int $memberId): void
    {
        Cache::clear();
    }
}
