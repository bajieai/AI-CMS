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

namespace app\api\controller;

use app\common\model\MobileNavTab;

/**
 * V2.9.24 H-2: 移动端导航API
 * 公开接口，无需登录
 */
class MobileNavController extends BaseController
{
    /**
     * 获取启用的底部导航Tab列表
     * GET /api/mobile/navTabs
     */
    public function navTabs(): \think\Response
    {
        $tabs = MobileNavTab::getEnabledTabs();

        // 根据登录状态过滤需要登录的Tab
        $memberId = $this->request->middleware('member_id', 0);
        $result = [];
        foreach ($tabs as $tab) {
            if ($tab['require_login'] && !$memberId) {
                // 未登录用户将需要登录的TabURL改为登录页
                $tab['url'] = '/member/login';
            }
            $result[] = [
                'id' => $tab['id'],
                'name' => $tab['name'],
                'icon' => $tab['icon'],
                'icon_active' => $tab['icon_active'],
                'tab_type' => $tab['tab_type'],
                'url' => $tab['url'],
                'require_login' => (bool)$tab['require_login'],
                'show_badge' => (bool)$tab['show_badge'],
            ];
        }

        return json(['code' => 0, 'data' => $result]);
    }
}
