<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-3: 配置中心
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 配置中心 - V2.9.39 SYS-ROBUST-3
 * 分组管理 + 热更新 + 版本管理 + 环境隔离
 */
class ConfigCenterService
{
    protected const CACHE_TAG = 'config_center';
    protected const CACHE_TTL = 3600;

    protected string $table = 'config';
    protected string $versionTable = 'config_version';

    /**
     * 获取配置分组列表
     */
    public function getGroups(): array
    {
        return Cache::remember('config_groups', function () {
            try {
                $groups = Db::name($this->table)
                    ->field('`group`, count(*) as count')
                    ->group('`group`')
                    ->order('`group`')
                    ->select()
                    ->toArray();

                // 预定义分组
                $defaultGroups = [
                    'system'    => '系统配置',
                    'security'  => '安全配置',
                    'seo'       => 'SEO配置',
                    'email'     => '邮件配置',
                    'sms'       => '短信配置',
                    'payment'   => '支付配置',
                    'ai'        => 'AI配置',
                    'upload'    => '上传配置',
                    'cache'     => '缓存配置',
                    'performance' => '性能配置',
                ];

                $result = [];
                foreach ($defaultGroups as $key => $label) {
                    $count = 0;
                    foreach ($groups as $g) {
                        if ($g['group'] === $key) {
                            $count = $g['count'];
                            break;
                        }
                    }
                    $result[] = ['key' => $key, 'label' => $label, 'count' => $count];
                }

                // 添加未预定义的分组
                foreach ($groups as $g) {
                    if (!isset($defaultGroups[$g['group']])) {
                        $result[] = ['key' => $g['group'], 'label' => $g['group'], 'count' => $g['count']];
                    }
                }

                return $result;
            } catch (\Throwable) {
                return [];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 获取分组下的配置项
     */
    public function getConfigsByGroup(string $group, ?string $env = null): array
    {
        $cacheKey = 'configs_' . $group . '_' . ($env ?? 'all');

        return Cache::remember($cacheKey, function () use ($group, $env) {
            $query = Db::name($this->table)->where('group', $group);

            if ($env !== null) {
                $query->where(function ($q) use ($env) {
                    $q->where('env', $env)->whereOr('env', '')->whereOr('env', null);
                });
            }

            return $query->order('sort', 'asc')->select()->toArray();
        }, self::CACHE_TTL);
    }

    /**
     * 获取单个配置值
     */
    public function get(string $name, string $default = '', ?string $env = null): string
    {
        $cacheKey = 'config_value_' . $name . '_' . ($env ?? 'default');

        return Cache::remember($cacheKey, function () use ($name, $env, $default) {
            try {
                $query = Db::name($this->table)->where('name', $name);

                if ($env !== null) {
                    $query->where(function ($q) use ($env) {
                        $q->where('env', $env)->whereOr('env', '')->whereOr('env', null);
                    });
                }

                $value = $query->value('value');
                return $value !== null ? (string) $value : $default;
            } catch (\Throwable) {
                return $default;
            }
        }, self::CACHE_TTL);
    }

    /**
     * 设置配置值（支持热更新）
     */
    public function set(string $name, string $value, string $group = 'system', ?string $env = null, ?int $operatorId = null): array
    {
        // 保存旧值用于版本记录
        $oldValue = $this->get($name, '', $env);

        try {
            $exists = Db::name($this->table)->where('name', $name)->when($env, fn($q) => $q->where('env', $env))->find();

            if ($exists) {
                Db::name($this->table)->where('id', $exists['id'])->update([
                    'value'       => $value,
                    'group'       => $group,
                    'env'         => $env ?? ($exists['env'] ?? ''),
                    'update_time' => time(),
                ]);
            } else {
                Db::name($this->table)->insert([
                    'name'        => $name,
                    'value'       => $value,
                    'group'       => $group,
                    'env'         => $env ?? '',
                    'type'        => 'string',
                    'sort'        => 0,
                    'status'      => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }

            // 记录版本
            $this->saveVersion($name, $oldValue, $value, $operatorId ?? 0, $env);

            // 热更新：清除缓存
            Cache::clear();

            // 同步到ThinkPHP运行时配置
            \think\facade\Config::set($name, $value);

            Log::info('[ConfigCenter] 配置已更新', ['name' => $name, 'group' => $group, 'env' => $env]);

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('[ConfigCenter] 配置更新失败', ['name' => $name, 'error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 批量设置配置
     */
    public function setBatch(array $configs, string $group = 'system', ?string $env = null, ?int $operatorId = null): array
    {
        $results = [];
        foreach ($configs as $name => $value) {
            $results[$name] = $this->set($name, (string) $value, $group, $env, $operatorId);
        }
        return $results;
    }

    /**
     * 删除配置
     */
    public function delete(string $name, ?string $env = null): bool
    {
        $query = Db::name($this->table)->where('name', $name);
        if ($env !== null) {
            $query->where('env', $env);
        }

        $result = $query->delete();
        Cache::clear();

        return $result > 0;
    }

    /**
     * 获取配置版本历史
     */
    public function getVersionHistory(string $name, int $page = 1, int $limit = 20): array
    {
        $query = Db::name($this->versionTable)->where('config_name', $name);
        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 回滚到指定版本
     */
    public function rollbackToVersion(int $versionId, ?int $operatorId = null): array
    {
        $version = Db::name($this->versionTable)->find($versionId);
        if (!$version) {
            return ['success' => false, 'msg' => '版本不存在'];
        }

        $result = $this->set(
            $version['config_name'],
            $version['old_value'],
            '',
            $version['env'] ?? null,
            $operatorId
        );

        if ($result['success']) {
            Log::info('[ConfigCenter] 配置已回滚', [
                'config_name' => $version['config_name'],
                'version_id'  => $versionId,
            ]);
        }

        return $result;
    }

    /**
     * 比较两个版本的差异
     */
    public function compareVersions(int $versionId1, int $versionId2): array
    {
        $v1 = Db::name($this->versionTable)->find($versionId1);
        $v2 = Db::name($this->versionTable)->find($versionId2);

        if (!$v1 || !$v2) {
            return ['success' => false, 'msg' => '版本不存在'];
        }

        return [
            'success'  => true,
            'v1'       => $v1,
            'v2'       => $v2,
            'changed'  => $v1['new_value'] !== $v2['new_value'],
        ];
    }

    /**
     * 导出配置
     */
    public function export(?string $group = null, ?string $env = null): array
    {
        $query = Db::name($this->table);
        if ($group) {
            $query->where('group', $group);
        }
        if ($env) {
            $query->where('env', $env);
        }

        $configs = $query->select()->toArray();
        $export = [];

        foreach ($configs as $config) {
            $export[$config['name']] = [
                'value' => $config['value'],
                'group' => $config['group'],
                'type'  => $config['type'] ?? 'string',
                'env'   => $config['env'] ?? '',
            ];
        }

        return $export;
    }

    /**
     * 导入配置
     */
    public function import(array $configs, ?string $env = null, ?int $operatorId = null): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($configs as $name => $data) {
            $value = is_array($data) ? ($data['value'] ?? '') : (string) $data;
            $group = is_array($data) ? ($data['group'] ?? 'system') : 'system';

            $result = $this->set($name, (string) $value, $group, $env, $operatorId);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $name . ': ' . ($result['msg'] ?? 'unknown');
            }
        }

        return $results;
    }

    /**
     * 保存版本记录
     */
    protected function saveVersion(string $name, string $oldValue, string $newValue, int $operatorId, ?string $env): void
    {
        try {
            Db::name($this->versionTable)->insert([
                'config_name'  => $name,
                'old_value'    => $oldValue,
                'new_value'    => $newValue,
                'env'          => $env ?? '',
                'operator_id'  => $operatorId,
                'ip_address'   => request()->ip(),
                'create_time'  => time(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ConfigCenter] 版本记录失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 获取所有环境列表
     */
    public function getEnvironments(): array
    {
        return [
            ['key' => '',          'label' => '默认（所有环境）'],
            ['key' => 'production', 'label' => '生产环境'],
            ['key' => 'staging',   'label' => '预发布环境'],
            ['key' => 'development','label' => '开发环境'],
            ['key' => 'testing',   'label' => '测试环境'],
        ];
    }

    /**
     * 清除所有配置缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }
}
