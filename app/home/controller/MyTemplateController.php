<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\home\MyTemplateService;

/**
 * V2.9.27 U-7: 已购模板管理控制器
 */
class MyTemplateController extends FrontBaseController
{
    public function index()
    {
        $member = $this->getMember();
        if (!$member) {
            return redirect('/member/login');
        }

        $templates = MyTemplateService::getMyTemplates($member->id);
        $this->assign('templates', $templates);
        return $this->fetch('/my_templates');
    }
}