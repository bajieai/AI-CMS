<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\ApiToken as ApiTokenModel;
use app\common\service\ApiService;
use think\Request;

/**
 * API令牌管理
 */
class TokenController extends AdminBaseController
{
    protected ApiService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new ApiService;
    }

    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $list = ApiTokenModel::order('create_time', 'desc')->paginate(15, false, ['page' => $page]);
        return $this->view('/token_list', ['list' => $list]);
    }

    public function create(Request $request)
    {
        if ($request->isPost()) {
            $name = $request->post('name', '');
            $authType = $request->post('auth_type', 'bearer');
            $scopes = $request->post('scopes', '*');
            $rateLimit = (int) $request->post('rate_limit', 60);
            $expireDays = (int) $request->post('expire_days', 0);

            $result = $this->service->generateToken($name, $authType, $scopes, $rateLimit, $expireDays);
            return json($result);
        }
        return $this->view('/token_create');
    }

    public function revoke(Request $request)
    {
        $id = (int) $request->post('id', 0);
        $success = $this->service->revokeToken($id);
        return json(['success' => $success, 'msg' => $success ? '吊销成功' : '操作失败']);
    }
}