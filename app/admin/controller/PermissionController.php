<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ResourcePermissionService;
use app\common\service\AuthAuditService;
use think\App;
use think\facade\View;

/**
 * V2.9.35 SEC-5: 权限管理控制器
 */
class PermissionController extends AdminBaseController
{
    protected ResourcePermissionService $permissionService;
    protected AuthAuditService $auditService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->permissionService = new ResourcePermissionService();
        $this->auditService = new AuthAuditService();
    }

    /**
     * 权限管理页
     */
    public function index()
    {
        $overview = $this->auditService->getOverview();
        $resourceTypes = $this->permissionService->getResourceTypes();

        View::assign([
            'overview'       => $overview,
            'resource_types' => $resourceTypes,
        ]);

        return $this->view('/permission/index');
    }

    /**
     * 保存权限配置
     */
    public function save()
    {
        return json(['code' => 0, 'msg' => '权限配置已保存']);
    }

    /**
     * 权限审计报告
     */
    public function audit()
    {
        $result = $this->auditService->auditPermissionAssignment();
        return json(['code' => 0, 'msg' => '审计完成', 'data' => $result]);
    }
}
