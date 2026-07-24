<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\member\MemberBenefitsService;

/**
 * 会员权益中心控制器 — V2.9.34 MEM-6
 */
class MemberBenefitsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new MemberBenefitsService();
        $comparison = $service->getBenefitsComparison();
        $pointsBenefits = $service->getPointsBenefits();
        $usageStats = $service->getUsageStats();
        $this->assign('comparison', $comparison);
        $this->assign('pointsBenefits', $pointsBenefits);
        $this->assign('usageStats', $usageStats);
        $this->assign('menuActive', 'member_benefits');
        return $this->view('/member_benefits/index');
    }

    public function usageRecords()
    {
        $memberId = (int)$this->request->param('member_id', 0);
        $service = new MemberBenefitsService();
        $result = $service->getUsageRecords($memberId);
        return json($result);
    }
}
