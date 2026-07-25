<?php
declare(strict_types=1);
namespace app\common\service;

/**
 * Vite构建工具链Service - V2.9.32 PERF2-1
 * 方案A：与现有手动加载并行运行，不强制迁移
 */
class ViteBuildService
{
    public function isViteAvailable(): bool
    {
        return file_exists(public_path() . 'static/dist/.vite/manifest.json');
    }

    public function getAssetUrl(string $entry): string
    {
        if (!$this->isViteAvailable()) return '/static/' . $entry;
        $manifest = $this->getManifest();
        return isset($manifest[$entry]['file']) ? '/static/dist/' . $manifest[$entry]['file'] : '/static/' . $entry;
    }

    private function getManifest(): array
    {
        $path = public_path() . 'static/dist/.vite/manifest.json';
        if (!file_exists($path)) return [];
        return json_decode(file_get_contents($path), true) ?: [];
    }

    public static function assets(string $entry): string
    {
        $service = new self();
        $url = $service->getAssetUrl($entry);
        $ext = pathinfo($entry, PATHINFO_EXTENSION);
        if ($ext === 'js') return '<script src="' . $url . '"></script>';
        if ($ext === 'css') return '<link rel="stylesheet" href="' . $url . '">';
        return $url;
    }
}
