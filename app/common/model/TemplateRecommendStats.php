<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板推荐效果统计模型 — V2.9.26 P-1
 *
 * 记录每日推荐位曝光/点击/安装数据，用于推荐效果分析和A/B测试。
 */
class TemplateRecommendStats extends Model
{
    protected $name = 'template_recommend_stats';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 记录曝光
     */
    public static function recordImpression(int $templateId, int $ruleId, string $position, string $abGroup = 'A'): void
    {
        $date = date('Y-m-d');
        $existing = self::where([
            'template_id' => $templateId,
            'rule_id'     => $ruleId,
            'position'    => $position,
            'stat_date'   => $date,
        ])->find();

        if ($existing) {
            $existing->impression_count++;
            $existing->save();
        } else {
            self::create([
                'template_id'      => $templateId,
                'rule_id'          => $ruleId,
                'position'         => $position,
                'ab_group'         => $abGroup,
                'impression_count' => 1,
                'click_count'      => 0,
                'install_count'    => 0,
                'stat_date'        => $date,
            ]);
        }
    }

    /**
     * 记录点击
     */
    public static function recordClick(int $templateId, int $ruleId, string $position): void
    {
        $date = date('Y-m-d');
        self::where([
            'template_id' => $templateId,
            'rule_id'     => $ruleId,
            'position'    => $position,
            'stat_date'   => $date,
        ])->inc('click_count')->update();
    }

    /**
     * 记录安装
     */
    public static function recordInstall(int $templateId, string $position = ''): void
    {
        $date = date('Y-m-d');
        $query = self::where([
            'template_id' => $templateId,
            'stat_date'   => $date,
        ]);
        if ($position !== '') {
            $query->where('position', $position);
        }
        $query->inc('install_count')->update();
    }

    /**
     * 获取模板的推荐效果汇总
     */
    public static function getTemplateStats(int $templateId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $row = self::where('template_id', $templateId)
            ->where('stat_date', '>=', $startDate)
            ->field([
                'SUM(impression_count) as total_impressions',
                'SUM(click_count) as total_clicks',
                'SUM(install_count) as total_installs',
            ])
            ->find();
        return $row ? $row->toArray() : ['total_impressions' => 0, 'total_clicks' => 0, 'total_installs' => 0];
    }
}
