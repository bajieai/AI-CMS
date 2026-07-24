<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\system\SmsService;
use think\facade\Json;

/**
 * 短信服务控制器
 * V2.9.38 SYS-INTEG-3
 */
class SmsController extends AdminBaseController
{
    protected SmsService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new SmsService();
    }

    public function index()
    {
        return $this->view('sms/index');
    }

    public function templates()
    {
        $templates = \think\facade\Db::name('sms_template')->order('id', 'desc')->paginate(20);
        return $this->view('sms/templates', ['templates' => $templates]);
    }

    public function logs()
    {
        $page = (int) $this->request->param('page', 1);
        try {
            $query = \think\facade\Db::name('sms_log')->order('id', 'desc');
            $total = $query->count();
            $list = $query->page($page, 20)->select()->toArray();
        } catch (\Throwable $e) {
            $total = 0;
            $list = [];
        }
        return $this->view('sms/logs', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    public function send()
    {
        $mobile = $this->request->param('mobile', '');
        $templateCode = $this->request->param('template_code', '');
        $params = $this->request->param('params', []);
        $result = $this->service->send($mobile, $templateCode, $params);
        return Json::success('发送成功', $result);
    }
}
