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
use app\common\service\PaidService;

/**
 * 前台付费内容控制器
 */
class PaidController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 购买付费内容（AJAX）
     * POST /paid/buy
     */
    public function buy()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 2, 'msg' => '请先登录']);
        }

        $contentId = (int) $this->request->post('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $result = PaidService::quickBuy($this->memberInfo['id'], $contentId);
            return json(['code' => 0, 'msg' => '购买成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 已购内容列表
     */
    public function purchased()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $page = (int) $this->request->get('page', 1);
        $list = PaidService::getPurchasedList((int) $this->memberInfo['id'], $page);

        $this->assign('list', $list);
        $this->assign('member', $this->memberInfo);
        return $this->view('/member_purchased', ['ucenter_active' => 'purchased']);
    }
}
