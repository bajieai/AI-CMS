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
use app\common\service\AiWritingService;
use app\common\model\AiBatchTask;

/**
 * AI批量生成管理后台控制器 - V2.5新增
 */
class AiBatchController extends AdminBaseController
{
    /**
     * 批量任务列表
     */
    public function index()
    {
        $list = AiBatchTask::order('id', 'desc')
            ->paginate(['list_rows' => 20, 'path' => '/admin/ai_batch/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/ai_batch_index');
    }

    /**
     * 创建批量任务页面
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = [
                'title'    => $this->request->post('title', ''),
                'keywords' => $this->request->post('keywords', ''),
                'total'    => (int) $this->request->post('total', 1),
                'style'    => $this->request->post('style', 'default'),
                'cate_id'  => (int) $this->request->post('cate_id', 0),
                'model_id' => (int) $this->request->post('model_id', 0),
            ];

            if (empty($data['title']) || empty($data['keywords'])) {
                return json(['code' => 1, 'msg' => '请填写任务标题和关键词']);
            }

            try {
                $task = AiWritingService::createBatchTask(
                    $data['title'],
                    $data['keywords'],
                    $data['style'],
                    $data['cate_id'],
                    $data['model_id']
                );
                return json(['code' => 0, 'msg' => '任务创建成功', 'data' => ['id' => $task->id]]);
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }

        // 获取AI模型列表和分类列表供选择
        $models = \app\common\model\AiModel::where('status', 1)->select();
        $cates = \app\common\model\Cate::where('status', 1)->select();
        $styles = AiWritingService::getStyles();

        $this->assign('models', $models);
        $this->assign('cates', $cates);
        $this->assign('styles', $styles);
        return $this->view('/ai_batch_create');
    }

    /**
     * 任务详情
     */
    public function detail(int $id)
    {
        $task = AiBatchTask::find($id);
        if (!$task) {
            return json(['code' => 1, 'msg' => '任务不存在']);
        }

        $this->assign('task', $task);
        return $this->view('/ai_batch_detail');
    }

    /**
     * 取消任务
     */
    public function cancel()
    {
        $id = (int) $this->request->post('id', 0);
        $task = AiBatchTask::find($id);
        if (!$task) {
            return json(['code' => 1, 'msg' => '任务不存在']);
        }

        if ($task->status == 2 || $task->status == 3) {
            return json(['code' => 1, 'msg' => '任务已完成或已失败']);
        }

        $task->status = 3;
        $task->save();
        return json(['code' => 0, 'msg' => '任务已取消']);
    }
}
