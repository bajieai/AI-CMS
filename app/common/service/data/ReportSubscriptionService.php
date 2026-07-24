<?php
declare(strict_types=1);

namespace app\common\service\data;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

/**
 * 数据报告订阅服务 - V2.9.40 DATA-DEEP2-3
 *
 * 报告定时订阅：创建→调度→推送(邮件/站内/微信)
 * 支持日报/周报/月报/自定义周期
 */
class ReportSubscriptionService
{
    private const CACHE_TAG = 'report_subscription';

    /** 频率定义 */
    private const FREQUENCIES = [
        'daily'   => ['label' => '每日报告', 'cron' => '0 8 * * *'],
        'weekly'  => ['label' => '每周报告', 'cron' => '0 8 * * 1'],
        'monthly' => ['label' => '每月报告', 'cron' => '0 8 1 * *'],
        'custom'  => ['label' => '自定义周期', 'cron' => ''],
    ];

    /** 推送通道 */
    private const CHANNELS = ['email', 'notification', 'wechat'];

    /**
     * 创建订阅
     */
    public function create(array $data): int
    {
        $frequency = $data['frequency'] ?? 'daily';
        $cronExpr = self::FREQUENCIES[$frequency]['cron'] ?? '0 8 * * *';
        if ($frequency === 'custom' && !empty($data['cron'])) {
            $cronExpr = $data['cron'];
        }

        $id = Db::name('data_report_subscription')->insertGetId([
            'user_id'       => $data['user_id'] ?? 0,
            'report_type'   => $data['report_type'] ?? 'dashboard',
            'report_id'     => $data['report_id'] ?? 0,
            'frequency'     => $frequency,
            'cron'          => $cronExpr,
            'push_channels' => json_encode($data['push_channels'] ?? ['email']),
            'push_config'   => json_encode($data['push_config'] ?? []),
            'status'        => 1,
            'last_run_at'   => 0,
            'next_run_at'   => $this->calcNextRun($cronExpr),
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 更新订阅
     */
    public function update(int $id, array $data): bool
    {
        $update = [];
        $fields = ['frequency', 'push_channels', 'push_config', 'status'];
        foreach ($fields as $f) {
            if (isset($data[$f])) $update[$f] = is_array($data[$f]) ? json_encode($data[$f]) : $data[$f];
        }
        if (isset($data['cron'])) {
            $update['cron'] = $data['cron'];
            $update['next_run_at'] = $this->calcNextRun($data['cron']);
        }
        $update['updated_at'] = time();

        Db::name('data_report_subscription')->where('id', $id)->update($update);
        Cache::clear();
        return true;
    }

    /**
     * 删除订阅
     */
    public function delete(int $id): bool
    {
        Db::name('data_report_subscription')->where('id', $id)->delete();
        Cache::clear();
        return true;
    }

    /**
     * 获取用户订阅列表
     */
    public function getUserSubscriptions(int $userId): array
    {
        return Db::name('data_report_subscription')
            ->where('user_id', $userId)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取待执行的订阅（调度器调用）
     */
    public function getPendingSubscriptions(): array
    {
        return Db::name('data_report_subscription')
            ->where('status', 1)
            ->where('next_run_at', '<=', time())
            ->select()
            ->toArray();
    }

    /**
     * 执行订阅推送
     */
    public function executeSubscription(int $subscriptionId): bool
    {
        $sub = Db::name('data_report_subscription')->find($subscriptionId);
        if (!$sub) return false;

        // 生成报告数据
        $reportData = $this->generateReportData($sub);

        // 推送
        $channels = json_decode($sub['push_channels'], true) ?: ['email'];
        foreach ($channels as $channel) {
            $this->pushReport($channel, $sub, $reportData);
        }

        // 更新执行记录
        Db::name('data_report_subscription')->where('id', $subscriptionId)->update([
            'last_run_at' => time(),
            'next_run_at' => $this->calcNextRun($sub['cron']),
            'updated_at'  => time(),
        ]);

        Log::info('报告订阅推送完成: subscription_id=' . $subscriptionId);
        return true;
    }

    /**
     * 生成报告数据
     */
    private function generateReportData(array $sub): array
    {
        // 生成报告数据 — 从缓存获取模块数据
        $reportType = $sub['report_type'];
        $reportId = $sub['report_id'];

        $moduleData = Cache::remember('module_data_' . $reportId, function () use ($reportId) {
            return Db::name('data_dashboard')->where('id', $reportId)->find() ?: [];
        }, 300);

        return $moduleData;
    }

    /**
     * 推送报告到指定通道
     */
    private function pushReport(string $channel, array $sub, array $data): void
    {
        switch ($channel) {
            case 'email':
                // 邮件推送 — 调用现有MailService
                try {
                    $pushConfig = json_decode($sub['push_config'], true) ?: [];
                    $email = $pushConfig['email'] ?? '';
                    if (!empty($email)) {
                        $mailService = new \app\common\service\MailTemplateService();
                        $mailService->send($email, '数据报告订阅', 'report_subscription', $data);
                    }
                } catch (\Exception $e) {
                    Log::error('报告订阅邮件推送失败: ' . $e->getMessage());
                }
                break;

            case 'notification':
                // 站内通知推送
                try {
                    Db::name('notification')->insert([
                        'user_id'     => $sub['user_id'],
                        'type'        => 'report_subscription',
                        'title'       => '数据报告订阅推送',
                        'content'     => json_encode($data),
                        'status'      => 0,
                        'created_at'  => time(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('报告订阅站内推送失败: ' . $e->getMessage());
                }
                break;

            case 'wechat':
                Log::info('微信推送暂未集成，需配置微信模板消息');
                break;
        }
    }

    /**
     * 计算下次执行时间（简化版cron解析）
     */
    private function calcNextRun(string $cron): int
    {
        // 简化处理：基于频率直接计算
        $parts = explode(' ', $cron);
        if (count($parts) >= 5) {
            $minute = (int) $parts[0];
            $hour   = (int) $parts[1];

            // 每日
            if ($parts[2] === '*' && $parts[3] === '*' && $parts[4] === '*') {
                return strtotime('tomorrow ' . $hour . ':' . $minute);
            }
            // 每周
            if ($parts[4] !== '*') {
                $days = explode(',', $parts[4]);
                $dayMap = ['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '0' => 'Sun'];
                $nextDay = $dayMap[$days[0]] ?? 'Mon';
                return strtotime('next ' . $nextDay . ' ' . $hour . ':' . $minute);
            }
            // 每月
            if ($parts[2] !== '*') {
                return strtotime('+' . (int)$parts[2] . ' days ' . $hour . ':' . $minute);
            }
        }
        return time() + 86400; // 默认24小时后
    }

    /**
     * 获取频率选项
     */
    public function getFrequencyOptions(): array
    {
        return self::FREQUENCIES;
    }

    /**
     * 获取推送通道选项
     */
    public function getChannelOptions(): array
    {
        return self::CHANNELS;
    }
}
