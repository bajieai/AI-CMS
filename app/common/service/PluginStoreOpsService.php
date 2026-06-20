<?php

declare(strict_types=1);

namespace app\common\service;

use app\common\model\PluginPackage;
use app\common\model\PluginCategory;
use app\common\model\PluginVersion;
use app\common\model\PluginDependency;
use think\facade\Cache;

/**
 * V2.9.25 L-2: 插件包运营 Service
 */
class PluginStoreOpsService
{
    /**
     * 获取插件包列表（带缓存）
     */
    public function getPackageList(array $params): array
    {
        $cacheKey = 'plugin_store_list_' . md5(json_encode($params));
        $cacheTag = 'plugin_store';

        return Cache::tag($cacheTag)->remember($cacheKey, function () use ($params) {
            $query = PluginPackage::with('category');

            if (!empty($params['keyword'])) {
                $query->where(function ($q) use ($params) {
                    $q->where('name', 'like', '%' . $params['keyword'] . '%')
                      ->whereOr('code', 'like', '%' . $params['keyword'] . '%')
                      ->whereOr('description', 'like', '%' . $params['keyword'] . '%');
                });
            }

            if (!empty($params['category_id'])) {
                $query->where('category_id', $params['category_id']);
            }

            if ($params['status'] !== '') {
                $query->where('status', (int) $params['status']);
            }

            $total = $query->count();
            $list = $query->order('sort', 'desc')
                ->order('id', 'desc')
                ->page($params['page'] ?? 1, $params['limit'] ?? 20)
                ->select();

            return [
                'list' => $list,
                'total' => $total,
            ];
        }, 3600);
    }

    /**
     * 创建插件包
     */
    public function createPackage(array $data): array
    {
        $validate = new \think\Validate([
            'code' => 'require|max:64|alphaDash',
            'name' => 'require|max:128',
            'version' => 'require|max:32',
            'category_id' => 'integer',
            'price' => 'float',
            'status' => 'in:0,1,2',
        ]);

        if (!$validate->check($data)) {
            return ['code' => 1, 'msg' => $validate->getError()];
        }

        // code 唯一性检查
        $exists = PluginPackage::where('code', $data['code'])->find();
        if ($exists) {
            return ['code' => 1, 'msg' => '插件标识已存在'];
        }

        $data['is_free'] = ($data['price'] ?? 0) <= 0 ? 1 : 0;
        $package = PluginPackage::create($data);

        Cache::tag('plugin_store')->clear();
        return ['code' => 0, 'msg' => '创建成功', 'data' => ['id' => $package->id]];
    }

    /**
     * 更新插件包
     */
    public function updatePackage(int $id, array $data): array
    {
        $package = PluginPackage::find($id);
        if (!$package) {
            return ['code' => 1, 'msg' => '插件包不存在'];
        }

        // 如果修改了 code，检查唯一性
        if (isset($data['code']) && $data['code'] !== $package->code) {
            $exists = PluginPackage::where('code', $data['code'])->where('id', '<>', $id)->find();
            if ($exists) {
                return ['code' => 1, 'msg' => '插件标识已存在'];
            }
        }

        if (isset($data['price'])) {
            $data['is_free'] = $data['price'] <= 0 ? 1 : 0;
        }

        $package->save($data);
        Cache::tag('plugin_store')->clear();
        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 获取前端浏览列表（仅上架）
     */
    public function getFrontList(array $params): array
    {
        $cacheKey = 'plugin_store_front_' . md5(json_encode($params));

        return Cache::tag('plugin_store')->remember($cacheKey, function () use ($params) {
            $query = PluginPackage::where('status', 1);

            if (!empty($params['category_id'])) {
                $query->where('category_id', $params['category_id']);
            }

            if (!empty($params['keyword'])) {
                $query->where(function ($q) use ($params) {
                    $q->where('name', 'like', '%' . $params['keyword'] . '%')
                      ->whereOr('tags', 'like', '%' . $params['keyword'] . '%');
                });
            }

            // 排序
            $sort = $params['sort'] ?? 'default';
            switch ($sort) {
                case 'download':
                    $query->order('download_count', 'desc');
                    break;
                case 'rating':
                    $query->order('rating_avg', 'desc');
                    break;
                case 'newest':
                    $query->order('create_time', 'desc');
                    break;
                case 'price_asc':
                    $query->order('price', 'asc');
                    break;
                case 'price_desc':
                    $query->order('price', 'desc');
                    break;
                default:
                    $query->order('is_recommended', 'desc')
                          ->order('is_hot', 'desc')
                          ->order('sort', 'desc');
            }

            $total = $query->count();
            $list = $query->page($params['page'] ?? 1, $params['limit'] ?? 20)
                ->select();

            return [
                'list' => $list,
                'total' => $total,
            ];
        }, 1800);
    }

    /**
     * 获取插件详情（前端）
     */
    public function getFrontDetail(string $code): array
    {
        $cacheKey = 'plugin_store_detail_' . $code;

        return Cache::tag('plugin_store')->remember($cacheKey, function () use ($code) {
            $package = PluginPackage::with(['category', 'versions', 'dependencies'])
                ->where('code', $code)
                ->where('status', 1)
                ->find();

            if (!$package) {
                return ['code' => 1, 'msg' => '插件不存在或已下架'];
            }

            return ['code' => 0, 'data' => $package];
        }, 3600);
    }

    /**
     * 检查插件更新（L-6）
     */
    public function checkUpdate(string $code, string $currentVersion): array
    {
        $package = PluginPackage::where('code', $code)->where('status', 1)->find();
        if (!$package) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        $latest = PluginVersion::where('plugin_id', $package->id)
            ->where('is_current', 1)
            ->find();

        if (!$latest) {
            return ['code' => 0, 'has_update' => false, 'msg' => '暂无更新'];
        }

        $hasUpdate = version_compare($latest->version, $currentVersion, '>');
        return [
            'code' => 0,
            'has_update' => $hasUpdate,
            'latest_version' => $latest->version,
            'current_version' => $currentVersion,
            'download_url' => $latest->file_path,
            'changelog' => $latest->changelog,
            'file_hash' => $latest->file_hash,
        ];
    }

    /**
     * 安装插件（L-7 / M-3）
     */
    public function installPackage(int $pluginId, array $extra = []): array
    {
        $package = PluginPackage::find($pluginId);
        if (!$package) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        // M-3: 触发插件启用前 Hook
        try {
            $hookResult = \app\common\hook\Hook::fire(\app\common\hook\HookEvents::PLUGIN_BEFORE_ENABLE, [
                'plugin_name' => $package->code,
                'version' => $package->version,
                'plugin_id' => $pluginId,
            ], ['module' => 'plugin_store', 'ip' => request()->ip()]);
            if ($hookResult->stopped) {
                return ['code' => 1, 'msg' => '安装被Hook拦截: ' . $hookResult->message];
            }
        } catch (\Throwable $e) {
            \think\facade\Log::warning('PLUGIN_BEFORE_ENABLE Hook 执行失败: ' . $e->getMessage());
        }

        // 依赖检查
        $depService = new PluginDependencyService();
        $depCheck = $depService->checkDependencies($pluginId);
        if (!$depCheck['success']) {
            return ['code' => 1, 'msg' => '缺少依赖: ' . implode(', ', $depCheck['missing'])];
        }

        // 获取当前版本
        $version = PluginVersion::where('plugin_id', $pluginId)
            ->where('is_current', 1)
            ->find();
        if (!$version) {
            return ['code' => 1, 'msg' => '没有可用版本'];
        }

        // 安全校验
        $filePath = root_path() . ltrim($version->file_path, '/');
        $security = new PluginSecurityService();
        $verify = $security->verifyPackage($filePath, $package);
        if (!$verify['success']) {
            return ['code' => 1, 'msg' => '安全校验失败: ' . $verify['msg']];
        }

        // 记录安装日志
        $security->logDownload($pluginId, $version->version, array_merge($extra, ['source' => 'install', 'status' => 1]));

        // 更新安装计数
        $package->install_count += 1;
        $package->save();
        Cache::tag('plugin_store')->clear();

        // M-3: 触发插件启用后 Hook
        try {
            \app\common\hook\Hook::fire(\app\common\hook\HookEvents::PLUGIN_AFTER_ENABLE, [
                'plugin_name' => $package->code,
                'version' => $version->version,
                'plugin_id' => $pluginId,
            ], ['module' => 'plugin_store', 'ip' => request()->ip()]);
        } catch (\Throwable $e) {
            \think\facade\Log::warning('PLUGIN_AFTER_ENABLE Hook 执行失败: ' . $e->getMessage());
        }

        return [
            'code' => 0,
            'msg' => '安装成功',
            'data' => [
                'plugin_id' => $pluginId,
                'version' => $version->version,
                'file_path' => $version->file_path,
            ],
        ];
    }

    /**
     * 卸载插件（L-7 / M-3）
     */
    public function uninstallPackage(int $pluginId): array
    {
        $package = PluginPackage::find($pluginId);
        if (!$package) {
            return ['code' => 1, 'msg' => '插件不存在'];
        }

        // M-3: 触发插件卸载前 Hook
        try {
            $hookResult = \app\common\hook\Hook::fire(\app\common\hook\HookEvents::PLUGIN_BEFORE_UNINSTALL, [
                'plugin_name' => $package->code,
                'version' => $package->version,
                'plugin_id' => $pluginId,
            ], ['module' => 'plugin_store', 'ip' => request()->ip()]);
            if ($hookResult->stopped) {
                return ['code' => 1, 'msg' => '卸载被Hook拦截: ' . $hookResult->message];
            }
        } catch (\Throwable $e) {
            \think\facade\Log::warning('PLUGIN_BEFORE_UNINSTALL Hook 执行失败: ' . $e->getMessage());
        }

        // 检查是否有其他插件依赖此插件
        $reverseDeps = PluginDependency::where('depends_on_plugin_id', $pluginId)->count();
        if ($reverseDeps > 0) {
            return ['code' => 1, 'msg' => '有 ' . $reverseDeps . ' 个插件依赖此插件，无法卸载'];
        }

        Cache::tag('plugin_store')->clear();

        // M-3: 触发插件卸载后 Hook
        try {
            \app\common\hook\Hook::fire(\app\common\hook\HookEvents::PLUGIN_AFTER_UNINSTALL, [
                'plugin_name' => $package->code,
                'version' => $package->version,
                'plugin_id' => $pluginId,
            ], ['module' => 'plugin_store', 'ip' => request()->ip()]);
        } catch (\Throwable $e) {
            \think\facade\Log::warning('PLUGIN_AFTER_UNINSTALL Hook 执行失败: ' . $e->getMessage());
        }

        return ['code' => 0, 'msg' => '卸载成功'];
    }
}
