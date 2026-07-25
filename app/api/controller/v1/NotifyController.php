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
use app\common\model\Notification;

/**
 * 通知 API - V2.9.18 U-3
 * 需要登录认证
 */
class NotifyController extends BaseController
{
    /**
     * 通知列表
     */
    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 10);
        $userId   = $this->getUserId();

        $query = Notification::where('receiver_type', 'member')
            ->where('receiver_id', $userId)
            ->order('id', 'desc');

        $total = $query->count();
        $data  = $query->page($page, $pageSize)->select();

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['data' => $data, 'total' => $total]]);
    }

    /**
     * 未读通知数
     */
    public function unreadCount()
    {
        $userId = $this->getUserId();
        $count = Notification::where('receiver_type', 'member')
            ->where('receiver_id', $userId)
            ->where('is_read', 0)
            ->count();

        return json(['code' => 0, 'msg' => 'ok', 'data' => ['count' => $count]]);
    }

    /**
     * 标记已读
     */
    public function read()
    {
        $id     = (int) $this->request->post('id', 0);
        $userId = $this->getUserId();

        Notification::where('id', $id)
            ->where('receiver_type', 'member')
            ->where('receiver_id', $userId)
            ->update(['is_read' => 1]);

        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 全部已读
     */
    public function readAll()
    {
        $userId = $this->getUserId();
        Notification::where('receiver_type', 'member')
            ->where('receiver_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return json(['code' => 0, 'msg' => 'ok']);
    }

    private function getUserId(): int
    {
        // 从 session 或 token 获取当前登录用户 ID
        $member = session('member');
        return $member['id'] ?? 0;
    }
}
