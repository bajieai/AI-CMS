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
use app\common\model\PushLog;
use app\common\model\PushChannel;
use app\common\service\push\PushDispatchService;

/**
 * 推送日志控制器 - V2.9.18 D-1
 */
class PushLogController extends AdminBaseController
{
    /**
     * 推送日志列表页
     */
    public function index()
    {
        return $this->view('/push_log');
    }

    /**
     * AJAX 获取日志列表
     */
    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 20);
        $status   = $this->request->get('status', '');

        $query = PushLog::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->page($page, $pageSize)->select();

        // 关联通道名称
        $channelIds = array_unique(array_column($list->toArray(), 'channel_id'));
        $channels = PushChannel::whereIn('id', $channelIds)->column('name', 'id');
        
        $data = [];
        foreach ($list as $item) {
            $row = $item->toArray();
            $row['channel_name'] = $channels[$row['channel_id']] ?? '未知通道';
            $data[] = $row;
        }

        return $this->success('ok', [
            'data'  => $data,
            'total' => $total,
        ]);
    }

    /**
     * 重试失败推送
     */
    public function retry($id)
    {
        $service = new PushDispatchService();
        $result  = $service->retry((int) $id);

        if ($result['success']) {
            return $this->success('重试推送成功', $result);
        }
        return $this->error($result['error_msg'] ?? '重试推送失败', $result);
    }
}
