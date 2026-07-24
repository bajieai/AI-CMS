<?php
declare(strict_types=1);

namespace app\api\controller\mini;

use app\api\controller\BaseController;
use app\common\service\mini\MiniMessageService;

/**
 * 小程序/H5 消息API
 * V2.9.37 MINI-FULL-6
 */
class MessageController extends BaseController
{
    /**
     * 消息列表
     */
    public function list()
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return $this->success('请先登录', [], 401);
        }
        $page = (int) $this->request->get('page', 1);
        $service = new MiniMessageService();
        $result = $service->getList($memberId, $page);
        return $this->success('ok', $result);
    }

    /**
     * 标记已读
     */
    public function read()
    {
        $memberId = $this->getMemberId();
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return $this->success('参数错误', [], 400);
        }
        $service = new MiniMessageService();
        $result = $service->markRead($id);
        return $this->success('已标记', ['result' => $result]);
    }

    /**
     * 全部已读
     */
    public function readAll()
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return $this->success('请先登录', [], 401);
        }
        $service = new MiniMessageService();
        $result = $service->markAllRead($memberId);
        return $this->success('已全部标记', ['result' => $result]);
    }

    /**
     * 未读数
     */
    public function unread()
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return $this->success('ok', ['count' => 0]);
        }
        $service = new MiniMessageService();
        $count = $service->getUnreadCount($memberId);
        return $this->success('ok', ['count' => $count]);
    }

    /**
     * 删除消息
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return $this->success('参数错误', [], 400);
        }
        $service = new MiniMessageService();
        $result = $service->delete($id);
        return $this->success('已删除', ['result' => $result]);
    }
}
