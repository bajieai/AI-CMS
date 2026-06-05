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

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\common\service\SubscribeService;

/**
 * 邮件订阅 API - V2.9.18 D-3
 * 公开端点，无需认证
 */
class SubscribeController extends BaseController
{
    /**
     * 提交订阅
     * POST /api/subscribe/submit
     */
    public function submit()
    {
        $email  = $this->request->post('email', '');
        $source = $this->request->post('source', 'footer');

        if (empty($email)) {
            return json(['code' => 1, 'msg' => '请输入邮箱地址']);
        }

        $service = new SubscribeService();
        $result = $service->submit($email, $source);

        return json([
            'code' => $result['success'] ? 0 : 1,
            'msg'  => $result['msg'],
        ]);
    }

    /**
     * 确认订阅
     * GET /api/subscribe/confirm?token=xxx
     */
    public function confirm()
    {
        $token = $this->request->get('token', '');
        if (empty($token)) {
            return '<h3 style="text-align:center;padding:50px;color:#dc3545">❌ 确认链接无效</h3>';
        }

        $service = new SubscribeService();
        $result = $service->confirm($token);

        $icon = $result['success'] ? '✅' : '❌';
        $color = $result['success'] ? '#28a745' : '#dc3545';
        return "<h3 style=\"text-align:center;padding:50px;color:{$color}\">{$icon} {$result['msg']}</h3>";
    }

    /**
     * 退订
     * GET /api/subscribe/unsubscribe?token=xxx
     */
    public function unsubscribe()
    {
        $token = $this->request->get('token', '');
        if (empty($token)) {
            return '<h3 style="text-align:center;padding:50px;color:#dc3545">❌ 退订链接无效</h3>';
        }

        $service = new SubscribeService();
        $result = $service->unsubscribe($token);

        $icon = $result['success'] ? '✅' : '❌';
        $color = $result['success'] ? '#28a745' : '#dc3545';
        return "<h3 style=\"text-align:center;padding:50px;color:{$color}\">{$icon} {$result['msg']}</h3>";
    }
}
