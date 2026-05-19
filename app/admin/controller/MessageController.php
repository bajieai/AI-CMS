<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PrivateMessageService;

/**
 * 后台消息管理控制器 - V2.6
 */
class MessageController extends AdminBaseController
{
    /**
     * 系统通知列表
     */
    public function system()
    {
        $this->app->view->assign('menuActive', 'message_system');
        $page = (int) $this->request->get('page', 1);
        $list = \app\common\model\MessageSystem::order('id', 'desc')->paginate(20);
        $this->assign('list', $list);
        return $this->view('/message_system');
    }

    /**
     * 发送系统通知
     */
    public function sendSystem()
    {
        if ($this->request->isGet()) {
            return $this->view('/message_send_system');
        }

        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');
        $type = $this->request->post('type', 'system');
        $targetUrl = $this->request->post('target_url', '');

        if (empty($title) || empty($content)) {
            return json(['code' => 1, 'msg' => '标题和内容不能为空']);
        }

        $id = PrivateMessageService::sendSystem($title, $content, $type, $targetUrl);
        $this->recordLog('发送系统通知', "ID:{$id}", ['title' => $title]);
        return json(['code' => 0, 'msg' => '发送成功']);
    }
}
