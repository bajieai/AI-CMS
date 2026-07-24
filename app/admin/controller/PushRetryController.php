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
use app\common\model\PushRetry;
use app\common\service\PushRetryService;

/**
 * 推送重试队列管理控制器 - V2.9.19 D-1d
 */
class PushRetryController extends AdminBaseController
{
    /**
     * 重试队列列表页
     */
    public function index()
    {
        return $this->view('/push_retry');
    }

    /**
     * AJAX 获取重试队列列表
     */
    public function list()
    {
        $page     = (int) $this->request->get('page', 1);
        $pageSize = (int) $this->request->get('limit', 20);
        $status   = $this->request->get('status', '');

        $query = PushRetry::order('id', 'desc');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->page($page, $pageSize)->select();

        return $this->success('ok', [
            'data'  => $list->toArray(),
            'total' => $total,
        ]);
    }

    /**
     * 手动触发重试处理
     */
    public function process()
    {
        $limit = (int) $this->request->post('limit', 50);
        $result = PushRetryService::processRetries($limit);
        return $this->success("处理完成：成功{$result['success']} 失败{$result['fail']} 延后{$result['skip']}", $result);
    }

    /**
     * 删除已完成/失败的记录
     */
    public function cleanup()
    {
        $days = (int) $this->request->post('days', 7);
        $cutoff = time() - ($days * 86400);

        $count = PushRetry::where('status', '<>', PushRetry::STATUS_PENDING)
            ->where('updated_at', '<', $cutoff)
            ->delete();

        return $this->success("已清理 {$count} 条历史记录");
    }
}
