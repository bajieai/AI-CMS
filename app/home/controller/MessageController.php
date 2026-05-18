<?php
declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\FrontBaseController;
use app\common\service\PrivateMessageService;

/**
 * 前台私信控制器 - V2.6
 */
class MessageController extends FrontBaseController
{
    /**
     * 私信列表页
     */
    public function index()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login?redirect=' . urlencode(request()->url()));
        }

        $page = (int) $this->request->get('page', 1);
        $conversations = PrivateMessageService::getConversations($this->memberInfo['id'], $page, 20);

        $this->assign([
            'conversations' => $conversations,
            'unread_count' => PrivateMessageService::getUnreadCount($this->memberInfo['id']),
        ]);
        return $this->view('/message_index');
    }

    /**
     * 聊天详情页
     */
    public function chat(int $id)
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login?redirect=' . urlencode(request()->url()));
        }

        $page = (int) $this->request->get('page', 1);
        $messages = PrivateMessageService::getMessages($id, $this->memberInfo['id'], $page, 30);
        PrivateMessageService::markRead($id, $this->memberInfo['id']);

        $conversation = \app\common\model\MessageConversation::find($id);
        $otherId = $conversation ? (($conversation->user_id_1 == $this->memberInfo['id']) ? $conversation->user_id_2 : $conversation->user_id_1) : 0;
        $this->assign([
            'conversation_id' => $id,
            'other_user_id' => $otherId,
            'messages' => $messages,
        ]);
        return $this->view('/message_chat');
    }

    /**
     * 发送私信（AJAX）
     */
    public function send()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $toUserId = (int) $this->request->post('to_user_id', 0);
        $content = $this->request->post('content', '');

        try {
            $result = PrivateMessageService::send($this->memberInfo['id'], $toUserId, $content);
            return json(['code' => 0, 'msg' => '发送成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 系统通知列表
     */
    public function system()
    {
        if (!$this->isMemberLogin) {
            return redirect('/member/login?redirect=' . urlencode(request()->url()));
        }

        $page = (int) $this->request->get('page', 1);
        $list = PrivateMessageService::getSystemMessages($this->memberInfo['id'], $page, 20);

        $this->assign([
            'list' => $list,
            'unread_count' => PrivateMessageService::getSystemUnreadCount($this->memberInfo['id']),
        ]);
        return $this->view('/message_system');
    }

    /**
     * 标记系统通知已读
     */
    public function markSystemRead()
    {
        if (!$this->isMemberLogin) {
            return json(['code' => 401, 'msg' => '请先登录']);
        }

        $msgId = (int) $this->request->post('id', 0);
        PrivateMessageService::markSystemRead($msgId, $this->memberInfo['id']);
        return json(['code' => 0, 'msg' => 'success']);
    }
}
