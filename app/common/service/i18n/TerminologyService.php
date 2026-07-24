<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use think\facade\Cache;
use think\facade\Db;

/**
 * 术语表管理服务 - V2.9.40 I18N-V3-1
 *
 * 多语言术语表：专业术语统一翻译、一致性校验、术语库CRUD
 */
class TerminologyService
{
    private const CACHE_TAG = 'terminology';
    private const CACHE_TTL = 3600;

    /**
     * 创建术语
     */
    public function create(array $data): int
    {
        $id = Db::name('terminology')->insertGetId([
            'term'          => $data['term'] ?? '',
            'domain'        => $data['domain'] ?? 'general',
            'source_lang'   => $data['source_lang'] ?? 'zh',
            'translations'  => json_encode($data['translations'] ?? []),
            'is_case_sensitive' => (int) ($data['is_case_sensitive'] ?? 0),
            'status'        => 1,
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 查询术语翻译
     */
    public function translate(string $term, string $targetLang, string $domain = 'general'): string
    {
        $cacheKey = 'term_' . md5($term . $targetLang . $domain);

        return Cache::remember($cacheKey, function () use ($term, $targetLang, $domain) {
            $entry = Db::name('terminology')
                ->where('term', $term)
                ->where('domain', $domain)
                ->where('status', 1)
                ->find();

            if (!$entry) {
                // 尝试general域
                $entry = Db::name('terminology')
                    ->where('term', $term)
                    ->where('domain', 'general')
                    ->where('status', 1)
                    ->find();
            }

            if (!$entry) return '';

            $translations = json_decode($entry['translations'] ?? '{}', true);
            return $translations[$targetLang] ?? '';
        }, self::CACHE_TTL);
    }

    /**
     * 批量翻译（将文本中的术语替换为规范翻译）
     */
    public function batchTranslate(string $text, string $targetLang, string $domain = 'general'): string
    {
        $terms = Db::name('terminology')
            ->where('domain', $domain)
            ->where('source_lang', 'zh')
            ->where('status', 1)
            ->select()
            ->toArray();

        $result = $text;
        foreach ($terms as $t) {
            $translations = json_decode($t['translations'] ?? '{}', true);
            $targetTerm = $translations[$targetLang] ?? '';
            if (!empty($targetTerm) && !empty($t['term'])) {
                $result = str_replace($t['term'], $targetTerm, $result);
            }
        }

        return $result;
    }

    /**
     * 获取术语列表
     */
    public function getList(string $domain = '', int $page = 1, int $limit = 50): array
    {
        $query = Db::name('terminology')->where('status', 1);
        if ($domain) $query->where('domain', $domain);

        return $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
    }

    /**
     * 更新术语
     */
    public function update(int $id, array $data): bool
    {
        $update = [];
        if (isset($data['term'])) $update['term'] = $data['term'];
        if (isset($data['domain'])) $update['domain'] = $data['domain'];
        if (isset($data['translations'])) $update['translations'] = json_encode($data['translations']);
        $update['updated_at'] = time();

        Db::name('terminology')->where('id', $id)->update($update);
        Cache::clear();
        return true;
    }

    /**
     * 删除术语
     */
    public function delete(int $id): bool
    {
        Db::name('terminology')->where('id', $id)->delete();
        Cache::clear();
        return true;
    }

    /**
     * 一致性校验（检查翻译是否符合术语表）
     */
    public function consistencyCheck(string $source, string $translation, string $targetLang, string $domain = 'general'): array
    {
        $issues = [];
        $terms = Db::name('terminology')
            ->where('domain', $domain)
            ->where('status', 1)
            ->select()
            ->toArray();

        foreach ($terms as $t) {
            $translations = json_decode($t['translations'] ?? '{}', true);
            $expected = $translations[$targetLang] ?? '';
            $actualTerm = $t['term'];

            // 检查源文本是否包含该术语
            if (strpos($source, $actualTerm) !== false) {
                // 检查翻译是否使用了规范术语
                if (!empty($expected) && strpos($translation, $expected) === false) {
                    $issues[] = [
                        'term'     => $actualTerm,
                        'expected' => $expected,
                        'domain'   => $domain,
                    ];
                }
            }
        }

        return [
            'total_terms' => count($terms),
            'issues'      => $issues,
            'score'       => count($terms) > 0 ? 1 - count($issues) / count($terms) : 1.0,
        ];
    }

    /**
     * 批量导入术语
     */
    public function batchImport(array $entries, string $domain = 'general'): int
    {
        $count = 0;
        foreach ($entries as $entry) {
            $this->create([
                'term'         => $entry['term'] ?? '',
                'domain'       => $domain,
                'source_lang'  => $entry['source_lang'] ?? 'zh',
                'translations' => $entry['translations'] ?? [],
            ]);
            $count++;
        }
        return $count;
    }
}
