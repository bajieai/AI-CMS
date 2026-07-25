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
use app\common\service\data\DataDrillService;

/**
 * 数据钻取控制器 - V2.9.39 DATA-DEEP-4
 */
class DataDrillController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 钻取首页
     */
    public function index()
    {
        $this->assign('dimensions', [
            DataDrillService::DIMENSION_TIME     => '时间维度',
            DataDrillService::DIMENSION_REGION   => '地域维度',
            DataDrillService::DIMENSION_CATEGORY => '分类维度',
            DataDrillService::DIMENSION_CHANNEL  => '渠道维度',
            DataDrillService::DIMENSION_USER     => '用户维度',
        ]);
        $this->assign('granularities', [
            DataDrillService::GRANULARITY_HOUR  => '按小时',
            DataDrillService::GRANULARITY_DAY   => '按天',
            DataDrillService::GRANULARITY_WEEK  => '按周',
            DataDrillService::GRANULARITY_MONTH => '按月',
        ]);
        return $this->view('/data_drill/index');
    }

    /**
     * API: 通用钻取
     */
    public function drill()
    {
        $dimension = $this->request->param('dimension', DataDrillService::DIMENSION_TIME);
        $params = $this->buildParams();

        $service = new DataDrillService();
        $result = $service->drill($dimension, $params);

        if ($result['success'] ?? false) {
            return $this->success('ok', $result);
        }
        return $this->error($result['msg'] ?? '钻取失败', 1, $result);
    }

    /**
     * API: 时间维度钻取
     */
    public function byTime()
    {
        $params = $this->buildParams();
        $service = new DataDrillService();
        $result = $service->drillByTime(
            $params['start_time'],
            $params['end_time'],
            $params
        );
        return $result['success'] ?? false
            ? $this->success('ok', $result)
            : $this->error($result['msg'] ?? '钻取失败');
    }

    /**
     * API: 地域维度钻取
     */
    public function byRegion()
    {
        $params = $this->buildParams();
        $service = new DataDrillService();
        $result = $service->drillByRegion(
            $params['start_time'],
            $params['end_time'],
            $params
        );
        return $result['success'] ?? false
            ? $this->success('ok', $result)
            : $this->error($result['msg'] ?? '钻取失败');
    }

    /**
     * API: 分类维度钻取
     */
    public function byCategory()
    {
        $params = $this->buildParams();
        $service = new DataDrillService();
        $result = $service->drillByCategory(
            $params['start_time'],
            $params['end_time'],
            $params
        );
        return $result['success'] ?? false
            ? $this->success('ok', $result)
            : $this->error($result['msg'] ?? '钻取失败');
    }

    /**
     * API: 渠道维度钻取
     */
    public function byChannel()
    {
        $params = $this->buildParams();
        $service = new DataDrillService();
        $result = $service->drillByChannel(
            $params['start_time'],
            $params['end_time'],
            $params
        );
        return $result['success'] ?? false
            ? $this->success('ok', $result)
            : $this->error($result['msg'] ?? '钻取失败');
    }

    /**
     * API: 用户维度钻取
     */
    public function byUser()
    {
        $params = $this->buildParams();
        $service = new DataDrillService();
        $result = $service->drillByUser(
            $params['start_time'],
            $params['end_time'],
            $params
        );
        return $result['success'] ?? false
            ? $this->success('ok', $result)
            : $this->error($result['msg'] ?? '钻取失败');
    }

    /**
     * API: 多维度交叉钻取
     */
    public function cross()
    {
        $dimensions = $this->request->param('dimensions', []);
        $params = $this->buildParams();

        if (empty($dimensions) || !is_array($dimensions)) {
            return $this->error('请指定至少一个钻取维度');
        }

        $service = new DataDrillService();
        $results = [];
        foreach ($dimensions as $dim) {
            $results[$dim] = $service->drill($dim, $params);
        }

        return $this->success('ok', $results);
    }

    /**
     * API: 清除钻取缓存
     */
    public function clearCache()
    {
        DataDrillService::clearCache();
        return $this->success('缓存已清除');
    }

    // ========================================================================
    // 工具方法
    // ========================================================================

    /**
     * 从请求参数构建钻取参数
     */
    private function buildParams(): array
    {
        $days = (int) $this->request->param('days', 0);
        $startTime = $this->request->param('start_time');
        $endTime = $this->request->param('end_time');

        if (!empty($startTime) && !is_numeric($startTime)) {
            $startTime = strtotime($startTime);
        }
        if (!empty($endTime) && !is_numeric($endTime)) {
            $endTime = strtotime($endTime);
        }

        if (empty($startTime)) {
            $startTime = $days > 0 ? strtotime("-{$days} days") : strtotime('-30 days');
        }
        if (empty($endTime)) {
            $endTime = time();
        }

        return [
            'start_time'      => (int) $startTime,
            'end_time'        => (int) $endTime,
            'granularity'     => $this->request->param('granularity', DataDrillService::GRANULARITY_DAY),
            'metric'          => $this->request->param('metric', 'pv'),
            'source_table'    => $this->request->param('source_table', 'visit_log'),
            'time_field'      => $this->request->param('time_field', 'visit_time'),
            'region_field'    => $this->request->param('region_field', 'region'),
            'limit'           => (int) $this->request->param('limit', 20),
        ];
    }
}
