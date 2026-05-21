<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\PointsProductService;

/**
 * 前台积分商城控制器 - V2.7
 */
class PointsProductController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 积分商品列表（V2.9.10: 纳入用户中心框架，需登录）
     */
    public function index()
    {
        if (empty($this->memberInfo)) {
            return redirect('/member/login');
        }

        $list = PointsProductService::getList(1, 20, true);
        $this->assign('list', $list);
        $this->assign('member', $this->memberInfo);
        $this->assign('ucenter_active', 'points_shop');
        return $this->view('/points_product');
    }

    /**
     * 兑换商品
     */
    public function exchange()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        if (empty($this->memberInfo)) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $productId = (int) $this->request->post('product_id', 0);
        $deliveryInfo = [
            'name'  => $this->request->post('name', ''),
            'phone' => $this->request->post('phone', ''),
            'address' => $this->request->post('address', ''),
        ];

        try {
            $result = PointsProductService::exchange($this->memberInfo['id'], $productId, $deliveryInfo);
            return json(['code' => 0, 'msg' => '兑换成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
