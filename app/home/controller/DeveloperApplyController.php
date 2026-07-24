<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\home\service\DeveloperApplyService;

/**
 * 开发者申请前台控制器 - V2.9.29 Sprint D-1
 */
class DeveloperApplyController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function apply()
    {
        $userId = $this->memberInfo['id'] ?? 0;
        if ($userId <= 0) {
            return redirect('/member/login?redirect=' . urlencode('/developer/apply'));
        }

        $service = new DeveloperApplyService();
        $developer = $service->getDeveloperByUserId($userId);

        if ($this->request->isPost()) {
            if ($developer && $developer->status == 1) {
                return $this->error('您已是认证开发者');
            }
            $data = [
                'real_name' => $this->request->post('real_name', ''),
                'contact_phone' => $this->request->post('contact_phone', ''),
                'contact_email' => $this->request->post('contact_email', ''),
                'introduction' => $this->request->post('introduction', ''),
            ];
            $result = $service->apply($userId, $data);
            return $result ? $this->success('申请已提交，请等待审核') : $this->error('提交失败');
        }

        $this->assign('developer', $developer);
        return $this->view('/developer_apply');
    }

    public function panel()
    {
        $userId = $this->memberInfo['id'] ?? 0;
        if ($userId <= 0) {
            return redirect('/member/login?redirect=' . urlencode('/developer/panel'));
        }

        $service = new DeveloperApplyService();
        $developer = $service->getDeveloperByUserId($userId);
        if (!$developer || $developer->status != 1) {
            return redirect('/developer/apply');
        }

        $panelData = $service->getPanelData($userId);
        $this->assign('developer', $developer);
        $this->assign('panel_data', $panelData);
        return $this->view('/developer_panel');
    }
}
