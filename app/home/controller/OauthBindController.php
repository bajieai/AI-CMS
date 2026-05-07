<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\model\MemberOauth;

/**
 * OAuth绑定/解绑管理控制器
 */
class OauthBindController extends FrontBaseController
{
    protected bool $enablePageCache = false;

    /**
     * 绑定管理页面
     */
    public function index()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login');
        }

        $memberId = $this->memberInfo['id'];
        $bindings = MemberOauth::where('member_id', $memberId)->select()->toArray();

        $providerList = [
            ['provider' => 'wechat', 'name' => '微信', 'icon' => 'fa-wechat'],
            ['provider' => 'qq', 'name' => 'QQ', 'icon' => 'fa-qq'],
            ['provider' => 'gitee', 'name' => 'Gitee', 'icon' => 'fa-git'],
        ];

        // 标记已绑定状态
        foreach ($providerList as &$p) {
            $bound = array_filter($bindings, fn($b) => $b['provider'] === $p['provider']);
            $p['bound'] = !empty($bound);
            $p['nickname'] = !empty($bound) ? reset($bound)['nickname'] : '';
        }

        $this->assign('providers', $providerList);
        $this->assign('member', $this->memberInfo);
        return $this->view('/oauth_bind');
    }

    /**
     * 解绑（AJAX）
     */
    public function unbind()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 1, 'msg' => '请先登录']);
        }

        $memberId = $this->memberInfo['id'];
        $provider = $this->request->post('provider', '');

        if (empty($provider)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        // 至少保留一个登录方式
        $totalBindings = MemberOauth::where('member_id', $memberId)->count();
        $member = \app\common\model\Member::find($memberId);
        $hasPassword = !empty($member->password);

        if ($totalBindings <= 1 && !$hasPassword) {
            return json(['code' => 1, 'msg' => '至少保留一个登录方式，请先设置密码']);
        }

        MemberOauth::where('member_id', $memberId)
            ->where('provider', $provider)
            ->delete();

        return json(['code' => 0, 'msg' => '解绑成功']);
    }
}
