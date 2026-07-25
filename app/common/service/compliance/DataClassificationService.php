<?php
declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Cache;
use think\facade\Db;

/**
 * 数据分级分类服务 - V2.9.40 COMPLIANCE2-4
 *
 * 数据分级分类管理：4级分类(public/internal/confidential/restricted)
 * 自动扫描+人工标记+分类规则引擎
 */
class DataClassificationService
{
    private const CACHE_TAG = 'data_classification';
    private const CACHE_TTL = 3600;

    /** 分类等级 */
    private const LEVELS = [
        'public'       => ['label' => '公开', 'color' => '#28a745', 'score' => 0],
        'internal'     => ['label' => '内部', 'color' => '#17a2b8', 'score' => 1],
        'confidential' => ['label' => '保密', 'color' => '#ffc107', 'score' => 2],
        'restricted'   => ['label' => '受限', 'color' => '#dc3545', 'score' => 3],
    ];

    /** 自动识别规则 */
    private const AUTO_RULES = [
        'email'   => ['pattern' => '/[\w.+-]+@[\w.-]+\.\w{2,}/', 'level' => 'confidential'],
        'phone'   => ['pattern' => '/1[3-9]\d{9}/', 'level' => 'confidential'],
        'id_card' => ['pattern' => '/\d{17}[\dXx]/', 'level' => 'restricted'],
        'bank'    => ['pattern' => '/\d{16,19}/', 'level' => 'restricted'],
        'ip'      => ['pattern' => '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', 'level' => 'internal'],
        'amount'  => ['pattern' => '/[\d,]+\.\d{2}/', 'level' => 'confidential'],
    ];

    /**
     * 标记数据分类
     */
    public function classify(int $itemId, string $itemType, string $level, string $reason = ''): bool
    {
        $exists = Db::name('data_classification')
            ->where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->find();

        if ($exists) {
            Db::name('data_classification')->where('id', $exists['id'])->update([
                'level'       => $level,
                'reason'      => $reason,
                'classified_by' => 'manual',
                'updated_at'  => time(),
            ]);
        } else {
            Db::name('data_classification')->insert([
                'item_id'      => $itemId,
                'item_type'    => $itemType,
                'level'        => $level,
                'reason'       => $reason,
                'classified_by' => 'manual',
                'status'       => 1,
                'created_at'   => time(),
                'updated_at'   => time(),
            ]);
        }

        Cache::clear();
        return true;
    }

    /**
     * 自动扫描分类
     */
    public function autoClassify(int $itemId, string $itemType, string $content): string
    {
        $maxLevel = 'public';
        $maxScore = 0;

        foreach (self::AUTO_RULES as $name => $rule) {
            if (preg_match($rule['pattern'], $content)) {
                $score = self::LEVELS[$rule['level']]['score'];
                if ($score > $maxScore) {
                    $maxScore = $score;
                    $maxLevel = $rule['level'];
                }
            }
        }

        Db::name('data_classification')->insert([
            'item_id'      => $itemId,
            'item_type'    => $itemType,
            'level'        => $maxLevel,
            'reason'       => '自动识别: ' . $maxLevel,
            'classified_by' => 'auto',
            'status'       => 1,
            'created_at'   => time(),
            'updated_at'   => time(),
        ]);

        Cache::clear();
        return $maxLevel;
    }

    /**
     * 获取数据分类信息
     */
    public function getClassification(int $itemId, string $itemType): ?array
    {
        $record = Db::name('data_classification')
            ->where('item_id', $itemId)
            ->where('item_type', $itemType)
            ->find();

        if (!$record) return null;

        $record['level_info'] = self::LEVELS[$record['level']] ?? self::LEVELS['public'];
        return $record;
    }

    /**
     * 获取分类统计
     */
    public function getStats(): array
    {
        return [
            'total_items'  => Db::name('data_classification')->count(),
            'by_level'     => Db::name('data_classification')->group('level')->column('count(*) as cnt', 'level'),
            'by_type'      => Db::name('data_classification')->group('item_type')->column('count(*) as cnt', 'item_type'),
            'auto_count'   => Db::name('data_classification')->where('classified_by', 'auto')->count(),
            'manual_count' => Db::name('data_classification')->where('classified_by', 'manual')->count(),
            'levels'       => self::LEVELS,
        ];
    }

    /**
     * 获取分类列表
     */
    public function getList(string $level = '', string $itemType = '', int $page = 1, int $limit = 20): array
    {
        $query = Db::name('data_classification')->where('status', 1);
        if ($level) $query->where('level', $level);
        if ($itemType) $query->where('item_type', $itemType);

        return $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
    }

    /**
     * 删除分类标记
     */
    public function delete(int $id): bool
    {
        Db::name('data_classification')->where('id', $id)->delete();
        Cache::clear();
        return true;
    }
}
