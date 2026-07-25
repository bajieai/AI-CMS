<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\MemberTemplateService;

class MyTemplateController extends FrontBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $memberId = $this->memberInfo['id'] ?? 0;
        if ($memberId <= 0) {
            return redirect('/member/login');
        }
        $service = new MemberTemplateService();
        $purchased = $service->getPurchasedTemplates($memberId);
        $favorited = $service->getFavoritedTemplates($memberId);
        $this->assign('purchased', $purchased);
        $this->assign('favorited', $favorited);
        return $this->view('/my_templates');
    }

    public function install(int $storeId)
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }
        $memberId = $this->memberInfo['id'] ?? 0;
        if ($memberId <= 0) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }
        $service = new MemberTemplateService();
        $result = $service->installPurchasedTemplate($memberId, $storeId);
        if ($result) {
            return json(['code' => 0, 'msg' => '安装成功']);
        }
        return json(['code' => 1, 'msg' => '安装失败']);
    }
}
