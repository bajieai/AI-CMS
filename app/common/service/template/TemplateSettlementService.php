<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateOrder;
use app\common\model\TemplateSettlement;
use app\common\model\TemplateSettlementRule;
use app\common\model\TemplateStore;
use app\common\model\TemplateWithdraw;
use app\common\model\Member;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 模板结算管理服务 — V2.9.28 M-7
 *
 * 提现仅做后台流程，线下打款（用户确认Q3）
 */
class TemplateSettlementService
{
    private const CACHE_TAG = 'template_settlement';

    /**
     * 获取开发者收益明细
     */
    public function getEarningsDetail(int $developerId, array $params = [], int $page = 1, int $limit = 20): array
    {
        // 获取开发者创建的模板
        $templateIds = TemplateStore::where('author_id', $developerId)->column('id');
        if (empty($templateIds)) {
            return ['list' => [], 'total' => 0, 'page' => $page, 'limit' => $limit];
        }

        $query = TemplateOrder::with('template')
            ->whereIn('template_id', $templateIds)
            ->where('status', TemplateOrder::STATUS_PAID);

        if (!empty($params['start_date'])) {
            $query->where('pay_time', '>=', strtotime($params['start_date']));
        }
        if (!empty($params['end_date'])) {
            $query->where('pay_time', '<', strtotime($params['end_date']) + 86400);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 计算每笔的作者收益
        $rule = TemplateSettlementRule::getForDeveloper($developerId);
        $commissionRate = $rule['commission_rate'] ?? 30;

        foreach ($list as &$row) {
            $row['commission_rate'] = $commissionRate;
            $row['commission_amount'] = round($row['pay_amount'] * $commissionRate / 100, 2);
            $row['author_earning'] = round($row['pay_amount'] - $row['commission_amount'], 2);
        }

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取收益汇总
     */
    public function getEarningsSummary(int $developerId): array
    {
        $cacheKey = 'earnings_summary_' . $developerId;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function() use ($developerId) {
            $templateIds = TemplateStore::where('author_id', $developerId)->column('id');
            if (empty($templateIds)) {
                return [
                    'total_revenue' => 0,
                    'total_commission' => 0,
                    'total_earning' => 0,
                    'withdrawn' => 0,
                    'available' => 0,
                ];
            }

            $rule = TemplateSettlementRule::getForDeveloper($developerId);
            $commissionRate = $rule['commission_rate'] ?? 30;

            $totalRevenue = TemplateOrder::whereIn('template_id', $templateIds)
                ->where('status', TemplateOrder::STATUS_PAID)
                ->sum('pay_amount');

            $totalCommission = round($totalRevenue * $commissionRate / 100, 2);
            $totalEarning = round($totalRevenue - $totalCommission, 2);

            // 已提现金额
            $withdrawn = TemplateWithdraw::where('developer_id', $developerId)
                ->whereIn('status', [TemplateWithdraw::STATUS_PROCESSING, TemplateWithdraw::STATUS_COMPLETED])
                ->sum('amount');

            // 待审核提现
            $pendingWithdraw = TemplateWithdraw::where('developer_id', $developerId)
                ->where('status', TemplateWithdraw::STATUS_PENDING)
                ->sum('amount');

            return [
                'total_revenue' => (float)$totalRevenue,
                'total_commission' => $totalCommission,
                'total_earning' => $totalEarning,
                'withdrawn' => (float)$withdrawn,
                'pending_withdraw' => (float)$pendingWithdraw,
                'available' => round($totalEarning - $withdrawn - $pendingWithdraw, 2),
                'min_withdraw' => $rule['min_withdraw'] ?? 100,
            ];
        }, 300);
    }

    /**
     * 创建提现申请
     */
    public function createWithdraw(int $developerId, float $amount, array $accountInfo): array
    {
        $summary = $this->getEarningsSummary($developerId);
        if ($amount > $summary['available']) {
            return ['success' => false, 'message' => '提现金额超过可用余额'];
        }
        if ($amount < $summary['min_withdraw']) {
            return ['success' => false, 'message' => '提现金额低于最低限额' . $summary['min_withdraw']];
        }

        $withdraw = TemplateWithdraw::create([
            'developer_id' => $developerId,
            'amount' => $amount,
            'fee' => 0, // 暂无手续费
            'actual_amount' => $amount,
            'account_info' => json_encode($accountInfo, JSON_UNESCAPED_UNICODE),
            'status' => TemplateWithdraw::STATUS_PENDING,
        ]);

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '提现申请已提交', 'withdraw_id' => $withdraw->id];
    }

    /**
     * 审核提现（管理员）
     * Q3: 提现仅做后台流程，线下打款
     */
    public function approveWithdraw(int $withdrawId, int $adminId, string $remark = ''): array
    {
        $withdraw = TemplateWithdraw::find($withdrawId);
        if (!$withdraw) {
            return ['success' => false, 'message' => '提现记录不存在'];
        }
        if ($withdraw->status != TemplateWithdraw::STATUS_PENDING) {
            return ['success' => false, 'message' => '提现已处理'];
        }

        $withdraw->status = TemplateWithdraw::STATUS_PROCESSING;
        $withdraw->admin_remark = $remark;
        $withdraw->process_time = time();
        $withdraw->save();

        Log::info('[TemplateSettlement] 提现审核通过: withdraw_id=' . $withdrawId . ', admin=' . $adminId);
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '提现已审核，请线下打款后确认到账'];
    }

    /**
     * 确认到账
     */
    public function confirmWithdraw(int $withdrawId, int $adminId): array
    {
        $withdraw = TemplateWithdraw::find($withdrawId);
        if (!$withdraw) {
            return ['success' => false, 'message' => '提现记录不存在'];
        }
        if ($withdraw->status != TemplateWithdraw::STATUS_PROCESSING) {
            return ['success' => false, 'message' => '提现状态不支持确认'];
        }

        $withdraw->status = TemplateWithdraw::STATUS_COMPLETED;
        $withdraw->confirm_time = time();
        $withdraw->save();

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '已确认到账'];
    }

    /**
     * 驳回提现
     */
    public function rejectWithdraw(int $withdrawId, int $adminId, string $remark): array
    {
        $withdraw = TemplateWithdraw::find($withdrawId);
        if (!$withdraw) {
            return ['success' => false, 'message' => '提现记录不存在'];
        }
        if ($withdraw->status != TemplateWithdraw::STATUS_PENDING) {
            return ['success' => false, 'message' => '提现已处理'];
        }

        $withdraw->status = TemplateWithdraw::STATUS_REJECTED;
        $withdraw->admin_remark = $remark;
        $withdraw->process_time = time();
        $withdraw->save();

        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '提现已驳回'];
    }

    /**
     * 获取提现列表
     */
    public function getWithdrawList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $query = new TemplateWithdraw();

        if (!empty($params['status']) || $params['status'] === '0') {
            $query->where('status', (int)$params['status']);
        }
        if (!empty($params['developer_id'])) {
            $query->where('developer_id', (int)$params['developer_id']);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 保存结算规则
     */
    public function saveSettlementRule(int $developerId, array $data): array
    {
        $rule = TemplateSettlementRule::where('developer_id', $developerId)->find();
        if ($rule) {
            $rule->save($data);
        } else {
            $data['developer_id'] = $developerId;
            TemplateSettlementRule::create($data);
        }
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '结算规则已保存'];
    }

    /**
     * 月度对账报表
     */
    public function getMonthlyReport(int $year, int $month): array
    {
        $startTime = strtotime("{$year}-{$month}-01 00:00:00");
        $endTime = strtotime("+1 month", $startTime) - 1;

        // 平台收入
        $platformRevenue = TemplateOrder::where('status', TemplateOrder::STATUS_PAID)
            ->whereBetweenTime('pay_time', $startTime, $endTime)
            ->sum('pay_amount');

        // 退款
        $refundAmount = TemplateRefund::where('status', TemplateRefund::STATUS_APPROVED)
            ->whereBetweenTime('process_time', $startTime, $endTime)
            ->sum('amount');

        // 按开发者分组
        $developerStats = TemplateOrder::alias('o')
            ->join('template_store s', 'o.template_id = s.id')
            ->where('o.status', TemplateOrder::STATUS_PAID)
            ->whereBetweenTime('o.pay_time', $startTime, $endTime)
            ->field('s.author_id as developer_id, COUNT(*) as order_count, SUM(o.pay_amount) as revenue')
            ->group('s.author_id')
            ->select()
            ->toArray();

        foreach ($developerStats as &$stat) {
            $rule = TemplateSettlementRule::getForDeveloper((int)$stat['developer_id']);
            $commissionRate = $rule['commission_rate'] ?? 30;
            $stat['commission'] = round($stat['revenue'] * $commissionRate / 100, 2);
            $stat['earning'] = round($stat['revenue'] - $stat['commission'], 2);
        }

        return [
            'year' => $year,
            'month' => $month,
            'platform_revenue' => (float)$platformRevenue,
            'refund_amount' => (float)$refundAmount,
            'net_revenue' => round((float)$platformRevenue - (float)$refundAmount, 2),
            'developer_stats' => $developerStats,
        ];
    }
}
