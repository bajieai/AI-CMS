<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AiReport;
use app\common\model\Comment;
use app\common\model\Content;
use app\common\model\Member;
use app\common\model\MemberFavorite;
use app\common\model\MemberLike;
use app\common\model\PaidOrder;
use app\common\model\VisitLog;
use think\facade\Log;

/**
 * AI数据分析报告服务 - V2.9.1 M9
 *
 * 从Model层直接采集数据，不跨应用调Controller
 * 支持日报(daily)/周报(weekly)/月报(monthly)/手动(manual)
 */
class AiReportService
{
    /**
     * 生成报告（异步入口）
     *
     * @param string $type 报告类型 daily/weekly/monthly/manual
     * @param int    $startTime 统计开始时间戳
     * @param int    $endTime   统计结束时间戳
     * @return int 报告ID
     */
    public static function generate(string $type, int $startTime, int $endTime): int
    {
        // 检查是否已存在
        $exists = AiReport::getByPeriod($type, $startTime, $endTime);
        if ($exists && $exists->status == AiReport::STATUS_GENERATING) {
            return $exists->id;
        }

        $title = match ($type) {
            'daily'   => date('Y-m-d', $endTime) . ' 运营日报',
            'weekly'  => date('Y-m-d', $startTime) . ' ~ ' . date('Y-m-d', $endTime) . ' 运营周报',
            'monthly' => date('Y年m月', $endTime) . ' 运营月报',
            default   => date('Y-m-d H:i', $endTime) . ' 运营分析报告',
        };

        if ($exists) {
            $report = $exists;
            $report->status = AiReport::STATUS_GENERATING;
            $report->save();
        } else {
            $report = AiReport::create([
                'type'         => $type,
                'title'        => $title,
                'period_start' => $startTime,
                'period_end'   => $endTime,
                'status'       => AiReport::STATUS_GENERATING,
                'create_time'  => time(),
                'update_time'  => time(),
            ]);
        }

        // 异步执行：直接在当前请求中执行（后台定时任务/手动触发）
        // 如需纯异步，可放入队列或CLI任务
        try {
            self::doGenerate($report->id);
        } catch (\Throwable $e) {
            Log::error("[AiReportService] 报告生成异常 id={$report->id}: " . $e->getMessage());
            $report->status = AiReport::STATUS_FAILED;
            $report->save();
        }

        return $report->id;
    }

    /**
     * 执行报告生成
     */
    public static function doGenerate(int $reportId): void
    {
        $report = AiReport::find($reportId);
        if (!$report) {
            throw new \Exception('报告不存在');
        }

        $start = (int) $report->period_start;
        $end   = (int) $report->period_end;

        // 1. 数据采集（Model层直接查询）
        $rawData = self::collectData($start, $end);

        // 2. 异常检测（基于简单统计偏离）
        $anomalies = self::detectAnomalies($rawData, $start, $end);

        // 3. 构建Prompt
        $prompt = match ($report->type) {
            'daily'   => AiReportPromptBuilder::buildDaily($rawData),
            'weekly'  => AiReportPromptBuilder::buildWeekly($rawData),
            'monthly' => AiReportPromptBuilder::buildMonthly($rawData),
            default   => AiReportPromptBuilder::buildDaily($rawData),
        };

        // 4. 调用AI生成
        $aiResult = self::callAi($prompt);

        // 5. 解析并保存
        $parsed = self::parseAiResponse($aiResult);

        $report->raw_data        = $rawData;
        $report->summary         = $parsed['summary'] ?? '';
        $report->findings        = $parsed['findings'] ?? [];
        $report->anomalies       = array_merge($anomalies, $parsed['anomalies'] ?? []);
        $report->recommendations = $parsed['recommendations'] ?? [];
        $report->sections        = $parsed['sections'] ?? [];
        $report->status          = AiReport::STATUS_COMPLETED;
        $report->update_time     = time();
        $report->save();

        // 6. 邮件推送（如配置）
        self::sendEmailNotification($report);
    }

    /**
     * 数据采集 — 从Model层直接查询
     */
    protected static function collectData(int $start, int $end): array
    {
        $data = [];

        // 流量数据
        try {
            $data['traffic'] = [
                'pv'        => VisitLog::whereBetween('visit_time', [$start, $end])->count(),
                'uv'        => VisitLog::whereBetween('visit_time', [$start, $end])->group('ip')->count(),
                'ip_count'  => VisitLog::whereBetween('visit_time', [$start, $end])->distinct('ip')->count(),
                'avg_pages' => 0,
            ];
            if ($data['traffic']['uv'] > 0) {
                $data['traffic']['avg_pages'] = round($data['traffic']['pv'] / $data['traffic']['uv'], 2);
            }
        } catch (\Throwable $e) {
            $data['traffic'] = [];
        }

        // 内容数据
        try {
            $newContents = Content::where('create_time', 'between', [$start, $end])->select();
            $data['content'] = [
                'new_count'    => count($newContents),
                'total_count'  => Content::count(),
                'total_views'  => Content::sum('views'),
                'top_contents' => Content::where('status', 2)
                    ->order('views', 'desc')
                    ->limit(3)
                    ->field('title,views')
                    ->select()
                    ->toArray(),
            ];
        } catch (\Throwable $e) {
            $data['content'] = [];
        }

        // 会员数据
        try {
            $data['member'] = [
                'new_count'    => Member::where('create_time', 'between', [$start, $end])->count(),
                'total_count'  => Member::count(),
                'active_count' => Member::where('last_login_time', 'between', [$start, $end])->count(),
            ];
        } catch (\Throwable $e) {
            $data['member'] = [];
        }

        // 互动数据
        try {
            $data['interaction'] = [
                'new_comments'  => Comment::where('create_time', 'between', [$start, $end])->count(),
                'new_likes'     => MemberLike::where('create_time', 'between', [$start, $end])->count(),
                'new_favorites' => MemberFavorite::where('create_time', 'between', [$start, $end])->count(),
            ];
        } catch (\Throwable $e) {
            $data['interaction'] = [];
        }

        // 订单数据
        try {
            $data['order'] = [
                'new_count'  => PaidOrder::where('create_time', 'between', [$start, $end])->count(),
                'total_amount' => PaidOrder::where('create_time', 'between', [$start, $end])->sum('amount'),
                'new_vip'    => Member::where('vip_expire_time', '>', time())
                    ->where('create_time', 'between', [$start, $end])
                    ->count(),
            ];
        } catch (\Throwable $e) {
            $data['order'] = [];
        }

        return $data;
    }

    /**
     * 异常检测 — 基于简单统计偏离
     */
    protected static function detectAnomalies(array $data, int $start, int $end): array
    {
        $anomalies = [];
        $periodDays = max(1, (int) round(($end - $start) / 86400));

        // 对比上一周期
        $prevStart = $start - ($end - $start);
        $prevEnd   = $start;

        // 流量异常
        if (!empty($data['traffic'])) {
            $prevPv = VisitLog::whereBetween('visit_time', [$prevStart, $prevEnd])->count();
            $currPv = $data['traffic']['pv'] ?? 0;
            if ($prevPv > 0) {
                $deviation = (($currPv - $prevPv) / $prevPv) * 100;
                if (abs($deviation) > 30) {
                    $anomalies[] = [
                        'metric'      => 'PV',
                        'value'       => $currPv,
                        'expected'    => $prevPv,
                        'deviation'   => ($deviation > 0 ? '+' : '') . round($deviation, 1) . '%',
                        'severity'    => abs($deviation) > 50 ? 'high' : 'medium',
                        'description' => "PV较上周期" . ($deviation > 0 ? '上升' : '下降') . round(abs($deviation), 1) . "%",
                    ];
                }
            }
        }

        // 内容异常
        if (!empty($data['content'])) {
            $prevNew = Content::where('create_time', 'between', [$prevStart, $prevEnd])->count();
            $currNew = $data['content']['new_count'] ?? 0;
            if ($prevNew > 0 && $currNew == 0) {
                $anomalies[] = [
                    'metric'      => '新增内容',
                    'value'       => 0,
                    'expected'    => $prevNew,
                    'deviation'   => '-100%',
                    'severity'    => 'medium',
                    'description' => '本周期无新增内容，内容更新可能停滞',
                ];
            }
        }

        return $anomalies;
    }

    /**
     * 调用AI生成报告
     */
    protected static function callAi(string $prompt): string
    {
        try {
            $aiService = new AiService();
            // 使用长文本模型，max_tokens设置较大以容纳完整JSON
            $response = $aiService->chat($prompt, [
                'model'      => 'deepseek-chat',
                'max_tokens' => 4096,
                'temperature'=> 0.3,
            ]);
            return $response['content'] ?? '';
        } catch (\Throwable $e) {
            Log::error('[AiReportService] AI调用失败: ' . $e->getMessage());
            throw new \Exception('AI调用失败: ' . $e->getMessage());
        }
    }

    /**
     * 解析AI返回的JSON
     */
    protected static function parseAiResponse(string $response): array
    {
        if (empty($response)) {
            return [];
        }

        // 尝试提取JSON代码块
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonStr = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $response, $matches)) {
            $jsonStr = $matches[0];
        } else {
            $jsonStr = $response;
        }

        $decoded = json_decode($jsonStr, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('[AiReportService] AI返回JSON解析失败: ' . json_last_error_msg());
            // 降级：返回原始文本作为summary
            return [
                'summary' => mb_substr(strip_tags($response), 0, 200),
                'findings' => [],
                'anomalies' => [],
                'recommendations' => [],
                'sections' => [],
            ];
        }

        return $decoded;
    }

    /**
     * 邮件推送通知
     */
    protected static function sendEmailNotification(AiReport $report): void
    {
        $emailEnabled = (bool) ConfigService::get('report_email_notify', 0);
        if (!$emailEnabled || $report->status != AiReport::STATUS_COMPLETED) {
            return;
        }

        $emails = ConfigService::get('report_email_list', '');
        if (empty($emails)) {
            return;
        }

        try {
            $mailService = new EmailService();
            $toList = array_map('trim', explode(',', $emails));
            foreach ($toList as $to) {
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $mailService->send($to, $report->title, $report->summary ?: '报告已生成');
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[AiReportService] 邮件推送失败: ' . $e->getMessage());
        }
    }
}
