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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Notification;
use app\common\model\Member;

/**
 * 后台通知管理控制器 - V2.9.18 U-3
 * 管理员发送站内通知
 */
class NotifyController extends AdminBaseController
{
    /**
     * 发送通知页面
     */
    public function sendPage()
    {
        return $this->view('/notify_send');
    }

    /**
     * 执行发送
     */
    public function doSend()
    {
        $title    = $this->request->post('title', '');
        $content  = $this->request->post('content', '');
        $link     = $this->request->post('link', '');
        $target   = $this->request->post('target', 'all'); // all | user_ids
        $userIds  = $this->request->post('user_ids', '');

        if (empty($title)) {
            return $this->error('请输入通知标题');
        }

        // 确定接收人
        if ($target === 'user_ids' && $userIds) {
            $memberIds = array_map('intval', explode(',', $userIds));
        } else {
            $memberIds = Member::where('status', 1)->column('id');
        }

        if (empty($memberIds)) {
            return $this->error('无可用接收用户');
        }

        $count = 0;
        $now = time();
        foreach ($memberIds as $memberId) {
            Notification::create([
                'type'          => 'system',
                'receiver_type' => 'member',
                'receiver_id'   => (int) $memberId,
                'title'         => $title,
                'content'       => $content,
                'link'          => $link,
                'is_read'       => 0,
                'create_time'   => $now,
            ]);
            $count++;
        }

        return $this->success("已发送通知给 {$count} 位用户");
    }

    /**
     * 发送历史页面
     */
    public function history()
    {
        return $this->view('/notify_history');
    }
}
