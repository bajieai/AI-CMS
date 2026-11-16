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
use app\common\model\Feedback as FeedbackModel;
use app\common\service\FeedbackService;
use think\App;
use think\Request;

/**
 * 问题反馈控制器 - V2.9.63
 *
 * 链路：后台提交反馈 → 本地落库 → （可选）中心网关上报 → （可选）邮件通知
 */
class FeedbackController extends AdminBaseController
{
    /** @var FeedbackService */
    protected $service;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->service = new FeedbackService();
    }

    /**
     * 反馈列表（含提交入口）
     */
    public function index(Request $request)
    {
        $page    = (int) $request->get('page', 1);
        $status  = $request->get('status', '');
        $type    = (string) $request->get('type', '');
        $keyword = trim((string) $request->get('keyword', ''));

        $query = FeedbackModel::order('create_time', 'desc')
            ->when($status !== '' && $status !== null, function ($q) use ($status) {
                $q->where('status', (int) $status);
            })
            ->when($type !== '', function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->whereLike('title', '%' . $keyword . '%')
                        ->whereOr('content', 'like', '%' . $keyword . '%');
                });
            });

        $list = $query->paginate(15, false, ['page' => $page]);

        return $this->view('/feedback/index', [
            'list'     => $list,
            'stats'    => $this->service->stats(),
            'typeMap'  => FeedbackModel::TYPE_MAP,
            'statusMap' => FeedbackModel::STATUS_MAP,
            'filters'  => ['status' => $status, 'type' => $type, 'keyword' => $keyword],
            'config'   => $this->service->getConfig(),
        ]);
    }

    /**
     * 提交反馈
     */
    public function submit(Request $request)
    {
        if (!$request->isPost()) {
            return $this->error('请求方式不正确');
        }

        $admin  = (array) $this->getCurrentUser();
        $result = $this->service->submit($request->post(), $admin);
        if (empty($result['ok'])) {
            return $this->error((string) $result['msg']);
        }

        $this->recordLog('提交问题反馈', '#' . $result['id'], $request->post());
        return $this->success((string) $result['msg'], ['id' => $result['id']]);
    }

    /**
     * 反馈详情（弹窗用）
     */
    public function detail(Request $request)
    {
        $id  = (int) $request->get('id', 0);
        $row = FeedbackModel::find($id);
        if (!$row) {
            return $this->error('反馈不存在');
        }

        return $this->success('ok', [
            'id'             => (int) $row['id'],
            'type'           => (string) $row['type'],
            'type_text'      => (string) $row['type_text'],
            'title'          => (string) $row['title'],
            'content'        => (string) $row['content'],
            'contact_email'  => (string) $row['contact_email'],
            'contact_mobile' => (string) $row['contact_mobile'],
            'admin_name'     => (string) $row['admin_name'],
            'status'         => (int) $row['status'],
            'status_text'    => (string) $row['status_text'],
            'gateway_text'   => (string) $row['gateway_status_text'],
            'gateway_no'     => (string) $row['gateway_no'],
            'gateway_error'  => (string) $row['gateway_error'],
            'notify_text'    => (string) $row['notify_status_text'],
            'reply_content'  => (string) $row['reply_content'],
            'reply_time'     => (int) $row['reply_time'] > 0 ? date('Y-m-d H:i', (int) $row['reply_time']) : '',
            'create_time'    => (int) $row['create_time'] > 0 ? date('Y-m-d H:i', (int) $row['create_time']) : '',
            'site_url'       => (string) $row['site_url'],
            'app_version'    => (string) $row['app_version'],
            'env'            => $row['env_list'],
            'attachments'    => $row['attachments_list'],
        ]);
    }

    /**
     * 官方答复
     */
    public function reply(Request $request)
    {
        if (!$request->isPost()) {
            return $this->error('请求方式不正确');
        }

        $id      = (int) $request->post('id', 0);
        $content = trim((string) $request->post('reply_content', ''));
        if (mb_strlen($content) < 2) {
            return $this->error('答复内容至少 2 个字');
        }

        $row = FeedbackModel::find($id);
        if (!$row) {
            return $this->error('反馈不存在');
        }

        $row->save([
            'reply_content' => $content,
            'reply_time'    => time(),
            'status'        => 3,
        ]);
        $this->recordLog('答复问题反馈', '#' . $id, ['reply_content' => $content]);

        return $this->success('答复已保存');
    }

    /**
     * 更新处理状态
     */
    public function status(Request $request)
    {
        if (!$request->isPost()) {
            return $this->error('请求方式不正确');
        }

        $id     = (int) $request->post('id', 0);
        $status = (int) $request->post('status', -1);
        if (!array_key_exists($status, FeedbackModel::STATUS_MAP)) {
            return $this->error('状态值不合法');
        }

        $row = FeedbackModel::find($id);
        if (!$row) {
            return $this->error('反馈不存在');
        }

        $row->save(['status' => $status]);
        $this->recordLog('更新反馈状态', '#' . $id, ['status' => $status]);

        return $this->success('状态已更新为「' . FeedbackModel::STATUS_MAP[$status] . '」');
    }

    /**
     * 重新上报中心网关
     */
    public function retry(Request $request)
    {
        if (!$request->isPost()) {
            return $this->error('请求方式不正确');
        }

        $id     = (int) $request->post('id', 0);
        $result = $this->service->report($id);
        if (empty($result['ok'])) {
            return $this->error((string) $result['msg']);
        }

        $this->recordLog('重试上报反馈', '#' . $id, []);
        return $this->success((string) $result['msg']);
    }

    /**
     * 反馈设置页
     */
    public function config()
    {
        return $this->view('/feedback/config', [
            'config' => $this->service->getConfig(),
        ]);
    }

    /**
     * 保存反馈设置
     */
    public function saveConfig(Request $request)
    {
        if (!$request->isPost()) {
            return $this->error('请求方式不正确');
        }

        $this->service->saveConfig($request->post());
        $this->recordLog('保存反馈设置', '', []);

        return $this->success('设置已保存');
    }

    /**
     * 测试通知通道
     */
    public function testNotify(Request $request)
    {
        if (!$request->isPost()) {
            return $this->error('请求方式不正确');
        }

        $to     = trim((string) $request->post('notify_email', ''));
        $result = $this->service->testNotify($to);
        return empty($result['ok'])
            ? $this->error((string) $result['msg'])
            : $this->success((string) $result['msg']);
    }
}
