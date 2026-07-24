<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.32 Sprint FIX-4: AI配图Controller
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\home\controller;

use app\common\controller\BaseController;
use app\common\service\ai\AiImageService;

/**
 * AI配图Controller - V2.9.32 FIX-4
 */
class AiImageController extends BaseController
{
    public function index()
    {
        $userId = (int) session('user_id');
        $service = new AiImageService();
        $library = $service->getImageLibrary($userId);
        $this->assign('library', $library);
        return $this->view('/ai/image_library');
    }

    public function generate(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentId = (int) $this->request->post('content_id', 0);
        $style = $this->request->post('style', 'auto');
        $count = (int) $this->request->post('count', 1);
        $service = new AiImageService();
        $result = $service->generate($contentId, $style, $count);
        return $result['success'] ? $this->success($result['message'], $result) : $this->error($result['message']);
    }

    public function batchGenerate(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentIds = $this->request->post('content_ids', []);
        $style = $this->request->post('style', 'auto');
        $service = new AiImageService();
        $result = $service->batchGenerate($contentIds, $style);
        return $this->success("批量配图完成（成功{$result['success_count']}篇，失败{$result['failed_count']}篇）", $result);
    }

    public function rateImage(): \think\Response
    {
        if (!$this->request->isPost()) return $this->error('请求方式错误');
        $contentId = (int) $this->request->post('content_id', 0);
        $score = (int) $this->request->post('score', 0);
        $feedback = $this->request->post('feedback', '');
        $service = new AiImageService();
        $result = $service->rateImage($contentId, $score, $feedback);
        return $result['success'] ? $this->success($result['message'], $result) : $this->error($result['message']);
    }
}
