<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiQualityCheckService;
use think\facade\Cache;

/**
 * AI内容质量控制后台控制器 - V2.9.40 AI-DEEP2-2
 */
class AiQualityController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 质量检查列表
     */
    public function index()
    {
        $service = new AiQualityCheckService();
        $list = $service->getList($this->request->get('page', 1), $this->request->get('limit', 20));

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }

        $this->assign('list', $list);
        return $this->view('/ai/quality_index');
    }

    /**
     * 执行质量检查
     */
    public function check()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '请指定内容ID']);
        }

        $content = \app\common\model\Content::find($contentId);
        if (!$content) {
            return json(['code' => 1, 'msg' => '内容不存在']);
        }

        $service = new AiQualityCheckService();
        $result = $service->check([
            'title'     => $content->title,
            'content'   => $content->content,
            'summary'   => $content->description ?? '',
            'keywords'  => $content->keywords ?? '',
            'cate_id'   => $content->cate_id ?? 0,
        ]);

        return json(['code' => 0, 'msg' => '检查完成', 'data' => $result]);
    }

    /**
     * 批量质量检查
     */
    public function batchCheck()
    {
        $ids = $this->request->post('ids', []);
        if (empty($ids)) {
            return json(['code' => 1, 'msg' => '请选择内容']);
        }

        $service = new AiQualityCheckService();
        $results = [];
        foreach ($ids as $id) {
            $content = \app\common\model\Content::find((int)$id);
            if ($content) {
                $results[$id] = $service->check([
                    'title'     => $content->title,
                    'content'   => $content->content,
                    'summary'   => $content->description ?? '',
                    'keywords'  => $content->keywords ?? '',
                    'cate_id'   => $content->cate_id ?? 0,
                ]);
            }
        }

        return json(['code' => 0, 'msg' => '批量检查完成', 'data' => $results]);
    }

    /**
     * 获取检查详情
     */
    public function detail(int $id)
    {
        $service = new AiQualityCheckService();
        $detail = $service->getDetail($id);
        if (!$detail) {
            return json(['code' => 1, 'msg' => '记录不存在']);
        }

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $detail]);
        }

        $this->assign('detail', $detail);
        return $this->view('/ai/quality_detail');
    }

    /**
     * 获取质量统计
     */
    public function stats()
    {
        $service = new AiQualityCheckService();
        $stats = $service->getStats();
        return json(['code' => 0, 'msg' => 'success', 'data' => $stats]);
    }
}
