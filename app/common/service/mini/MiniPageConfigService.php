<?php
declare(strict_types=1);

namespace app\common\service\mini;

use app\common\model\MiniPageConfig;
use think\facade\Cache;

/**
 * 移动端页面配置服务
 * V2.9.37 MINI-FULL-3
 */
class MiniPageConfigService
{
    private const CACHE_TAG = 'mini_page_config';

    /**
     * 获取配置列表
     */
    public function getConfigs(string $pageType = '', string $platform = 'all'): array
    {
        $query = MiniPageConfig::order('update_time', 'desc');
        if ($pageType) {
            $query->where('page_type', $pageType);
        }
        $query->where('platform', 'in', ['all', $platform]);
        return $query->paginate(20)->toArray();
    }

    /**
     * 获取已发布配置(前台用)
     */
    public function getPublishedConfig(string $pageType, string $platform = 'all'): ?array
    {
        return Cache::remember(
            'mini_pc:' . $pageType . ':' . $platform,
            function () use ($pageType, $platform) {
                $config = MiniPageConfig::where('page_type', $pageType)
                    ->where('platform', 'in', ['all', $platform])
                    ->where('is_published', 1)
                    ->order('version', 'desc')
                    ->find();
                return $config ? $config->toArray() : null;
            },
            300
        );
    }

    /**
     * 保存配置
     */
    public function saveConfig(array $data): int
    {
        $latest = MiniPageConfig::where('page_type', $data['page_type'])
            ->where('platform', $data['platform'] ?? 'all')
            ->order('version', 'desc')
            ->find();
        $nextVersion = $latest ? ($latest['version'] + 1) : 1;

        $data['version'] = $nextVersion;
        $data['is_published'] = 0;

        $model = MiniPageConfig::create($data);
        Cache::clear();
        return (int) $model->id;
    }

    /**
     * 发布配置
     */
    public function publishConfig(int $id): bool
    {
        $config = MiniPageConfig::find($id);
        if (!$config) {
            return false;
        }
        // 取消同类型同平台的已发布
        MiniPageConfig::where('page_type', $config['page_type'])
            ->where('platform', $config['platform'])
            ->where('is_published', 1)
            ->update(['is_published' => 0]);
        // 发布当前版本
        $config->is_published = 1;
        $config->publish_time = date('Y-m-d H:i:s');
        $result = $config->save();
        Cache::clear();
        return (bool) $result;
    }

    /**
     * 回滚到历史版本
     */
    public function rollbackConfig(int $id): bool
    {
        $target = MiniPageConfig::find($id);
        if (!$target || $target['is_published']) {
            return false;
        }
        return $this->publishConfig($id);
    }

    /**
     * 预览配置
     */
    public function previewConfig(int $id): array
    {
        $config = MiniPageConfig::find($id);
        if (!$config) {
            return [];
        }
        return $config->toArray();
    }

    /**
     * 导出配置JSON
     */
    public function exportConfig(int $id): string
    {
        $config = MiniPageConfig::find($id);
        if (!$config) {
            return '{}';
        }
        return json_encode($config->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 导入配置JSON
     */
    public function importConfig(string $json): int
    {
        $data = json_decode($json, true);
        if (!$data || !isset($data['page_type'])) {
            return 0;
        }
        unset($data['id'], $data['version'], $data['is_published'], $data['publish_time']);
        return $this->saveConfig($data);
    }

    /**
     * 获取预设布局模板
     */
    public function getDefaultLayouts(): array
    {
        return [
            'home_standard' => [
                'name' => '标准首页',
                'components' => [
                    ['type' => 'carousel', 'props' => ['autoplay' => true, 'interval' => 3000]],
                    ['type' => 'nav', 'props' => ['items' => []]],
                    ['type' => 'content_list', 'props' => ['model' => 'product', 'limit' => 6, 'style' => 'card']],
                    ['type' => 'content_list', 'props' => ['model' => 'case', 'limit' => 4, 'style' => 'grid']],
                    ['type' => 'content_list', 'props' => ['model' => 'news', 'limit' => 5, 'style' => 'list']],
                ],
            ],
            'home_simple' => [
                'name' => '简约首页',
                'components' => [
                    ['type' => 'image', 'props' => ['src' => '', 'mode' => 'widthFix']],
                    ['type' => 'content_list', 'props' => ['model' => 'product', 'limit' => 8, 'style' => 'grid']],
                ],
            ],
            'home_business' => [
                'name' => '企业首页',
                'components' => [
                    ['type' => 'carousel', 'props' => ['autoplay' => true]],
                    ['type' => 'company_intro', 'props' => ['title' => '关于我们']],
                    ['type' => 'content_list', 'props' => ['model' => 'product', 'limit' => 6, 'style' => 'card']],
                    ['type' => 'content_list', 'props' => ['model' => 'case', 'limit' => 4, 'style' => 'waterfall']],
                    ['type' => 'contact', 'props' => []],
                ],
            ],
        ];
    }

    /**
     * 获取组件列表(委托给组件库Service)
     */
    public function getComponentList(): array
    {
        return (new MiniComponentLibraryService())->getComponents('');
    }
}
