<?php
declare(strict_types=1);

namespace app\common\service\theme;

use think\facade\Cache;
use think\facade\Log;

/**
 * 远程模板源服务 - V3.1 Sprint 15
 *
 * 功能：
 * 1. 从OSS获取静态JSON模板列表（带10分钟服务端缓存）
 * 2. CDN下载模板ZIP
 * 3. 离线降级策略（网络异常时返回预埋模板列表）
 * 4. 透传模式：后端仅fetchAndCache，所有筛选/排序在前端完成
 */
class RemoteTemplateSource
{
    /** 缓存键 */
    protected const CACHE_KEY_REMOTE_LIST = 'theme_market_remote_list';
    protected const CACHE_KEY_LAST_FETCH = 'theme_market_last_fetch';
    protected const CACHE_TTL = 600; // 10分钟

    /** 网络超时 */
    protected const FETCH_TIMEOUT = 15;
    protected const DOWNLOAD_TIMEOUT = 120;

    /**
     * 获取模板列表（优先缓存，降级本地）
     *
     * @return array ['templates' => [...], 'source' => 'remote|cache|local', 'fetched_at' => timestamp]
     */
    public function fetchTemplateList(): array
    {
        // 1. 尝试服务端缓存
        $cached = Cache::get(self::CACHE_KEY_REMOTE_LIST);
        if ($cached !== null) {
            return [
                'templates'  => $cached,
                'source'     => 'cache',
                'fetched_at' => Cache::get(self::CACHE_KEY_LAST_FETCH, 0),
            ];
        }

        // 2. 从远程获取
        $remote = $this->fetchFromRemote();
        if ($remote !== null) {
            Cache::set(self::CACHE_KEY_REMOTE_LIST, $remote, self::CACHE_TTL);
            Cache::set(self::CACHE_KEY_LAST_FETCH, time(), self::CACHE_TTL);
            return [
                'templates'  => $remote,
                'source'     => 'remote',
                'fetched_at' => time(),
            ];
        }

        // 3. 降级：返回预埋模板列表
        return [
            'templates'  => $this->getPrebuiltTemplates(),
            'source'     => 'local',
            'fetched_at' => 0,
        ];
    }

    /**
     * 强制刷新远程列表（后台管理调用）
     */
    public function refresh(): array
    {
        Cache::delete(self::CACHE_KEY_REMOTE_LIST);
        Cache::delete(self::CACHE_KEY_LAST_FETCH);
        return $this->fetchTemplateList();
    }

    /**
     * 从远程OSS获取JSON
     */
    protected function fetchFromRemote(): ?array
    {
        $url = config('app.theme_market_url', '');
        if (empty($url)) {
            Log::info('RemoteTemplateSource: 未配置theme_market_url，跳过远程获取');
            return null;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout'       => self::FETCH_TIMEOUT,
                    'user_agent'    => 'AI-CMS-ThemeMarket/3.1',
                    'follow_location' => true,
                    'max_redirects' => 3,
                ],
                'ssl'  => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $json = @file_get_contents($url, false, $context);
            if ($json === false) {
                Log::warning('RemoteTemplateSource: 远程获取失败，URL=' . $url);
                return null;
            }

            $data = json_decode($json, true);
            if (!is_array($data) || !isset($data['templates'])) {
                Log::warning('RemoteTemplateSource: 远程JSON格式不正确');
                return null;
            }

            // 安全校验：过滤非法字段
            $templates = [];
            foreach ($data['templates'] as $t) {
                if (empty($t['code']) || empty($t['name'])) {
                    continue;
                }
                $templates[] = [
                    'code'         => $this->sanitize($t['code']),
                    'name'         => $this->sanitize($t['name']),
                    'description'  => $this->sanitize($t['description'] ?? ''),
                    'version'      => $this->sanitize($t['version'] ?? '1.0.0'),
                    'author'       => $this->sanitize($t['author'] ?? ''),
                    'industry'     => $this->sanitize($t['industry'] ?? ''),
                    'style_tag'    => $this->sanitize($t['style_tag'] ?? ''),
                    'thumbnail'    => $this->sanitizeUrl($t['thumbnail'] ?? ''),
                    'screenshots'  => array_map([$this, 'sanitizeUrl'], $t['screenshots'] ?? []),
                    'avg_rating'   => (float) ($t['avg_rating'] ?? 0),
                    'install_count'=> (int) ($t['install_count'] ?? 0),
                    'download_url' => $this->sanitizeUrl($t['download_url'] ?? ''),
                    'source'       => 'remote',
                    'is_prebuilt'  => false,
                ];
            }

            return $templates;
        } catch (\Throwable $e) {
            Log::warning('RemoteTemplateSource: 异常 ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取预埋模板列表（离线降级）
     */
    public function getPrebuiltTemplates(): array
    {
        $prebuiltDir = root_path() . 'app' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'prebuilt';
        if (!is_dir($prebuiltDir)) {
            return [];
        }

        $templates = [];
        $dirs = glob($prebuiltDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $code = basename($dir);
            $jsonFile = $dir . DIRECTORY_SEPARATOR . 'theme.json';
            if (!is_file($jsonFile)) {
                continue;
            }

            $meta = json_decode(file_get_contents($jsonFile), true) ?: [];
            $templates[] = [
                'code'         => $code,
                'name'         => $meta['name'] ?? $code,
                'description'  => $meta['description'] ?? '',
                'version'      => $meta['version'] ?? '1.0.0',
                'author'       => $meta['author'] ?? '八界AI',
                'industry'     => $meta['industry'] ?? '',
                'style_tag'    => $meta['style_tag'] ?? '',
                'thumbnail'    => '',
                'screenshots'  => [],
                'avg_rating'   => 0,
                'install_count'=> 0,
                'download_url' => '',
                'source'       => 'prebuilt',
                'is_prebuilt'  => true,
            ];
        }

        return $templates;
    }

    /**
     * 下载远程模板ZIP到临时目录
     */
    public function downloadZip(string $downloadUrl): ?string
    {
        if (empty($downloadUrl) || !filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'theme_' . md5($downloadUrl . time()) . '.zip';

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout'         => self::DOWNLOAD_TIMEOUT,
                    'user_agent'      => 'AI-CMS-ThemeMarket/3.1',
                    'follow_location' => true,
                    'max_redirects'   => 3,
                ],
                'ssl'  => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $data = @file_get_contents($downloadUrl, false, $context);
            if ($data === false) {
                Log::warning('RemoteTemplateSource: 下载失败 ' . $downloadUrl);
                return null;
            }

            file_put_contents($tempFile, $data);
            return $tempFile;
        } catch (\Throwable $e) {
            Log::warning('RemoteTemplateSource: 下载异常 ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 字符串安全过滤
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * URL安全过滤
     */
    protected function sanitizeUrl(string $input): string
    {
        $url = trim($input);
        if (empty($url)) return '';
        // 仅允许 http/https 协议
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}
