<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\system\OauthUserService;
use think\facade\Json;

/**
 * 第三方登录管理控制器
 * V2.9.38 SYS-INTEG-1
 */
class OauthUserController extends AdminBaseController
{
    protected OauthUserService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new OauthUserService();
    }

    public function index()
    {
        $page = (int) $this->request->param('page', 1);
        $query = \app\common\model\OauthUser::where('id', '>', 0);
        if ($provider = $this->request->param('provider')) $query->where('oauth_provider', $provider);
        $total = $query->count();
        $list = $query->order('id', 'desc')->page($page, 20)->select()->toArray();
        return $this->view('oauth_user/index', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    public function stats()
    {
        $stats = $this->service->getOauthStats();
        return $this->view('oauth_user/stats', ['stats' => $stats]);
    }
}
