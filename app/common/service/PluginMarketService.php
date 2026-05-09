<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Plugin as PluginModel;
use think\facade\Config;
use think\facade\Log;

/**
 * 插件市场服务 - V2.9.2 M25
 * 远程仓库浏览 + 一键安装 + 本地上传ZIP安装 + 更新检测
 */
class PluginMarketService
{
    /**
     * 获取远程市场插件列表
     */
    public function getMarketList(array $filters = []): array
    {
        $marketUrl = Config::get('plugin.market_url', '');
        if (empty($marketUrl)) {
            return ['success' => false, 'msg' => '插件市场未配置', 'data' => []];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 15]);
            $response = $client->get($marketUrl . '/plugins', [
                'query' => [
                    'page'     => $filters['page'] ?? 1,
                    'limit'    => $filters['limit'] ?? 20,
                    'keyword'  => $filters['keyword'] ?? '',
                    'category' => $filters['category'] ?? '',
                    'cms_version' => config('app.version', '2.9.2'),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            if (!is_array($body)) {
                return ['success' => false, 'msg' => '市场返回格式错误', 'data' => []];
            }

            $plugins = $body['data'] ?? [];

            // 叠加本地安装状态
            $localPlugins = PluginModel::column('version', 'code');
            foreach ($plugins as &$plugin) {
                $code = $plugin['code'] ?? '';
                if (isset($localPlugins[$code])) {
                    $plugin['local_version'] = $localPlugins[$code];
                    $plugin['is_installed'] = 1;
                    $plugin['has_update'] = version_compare($plugin['version'] ?? '1.0.0', $localPlugins[$code], '>');
                } else {
                    $plugin['local_version'] = '';
                    $plugin['is_installed'] = 0;
                    $plugin['has_update'] = false;
                }
            }

            return [
                'success' => true,
                'msg'     => '获取成功',
                'data'    => $plugins,
                'total'   => $body['total'] ?? count($plugins),
            ];
        } catch (\Throwable $e) {
            Log::warning('[PluginMarket] 获取市场列表失败: ' . $e->getMessage());
            return ['success' => false, 'msg' => '连接市场失败: ' . $e->getMessage(), 'data' => []];
        }
    }

    /**
     * 从市场下载并安装插件
     */
    public function installFromMarket(string $code, string $downloadUrl): array
    {
        $tempZip = runtime_path() . 'temp/plugin_' . $code . '_' . time() . '.zip';
        $extractDir = root_path() . 'plugin/' . $code;

        try {
            // 1. 下载ZIP
            $client = new \GuzzleHttp\Client(['timeout' => 60]);
            $response = $client->get($downloadUrl, ['sink' => $tempZip]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('下载失败，HTTP状态码: ' . $response->getStatusCode());
            }

            // 2. 解压
            $this->extractZip($tempZip, $extractDir);

            // 3. 校验 plugin.json
            $jsonFile = $extractDir . '/plugin.json';
            if (!file_exists($jsonFile)) {
                throw new \Exception('插件包中缺少 plugin.json');
            }

            $info = json_decode(file_get_contents($jsonFile), true);
            if (!$info || ($info['code'] ?? '') !== $code) {
                throw new \Exception('插件标识不一致');
            }

            // 4. 调用本地安装
            PluginService::install($code);

            // 5. 清理临时文件
            @unlink($tempZip);

            return ['success' => true, 'msg' => '插件安装成功'];
        } catch (\Throwable $e) {
            // 清理失败残留
            @unlink($tempZip);
            if (is_dir($extractDir)) {
                $this->removeDir($extractDir);
            }
            return ['success' => false, 'msg' => '安装失败: ' . $e->getMessage()];
        }
    }

    /**
     * 本地上传ZIP安装
     */
    public function uploadAndInstall(string $zipPath, string $originalName = ''): array
    {
        $tempZip = runtime_path() . 'temp/upload_' . time() . '_' . basename($originalName);

        try {
            if (!file_exists($zipPath)) {
                throw new \Exception('上传文件不存在');
            }

            // 移动临时文件
            if (!copy($zipPath, $tempZip)) {
                throw new \Exception('文件处理失败');
            }

            // 先解压到临时目录读取plugin.json
            $tempExtractDir = runtime_path() . 'temp/plugin_extract_' . time();
            $this->extractZip($tempZip, $tempExtractDir);

            // 读取plugin.json获取code
            $jsonFile = $tempExtractDir . '/plugin.json';
            if (!file_exists($jsonFile)) {
                // 可能在子目录中
                $subDirs = glob($tempExtractDir . '/*', GLOB_ONLYDIR);
                foreach ($subDirs as $subDir) {
                    $candidate = $subDir . '/plugin.json';
                    if (file_exists($candidate)) {
                        $jsonFile = $candidate;
                        $tempExtractDir = $subDir;
                        break;
                    }
                }
            }

            if (!file_exists($jsonFile)) {
                throw new \Exception('ZIP包中未找到 plugin.json');
            }

            $info = json_decode(file_get_contents($jsonFile), true);
            if (!$info || empty($info['code'])) {
                throw new \Exception('plugin.json 格式错误或缺少 code 字段');
            }

            $code = $info['code'];
            $targetDir = root_path() . 'plugin/' . $code;

            // 如果目标目录已存在，先删除旧版本
            if (is_dir($targetDir)) {
                $this->removeDir($targetDir);
            }

            // 移动到正式目录
            if (!rename($tempExtractDir, $targetDir)) {
                throw new \Exception('无法移动插件到安装目录');
            }

            // 安装
            PluginService::install($code);

            // 清理
            @unlink($tempZip);
            @rmdir(dirname($tempExtractDir));

            return ['success' => true, 'msg' => '插件 "' . ($info['name'] ?? $code) . '" 安装成功'];
        } catch (\Throwable $e) {
            @unlink($tempZip);
            return ['success' => false, 'msg' => '安装失败: ' . $e->getMessage()];
        }
    }

    /**
     * 检查更新
     */
    public function checkUpdates(): array
    {
        $marketUrl = Config::get('plugin.market_url', '');
        if (empty($marketUrl)) {
            return [];
        }

        $localPlugins = PluginModel::select();
        if ($localPlugins->isEmpty()) {
            return [];
        }

        $codes = $localPlugins->column('code');

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 15]);
            $response = $client->post($marketUrl . '/check-updates', [
                'json' => ['codes' => $codes, 'cms_version' => config('app.version', '2.9.2')],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            $updates = $body['data'] ?? [];

            $result = [];
            foreach ($localPlugins as $plugin) {
                $remote = $updates[$plugin->code] ?? null;
                if ($remote && version_compare($remote['version'], $plugin->version, '>')) {
                    $result[] = [
                        'code'          => $plugin->code,
                        'name'          => $plugin->name,
                        'local_version' => $plugin->version,
                        'remote_version'=> $remote['version'],
                        'download_url'  => $remote['download_url'] ?? '',
                    ];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning('[PluginMarket] 检查更新失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取市场分类列表
     */
    public function getCategories(): array
    {
        $marketUrl = Config::get('plugin.market_url', '');
        if (empty($marketUrl)) {
            return [];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->get($marketUrl . '/categories');
            $body = json_decode((string) $response->getBody(), true);
            return $body['data'] ?? [];
            } catch (\Throwable) {
                return [];
            }
    }

    /**
     * 解压ZIP文件
     */
    protected function extractZip(string $zipPath, string $extractTo): void
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('服务器未启用 ZipArchive 扩展，无法解压插件');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('无法打开ZIP文件');
        }

        if (!is_dir($extractTo)) {
            mkdir($extractTo, 0755, true);
        }

        $zip->extractTo($extractTo);
        $zip->close();
    }

    /**
     * 递归删除目录
     */
    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
