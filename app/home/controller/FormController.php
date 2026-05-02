<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\FormService;

/**
 * 前台表单提交控制器
 */
class FormController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 提交表单
     */
    public function submit()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        try {
            FormService::submit($this->request->post());
            return json(['code' => 0, 'msg' => '提交成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
