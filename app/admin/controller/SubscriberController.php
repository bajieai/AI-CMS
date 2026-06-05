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
use app\common\model\Subscriber;

/**
 * 订阅者管理控制器 - V2.9.18 D-3
 * 后台管理：列表/添加/删除/导出
 */
class SubscriberController extends AdminBaseController
{
    /**
     * 订阅者列表页
     */
    public function index()
    {
        return $this->view('/subscriber');
    }

    /**
     * AJAX 列表
     */
    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 20);
        $status   = $this->request->get('status', '');

        $query = Subscriber::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $data  = $query->page($page, $pageSize)->select();

        return $this->success('ok', ['data' => $data, 'total' => $total]);
    }

    /**
     * 手动添加订阅者
     */
    public function add()
    {
        $email = $this->request->post('email', '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        $exists = Subscriber::where('email', $email)->find();
        if ($exists) {
            return $this->error('该邮箱已存在');
        }

        Subscriber::create([
            'email'         => $email,
            'status'        => Subscriber::STATUS_CONFIRMED,
            'confirm_token' => Subscriber::generateToken(),
            'source'        => 'admin_add',
            'subscribed_at' => date('Y-m-d H:i:s'),
            'confirmed_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->success('添加成功');
    }

    /**
     * 删除订阅者
     */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        $subscriber = Subscriber::find((int) $id);
        if (!$subscriber) {
            return $this->error('订阅者不存在');
        }
        $subscriber->delete();
        return $this->success('删除成功');
    }

    /**
     * 导出 CSV
     */
    public function export()
    {
        $list = Subscriber::where('status', Subscriber::STATUS_CONFIRMED)->column('email');
        $csv = "email\n" . implode("\n", $list);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=subscribers_' . date('Ymd') . '.csv',
        ]);
    }
}
