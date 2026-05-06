<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\EmailSubscriber;

/**
 * 邮件订阅管理后台控制器
 */
class EmailSubscriberController extends AdminBaseController
{
    /**
     * 订阅者列表
     */
    public function index()
    {
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);

        $list = EmailSubscriber::order('id', 'desc')
            ->page($page, $limit)
            ->select();
        $total = EmailSubscriber::count();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list, 'count' => $total]);
        }
        $this->assign('list', $list);
        return $this->view('/email_subscriber_index');
    }

    /**
     * 删除订阅者（支持批量）
     */
    public function delete()
    {
        $id = $this->request->post('id', 0);
        if (is_array($id) && !empty($id)) {
            EmailSubscriber::whereIn('id', $id)->delete();
            return json(['code' => 0, 'msg' => '删除成功']);
        }
        $subscriber = EmailSubscriber::find((int) $id);
        if (!$subscriber) return json(['code' => 1, 'msg' => '订阅者不存在']);
        $subscriber->delete();
        return json(['code' => 0, 'msg' => '删除成功']);
    }

    /**
     * 导出订阅者邮箱
     */
    public function export()
    {
        $list = EmailSubscriber::column('email');
        $csv = "email\n" . implode("\n", $list);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=subscribers_' . date('Ymd') . '.csv',
        ]);
    }
}
