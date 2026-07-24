<?php
declare(strict_types=1);

namespace app\common\service\ml;

use app\common\model\LangPack;
use app\common\model\LangPackSnapshot;
use think\facade\Cache;

/**
 * 语言包管理服务
 * V2.9.37 I18N-2
 * P0-2修复: 包含版本快照/对比/回滚功能
 */
class LangPackService
{
    private const CACHE_TAG = 'lang_pack';

    /**
     * 获取语言包列表(含完成度)
     */
    public function getPackList(): array
    {
        $langCodes = LangPack::field('lang_code, COUNT(*) as total, SUM(is_translated) as translated')
            ->group('lang_code')
            ->select()
            ->toArray();
        $result = [];
        foreach ($langCodes as $item) {
            $rate = $item['total'] > 0 ? round($item['translated'] / $item['total'] * 100, 2) : 0;
            $result[] = [
                'lang_code'        => $item['lang_code'],
                'total'            => $item['total'],
                'translated'       => $item['translated'],
                'untranslated'     => $item['total'] - $item['translated'],
                'completion_rate'  => $rate,
            ];
        }
        return $result;
    }

    /**
     * 获取条目列表
     */
    public function getEntries(string $langCode, array $filters = []): array
    {
        $query = LangPack::where('lang_code', $langCode);
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (!empty($filters['group'])) {
            $query->where('group_name', $filters['group']);
        }
        if (isset($filters['translated'])) {
            $query->where('is_translated', $filters['translated']);
        }
        if (!empty($filters['keyword'])) {
            $query->where('entry_key|entry_value|entry_original', 'like', '%' . $filters['keyword'] . '%');
        }
        return $query->order('sort_order', 'asc')->paginate(50)->toArray();
    }

    /**
     * 保存条目
     */
    public function saveEntry(array $data): int
    {
        $existing = LangPack::where('lang_code', $data['lang_code'])
            ->where('module', $data['module'] ?? 'frontend')
            ->where('group_name', $data['group_name'] ?? 'general')
            ->where('entry_key', $data['entry_key'])
            ->find();
        if ($existing) {
            $existing->entry_value = $data['entry_value'] ?? '';
            $existing->is_translated = !empty($data['entry_value']) ? 1 : 0;
            $existing->save();
            Cache::clear();
            return (int) $existing->id;
        }
        $model = LangPack::create([
            'lang_code'      => $data['lang_code'],
            'module'         => $data['module'] ?? 'frontend',
            'group_name'     => $data['group_name'] ?? 'general',
            'entry_key'      => $data['entry_key'],
            'entry_value'    => $data['entry_value'] ?? '',
            'entry_original' => $data['entry_original'] ?? '',
            'is_translated'  => !empty($data['entry_value']) ? 1 : 0,
            'is_using_ai'    => $data['is_using_ai'] ?? 0,
            'is_system'      => $data['is_system'] ?? 0,
            'sort_order'     => $data['sort_order'] ?? 0,
        ]);
        Cache::clear();
        return (int) $model->id;
    }

    /**
     * AI批量翻译
     */
    public function batchTranslate(string $langCode, string $module = 'frontend'): array
    {
        $untranslated = LangPack::where('lang_code', $langCode)
            ->where('module', $module)
            ->where('is_translated', 0)
            ->limit(100)
            ->select()
            ->toArray();
        if (empty($untranslated)) {
            return ['total' => 0, 'success' => 0, 'failed' => 0];
        }
        // 创建翻译前快照
        $this->createSnapshot($langCode, $module, 'auto_save');

        $memoryService = new TranslationMemoryService();
        $aiService = app()->make(\app\common\service\AiTranslationService::class);
        $defaultLang = (new LangSwitchService())->getDefaultLang();
        $success = 0;
        $failed = 0;
        foreach ($untranslated as $entry) {
            // 先查翻译记忆库
            $memoryMatch = $memoryService->match($entry['entry_original'] ?: $entry['entry_key'], $defaultLang, $langCode);
            if ($memoryMatch && $memoryMatch['similarity'] >= 80) {
                $entry['entry_value'] = $memoryMatch['target_text'];
                $entry['is_using_ai'] = 0;
            } else {
                // 调用AI翻译
                try {
                    $result = $aiService->translate(
                        $entry['entry_original'] ?: $entry['entry_key'],
                        $defaultLang,
                        $langCode
                    );
                    $entry['entry_value'] = $result;
                    $entry['is_using_ai'] = 1;
                    // 保存到翻译记忆库
                    $memoryService->store(
                        $entry['entry_original'] ?: $entry['entry_key'],
                        $result,
                        $defaultLang,
                        $langCode,
                        ['context_type' => 'lang_pack']
                    );
                } catch (\Throwable $e) {
                    $failed++;
                    continue;
                }
            }
            $entry['is_translated'] = 1;
            LangPack::update($entry, ['id' => $entry['id']]);
            $success++;
        }
        Cache::clear();
        return ['total' => count($untranslated), 'success' => $success, 'failed' => $failed];
    }

    /**
     * 导入语言包
     */
    public function importPack(string $langCode, string $json): int
    {
        $data = json_decode($json, true);
        if (!$data || !is_array($data)) return 0;
        $count = 0;
        foreach ($data as $item) {
            $item['lang_code'] = $langCode;
            if ($this->saveEntry($item)) $count++;
        }
        return $count;
    }

    /**
     * 导出语言包
     */
    public function exportPack(string $langCode, string $module = ''): string
    {
        $query = LangPack::where('lang_code', $langCode);
        if ($module) $query->where('module', $module);
        $entries = $query->select()->toArray();
        return json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 获取统计信息
     */
    public function getStats(string $langCode): array
    {
        $total = LangPack::where('lang_code', $langCode)->count();
        $translated = LangPack::where('lang_code', $langCode)->where('is_translated', 1)->count();
        $aiTranslated = LangPack::where('lang_code', $langCode)->where('is_using_ai', 1)->count();
        $modules = LangPack::where('lang_code', $langCode)
            ->field('module, COUNT(*) as cnt, SUM(is_translated) as translated')
            ->group('module')
            ->select()
            ->toArray();
        return [
            'total'          => $total,
            'translated'     => $translated,
            'untranslated'   => $total - $translated,
            'ai_translated'  => $aiTranslated,
            'completion_rate' => $total > 0 ? round($translated / $total * 100, 2) : 0,
            'modules'        => $modules,
        ];
    }

    // ===== P0-2修复: 版本快照/对比/回滚 =====

    /**
     * 创建版本快照
     */
    public function createSnapshot(string $langCode, string $module = 'frontend', string $reason = 'manual'): int
    {
        $entries = LangPack::where('lang_code', $langCode)
            ->where('module', $module)
            ->select()
            ->toArray();
        $total = count($entries);
        $translated = count(array_filter($entries, fn($e) => $e['is_translated']));
        $latestVersion = LangPackSnapshot::where('lang_code', $langCode)
            ->where('module', $module)
            ->max('version') ?: 0;
        $snapshot = LangPackSnapshot::create([
            'lang_code'        => $langCode,
            'module'           => $module,
            'version'          => $latestVersion + 1,
            'snapshot_data'    => $entries,
            'entry_count'      => $total,
            'translated_count' => $translated,
            'completion_rate'  => $total > 0 ? round($translated / $total * 100, 2) : 0,
            'created_by'       => 0,
            'create_reason'    => $reason,
        ]);
        return (int) $snapshot->id;
    }

    /**
     * 版本历史
     */
    public function getVersionHistory(string $langCode, string $module = 'frontend'): array
    {
        return LangPackSnapshot::where('lang_code', $langCode)
            ->where('module', $module)
            ->order('version', 'desc')
            ->limit(20)
            ->select()
            ->toArray();
    }

    /**
     * 版本对比 (P0-2修复)
     */
    public function compareVersions(string $langCode, string $module, int $v1, int $v2): array
    {
        $snap1 = LangPackSnapshot::where('lang_code', $langCode)->where('module', $module)->where('version', $v1)->find();
        $snap2 = LangPackSnapshot::where('lang_code', $langCode)->where('module', $module)->where('version', $v2)->find();
        if (!$snap1 || !$snap2) return [];
        $data1 = $snap1['snapshot_data'];
        $data2 = $snap2['snapshot_data'];
        $map1 = [];
        foreach ($data1 as $entry) {
            $key = $entry['group_name'] . '.' . $entry['entry_key'];
            $map1[$key] = $entry;
        }
        $added = [];
        $modified = [];
        $deleted = [];
        foreach ($data2 as $entry) {
            $key = $entry['group_name'] . '.' . $entry['entry_key'];
            if (!isset($map1[$key])) {
                $added[] = $entry;
            } elseif ($map1[$key]['entry_value'] !== $entry['entry_value']) {
                $modified[] = [
                    'key' => $key,
                    'old_value' => $map1[$key]['entry_value'],
                    'new_value' => $entry['entry_value'],
                ];
            }
            unset($map1[$key]);
        }
        foreach ($map1 as $key => $entry) {
            $deleted[] = $entry;
        }
        return [
            'v1' => $v1, 'v2' => $v2,
            'v1_info' => ['entries' => $snap1['entry_count'], 'translated' => $snap1['translated_count'], 'rate' => $snap1['completion_rate']],
            'v2_info' => ['entries' => $snap2['entry_count'], 'translated' => $snap2['translated_count'], 'rate' => $snap2['completion_rate']],
            'added'    => $added,
            'modified' => $modified,
            'deleted'  => $deleted,
            'summary'  => [
                'added_count'    => count($added),
                'modified_count' => count($modified),
                'deleted_count'  => count($deleted),
            ],
        ];
    }

    /**
     * 回滚到指定版本
     */
    public function rollbackVersion(string $langCode, string $module, int $version): bool
    {
        $snapshot = LangPackSnapshot::where('lang_code', $langCode)
            ->where('module', $module)
            ->where('version', $version)
            ->find();
        if (!$snapshot) return false;
        // 先创建当前状态的快照
        $this->createSnapshot($langCode, $module, 'pre_rollback');
        // 删除当前所有条目
        LangPack::where('lang_code', $langCode)->where('module', $module)->delete();
        // 从快照恢复
        foreach ($snapshot['snapshot_data'] as $entry) {
            unset($entry['id'], $entry['create_time'], $entry['update_time']);
            LangPack::create($entry);
        }
        Cache::clear();
        return true;
    }
}
