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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Db;

/**
 * AI生成统计看板 - V2.8新增
 */
class AiStatController extends AdminBaseController
{
    /**
     * AI统计看板页面
     */
    public function index()
    {
        return $this->view('/ai_stat_index');
    }

    /**
     * 生成总量趋势
     */
    public function getGenerateTrend()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('ai_log')
            ->field('FROM_UNIXTIME(create_time, "%Y-%m-%d") as date, COUNT(*) as count, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success')
            ->where('create_time', '>=', $startDate)
            ->group('date')
            ->order('date')
            ->select()
            ->toArray();
        
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 供应商消耗占比
     */
    public function getProviderStats()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('ai_log')
            ->alias('a')
            ->join('ai_model m', 'a.model_id = m.id', 'LEFT')
            ->field('COALESCE(m.provider, "unknown") as provider, COUNT(*) as count, SUM(a.duration_ms) as total_duration')
            ->where('a.create_time', '>=', $startDate)
            ->group('m.provider')
            ->select()
            ->toArray();
        
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 任务类型分布
     */
    public function getTaskTypeStats()
    {
        $days = min((int) $this->request->get('days', 7), 30);
        $startDate = strtotime("-{$days} days");
        
        $data = Db::name('ai_log')
            ->field('task_type, COUNT(*) as count')
            ->where('create_time', '>=', $startDate)
            ->group('task_type')
            ->select()
            ->toArray();
        
        return json(['code' => 0, 'data' => $data]);
    }

    /**
     * 质量分布（基于quality_score）
     */
    public function getQualityDistribution()
    {
        $ranges = [
            '优秀(80-100)' => [80, 100],
            '良好(60-79)' => [60, 79],
            '中等(40-59)' => [40, 59],
            '较差(<40)' => [0, 39],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = Db::name('content')
                ->where('quality_score', '>=', $range[0])
                ->where('quality_score', '<=', $range[1])
                ->count();
            $result[] = ['name' => $label, 'value' => $count];
        }
        
        return json(['code' => 0, 'data' => $result]);
    }
}
