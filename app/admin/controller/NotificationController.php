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
use app\common\model\Notification as NotificationModel;
use app\common\service\NotificationService;
use think\Request;

/**
 * 通知管理
 */
class NotificationController extends AdminBaseController
{
    protected NotificationService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new NotificationService;
    }

    public function index(Request $request)
    {
        $isRead = $request->get('is_read', '');
        $page = (int) $request->get('page', 1);

        $query = NotificationModel::where('receiver_type', 'admin')->order('create_time', 'desc');
        if ($isRead !== '') {
            $query->where('is_read', (int) $isRead);
        }

        $list = $query->paginate(15, false, ['page' => $page]);
        return $this->view('/notification_list', ['list' => $list, 'is_read' => $isRead]);
    }

    public function read(Request $request)
    {
        try {
            $id = (int) $request->post('id', 0);
            $all = (int) $request->post('all', 0);
            $userId = $this->getCurrentUser()['id'] ?? 0;

            if ($all === 1) {
                $this->service->markAllRead('admin', $userId);
                return json(['success' => true]);
            }

            $success = $this->service->markRead($id, 'admin', $userId);
            return json(['success' => $success]);
        } catch (\Exception $e) {
            return json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
}