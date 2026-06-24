<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Db;
use think\facade\Cache;

/**
 * AI编辑器配置管理服务 — V2.9.28 A-8
 */
class AiConfigService
{
    private const CACHE_TAG = 'ai_config';

    /**
     * 功能开关key列表
     */
    private array $featureKeys = [
        'ai_editor_paragraph_optimize' => '段落优化',
        'ai_editor_conversation' => '多轮对话',
        'ai_editor_format_preserve' => '格式保留',
        'ai_editor_translate' => '选段翻译',
        'ai_editor_template_library' => '模板库',
        'ai_editor_snapshot' => '版本快照',
    ];

    /**
     * 获取AI编辑器配置
     */
    public function getConfig(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('ai_editor_config', function() {
            $config = [];
            foreach ($this->featureKeys as $key => $label) {
                $config['features'][$key] = [
                    'label' => $label,
                    'enabled' => (bool)Db::name('config')->where('name', $key)->value('value'),
                ];
            }

            // 其他配置
            $config['conversation_timeout'] = Db::name('config')->where('name', 'ai_editor_conversation_timeout')->value('value') ?: 1800;
            $config['conversation_max_token'] = Db::name('config')->where('name', 'ai_editor_conversation_max_token')->value('value') ?: 4096;
            $config['snapshot_max'] = Db::name('config')->where('name', 'ai_editor_snapshot_max')->value('value') ?: 50;

            // 快捷键配置（小扣v2审核问题5：Alt键防冲突）
            $config['shortcuts'] = [
                'menu' => Db::name('config')->where('name', 'ai_editor_shortcut_menu')->value('value') ?: 'alt+space',
                'optimize' => Db::name('config')->where('name', 'ai_editor_shortcut_optimize')->value('value') ?: 'alt+shift+o',
                'translate' => Db::name('config')->where('name', 'ai_editor_shortcut_translate')->value('value') ?: 'alt+shift+t',
            ];

            return $config;
        }, 3600);
    }

    /**
     * 保存配置
     */
    public function saveConfig(array $data): array
    {
        // 保存功能开关
        foreach ($this->featureKeys as $key => $label) {
            $value = isset($data['features'][$key]) ? '1' : '0';
            $this->upsertConfig($key, $value);
        }

        // 保存其他配置
        if (isset($data['conversation_timeout'])) {
            $this->upsertConfig('ai_editor_conversation_timeout', (string)$data['conversation_timeout']);
        }
        if (isset($data['conversation_max_token'])) {
            $this->upsertConfig('ai_editor_conversation_max_token', (string)$data['conversation_max_token']);
        }
        if (isset($data['snapshot_max'])) {
            $this->upsertConfig('ai_editor_snapshot_max', (string)$data['snapshot_max']);
        }

        // 保存快捷键
        if (isset($data['shortcuts'])) {
            foreach ($data['shortcuts'] as $name => $value) {
                $this->upsertConfig('ai_editor_shortcut_' . $name, $value);
            }
        }

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '配置已保存'];
    }

    /**
     * 检查功能是否启用
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $config = $this->getConfig();
        return $config['features'][$feature]['enabled'] ?? false;
    }

    /**
     * 获取API消耗统计
     */
    public function getApiUsageStats(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $stats = Db::name('ai_content_log')
            ->where('created_at', '>=', $startDate)
            ->field('DATE(created_at) as date, COUNT(*) as count, SUM(tokens_used) as tokens')
            ->group('date')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        return ['stats' => $stats, 'days' => $days];
    }

    /**
     * 插入或更新配置
     */
    private function upsertConfig(string $name, string $value): void
    {
        $existing = Db::name('config')->where('name', $name)->find();
        if ($existing) {
            Db::name('config')->where('name', $name)->update(['value' => $value]);
        } else {
            Db::name('config')->insert(['name' => $name, 'value' => $value, 'group' => 'ai']);
        }
    }
}
