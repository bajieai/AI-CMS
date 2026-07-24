<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateRecommendRule;
use app\common\model\TemplateRecommendStats;
use think\facade\Cache;

/**
 * 推荐规则管理服务 — V2.9.26 P-1
 */
class RecommendationRuleService
{
    /**
     * 规则列表（分页）
     */
    public function list(int $page = 1, int $limit = 20, array $filter = []): array
    {
        $query = TemplateRecommendRule::order('priority', 'desc')->order('sort', 'asc');

        if (!empty($filter['rule_type'])) {
            $query->where('rule_type', $filter['rule_type']);
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int)$filter['status']);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 创建规则
     */
    public function create(array $data): array
    {
        $rule = TemplateRecommendRule::create([
            'name'         => $data['name'] ?? '',
            'rule_type'    => $data['rule_type'] ?? 'manual',
            'template_ids' => $data['template_ids'] ?? [],
            'category_id'  => $data['category_id'] ?? 0,
            'priority'     => $data['priority'] ?? 10,
            'ab_group'     => $data['ab_group'] ?? 'A',
            'conditions'   => $data['conditions'] ?? [],
            'status'       => $data['status'] ?? 1,
            'start_time'   => $data['start_time'] ?? null,
            'end_time'     => $data['end_time'] ?? null,
            'sort'         => $data['sort'] ?? 100,
        ]);

        $this->clearCache();
        return ['code' => 0, 'msg' => '创建成功', 'data' => $rule->toArray()];
    }

    /**
     * 更新规则
     */
    public function update(int $id, array $data): array
    {
        $rule = TemplateRecommendRule::find($id);
        if (!$rule) {
            return ['code' => -1, 'msg' => '规则不存在'];
        }

        $updateFields = ['name', 'rule_type', 'template_ids', 'category_id', 'priority',
                         'ab_group', 'conditions', 'status', 'start_time', 'end_time', 'sort'];
        foreach ($updateFields as $field) {
            if (isset($data[$field])) {
                $rule->{$field} = $data[$field];
            }
        }
        $rule->save();

        $this->clearCache();
        return ['code' => 0, 'msg' => '更新成功'];
    }

    /**
     * 删除规则
     */
    public function delete(int $id): array
    {
        $rule = TemplateRecommendRule::find($id);
        if (!$rule) {
            return ['code' => -1, 'msg' => '规则不存在'];
        }
        $rule->delete();
        $this->clearCache();
        return ['code' => 0, 'msg' => '删除成功'];
    }

    /**
     * 切换状态
     */
    public function toggleStatus(int $id): array
    {
        $rule = TemplateRecommendRule::find($id);
        if (!$rule) {
            return ['code' => -1, 'msg' => '规则不存在'];
        }
        $rule->status = $rule->status === 1 ? 0 : 1;
        $rule->save();
        $this->clearCache();
        return ['code' => 0, 'msg' => '状态已切换', 'data' => ['status' => $rule->status]];
    }

    /**
     * 获取效果统计
     */
    public function getStats(int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $overview = TemplateRecommendStats::where('stat_date', '>=', $startDate)
            ->field([
                'SUM(impression_count) as total_impressions',
                'SUM(click_count) as total_clicks',
                'SUM(install_count) as total_installs',
            ])
            ->find();
        $overviewData = $overview ? $overview->toArray() : ['total_impressions' => 0, 'total_clicks' => 0, 'total_installs' => 0];
        $overviewData['ctr'] = $overviewData['total_impressions'] > 0
            ? round($overviewData['total_clicks'] / $overviewData['total_impressions'] * 100, 2) : 0;
        $overviewData['cvr'] = $overviewData['total_clicks'] > 0
            ? round($overviewData['total_installs'] / $overviewData['total_clicks'] * 100, 2) : 0;

        $daily = TemplateRecommendStats::where('stat_date', '>=', $startDate)
            ->field(['stat_date', 'SUM(impression_count) as impressions',
                     'SUM(click_count) as clicks', 'SUM(install_count) as installs'])
            ->group('stat_date')->order('stat_date', 'asc')->select()->toArray();

        $byPosition = TemplateRecommendStats::where('stat_date', '>=', $startDate)
            ->field(['position', 'SUM(impression_count) as impressions',
                     'SUM(click_count) as clicks', 'SUM(install_count) as installs'])
            ->group('position')->select()->toArray();

        $byAbGroup = TemplateRecommendStats::where('stat_date', '>=', $startDate)
            ->field(['ab_group', 'SUM(impression_count) as impressions',
                     'SUM(click_count) as clicks', 'SUM(install_count) as installs'])
            ->group('ab_group')->select()->toArray();

        $topTemplates = TemplateRecommendStats::where('stat_date', '>=', $startDate)
            ->field(['template_id', 'SUM(impression_count) as impressions',
                     'SUM(click_count) as clicks', 'SUM(install_count) as installs'])
            ->group('template_id')->order('installs', 'desc')->limit(10)->select()->toArray();

        return [
            'overview'    => $overviewData,
            'daily'       => $daily,
            'by_position' => $byPosition,
            'by_ab_group' => $byAbGroup,
            'top_templates' => $topTemplates,
        ];
    }

    /**
     * 清除缓存
     */
    protected function clearCache(): void
    {
        Cache::clear();
    }
}
