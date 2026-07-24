<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\WebhookEndpoint;
use app\common\model\WebhookLog;

/**
 * Webhook管理后台控制器 - V2.9.29 Sprint D-4
 */
class WebhookController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $list = WebhookEndpoint::order('id', 'desc')->paginate(15);
        $this->assign('list', $list);
        return $this->view('/webhook_list');
    }

    public function edit(int $id = 0)
    {
        $info = $id > 0 ? WebhookEndpoint::find($id) : null;
        if ($this->request->isPost()) {
            $data = [
                'name' => $this->request->post('name', ''),
                'url' => $this->request->post('url', ''),
                'secret' => $this->request->post('secret', ''),
                'events' => json_encode($this->request->post('events', [])),
                'retry_count' => (int) $this->request->post('retry_count', 3),
                'timeout_seconds' => (int) $this->request->post('timeout_seconds', 10),
                'is_active' => (int) $this->request->post('is_active', 1),
            ];
            if ($id > 0) {
                $info->save($data);
            } else {
                WebhookEndpoint::create($data);
            }
            return $this->success('保存成功', ['redirect' => '/admin/webhook/index']);
        }
        $this->assign('info', $info);
        return $this->view('/webhook_edit');
    }

    public function delete(int $id = 0)
    {
        WebhookEndpoint::destroy($id);
        return $this->success('已删除');
    }

    public function toggle(int $id = 0)
    {
        $ep = WebhookEndpoint::find($id);
        if ($ep) {
            $ep->is_active = $ep->is_active ? 0 : 1;
            $ep->save();
        }
        return $this->success('操作成功');
    }

    public function logs(int $id = 0)
    {
        $query = WebhookLog::order('id', 'desc');
        if ($id > 0) $query->where('endpoint_id', $id);
        $list = $query->paginate(20);
        $this->assign('list', $list);
        $this->assign('endpoint_id', $id);
        return $this->view('/webhook_logs');
    }
}
