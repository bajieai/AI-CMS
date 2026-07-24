<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\BaseController;
use app\common\service\MobileOnboardingService;

/**
 * 移动端安装引导 — V2.9.33 CUS3-3
 */
class MobileOnboardingController extends BaseController
{
    /**
     * 获取引导配置
     */
    public function config()
    {
        $service = new MobileOnboardingService();
        $config = $service->getGuideConfig();
        return json(['success' => true, 'data' => $config]);
    }

    /**
     * 完成引导
     */
    public function complete()
    {
        $memberId = (int) $this->request->post('member_id', 0);
        if (!$memberId) {
            return json(['success' => false, 'message' => '用户未登录']);
        }

        $service = new MobileOnboardingService();
        $result = $service->completeGuide($memberId);
        return json($result);
    }

    /**
     * 检查引导状态
     */
    public function status()
    {
        $memberId = (int) $this->request->get('member_id', 0);
        if (!$memberId) {
            return json(['success' => false, 'message' => '用户未登录']);
        }

        $service = new MobileOnboardingService();
        $completed = $service->hasCompleted($memberId);
        return json(['success' => true, 'completed' => $completed]);
    }
}
