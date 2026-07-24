<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Db;

/**
 * V2.9.35 PLUG-3: 插件商店服务
 * 商店API对接 + 一键安装 + 更新检测
 */
class PluginStoreService
{
    /**
     * 商店API地址
     */
    protected string $storeApiUrl = 'https://store.i8j.cn/api/plugins';

    /**
     * 获取商店插件列表
     */
    public function getStoreList(array $params = []): array
    {
        $category = $params['category'] ?? '';
        $keyword = $params['keyword'] ?? '';
        $sort = $params['sort'] ?? 'latest';
        $page = (int)($params['page'] ?? 1);

        // 模拟商店API返回（实际需要对接商店API）
        return [
            'list' => [],
            'total' => 0,
            'page' => $page,
            'categories' => ['content', 'member', 'template', 'seo', 'security', 'tool'],
        ];
    }

    /**
     * 获取商店插件详情
     */
    public function getStoreDetail(string $storeId): array
    {
        // 模拟商店API返回
        return [];
    }

    /**
     * 商店一键安装
     */
    public function installFromStore(string $storeId): array
    {
        // 1. 下载ZIP
        $downloadResult = $this->downloadPlugin($storeId);
        if (!$downloadResult['success']) {
            return $downloadResult;
        }

        // 2. 解压
        $extractResult = $this->extractPlugin($downloadResult['path']);
        if (!$extractResult['success']) {
            return $extractResult;
        }

        // 3. 调用PluginManagerService安装
        $manager = new PluginManagerService();
        $installResult = $manager->install($extractResult['identifier']);

        // 4. 记录商店配置
        if ($installResult['success'] && !empty($installResult['plugin_id'])) {
            Db::name('plugin')->where('id', $installResult['plugin_id'])->update([
                'store_config' => json_encode([
                    'store_id'     => $storeId,
                    'install_time' => date('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return $installResult;
    }

    /**
     * 下载插件ZIP
     */
    protected function downloadPlugin(string $storeId): array
    {
        $tempDir = runtime_path() . 'plugin_temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/' . $storeId . '.zip';

        // 实际实现：从商店API下载ZIP
        // $client = new \GuzzleHttp\Client();
        // $client->get($this->storeApiUrl . '/' . $storeId . '/download', ['sink' => $zipPath]);

        return ['success' => false, 'message' => '商店API未配置，无法下载'];
    }

    /**
     * 解压插件ZIP
     */
    protected function extractPlugin(string $zipPath): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'ZIP文件不存在'];
        }

        // 使用ZipArchive解压
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'ZIP文件打开失败'];
        }

        $pluginRoot = root_path() . 'plugin' . DIRECTORY_SEPARATOR;
        if (!is_dir($pluginRoot)) {
            @mkdir($pluginRoot, 0755, true);
        }

        $zip->extractTo($pluginRoot);
        $zip->close();

        // 获取插件标识
        $identifier = basename($zipPath, '.zip');

        @unlink($zipPath);

        return ['success' => true, 'identifier' => $identifier];
    }

    /**
     * 检测插件更新
     */
    public function checkUpdates(): array
    {
        $installed = Db::name('plugin')
            ->whereNotNull('store_config')
            ->where('store_config', '<>', '')
            ->select()
            ->toArray();

        $updates = [];
        foreach ($installed as $plugin) {
            $storeConfig = json_decode($plugin['store_config'] ?? '{}', true);
            if (!empty($storeConfig['store_id'])) {
                // 检查商店是否有新版本
                // 实际实现：调用商店API检测版本
                // $storeDetail = $this->getStoreDetail($storeConfig['store_id']);
                // if (version_compare($storeDetail['version'], $plugin['version'], '>')) {
                //     $updates[] = ['plugin_id' => $plugin['id'], 'current' => $plugin['version'], 'latest' => $storeDetail['version']];
                // }
            }
        }

        return $updates;
    }
}
