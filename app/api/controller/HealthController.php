<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 SYS-ROBUST-4: 健康检查端点
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\api\controller;

use app\api\controller\BaseController;
use app\common\service\system\HealthCheckService;

/**
 * 健康检查端点 - V2.9.39 SYS-ROBUST-4
 * /api/health, /api/health/check, /api/health/readiness, /api/health/liveness
 */
class HealthController extends BaseController
{
    protected HealthCheckService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new HealthCheckService();
    }

    /**
     * 健康概览 GET /api/health
     */
    public function index(): \think\Response
    {
        $result = $this->service->checkAll();

        $httpStatus = match ($result['status']) {
            HealthCheckService::STATUS_HEALTHY   => 200,
            HealthCheckService::STATUS_DEGRADED  => 200,
            HealthCheckService::STATUS_UNHEALTHY => 503,
        };

        return json($result, $httpStatus);
    }

    /**
     * 详细检查 GET /api/health/check
     */
    public function check(): \think\Response
    {
        $result = $this->service->checkAll();

        return $this->success($result);
    }

    /**
     * 就绪检查 GET /api/health/readiness
     * 用于K8s Readiness Probe
     */
    public function readiness(): \think\Response
    {
        $result = $this->service->readiness();

        $httpStatus = $result['status'] === HealthCheckService::STATUS_HEALTHY ? 200 : 503;

        return json($result, $httpStatus);
    }

    /**
     * 存活检查 GET /api/health/liveness
     * 用于K8s Liveness Probe
     */
    public function liveness(): \think\Response
    {
        $result = $this->service->liveness();

        return json($result, 200);
    }
}
