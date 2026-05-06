<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\PublishLog;
use app\common\service\PublishPlatformService;

/**
 * 发布记录管理后台控制器 - V2.5新增
 */
class PublishLogController extends AdminBaseController
{
    public function index()
    {
        $list = PublishLog::with(['platform'])->order('id', 'desc')
            ->paginate(['list_rows' => 20, 'path' => '/admin/publish_log/index']);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/publish_log_index');
    }

    /**
     * 手动发布内容到平台
     */
    public function publish()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        $platformId = (int) $this->request->post('platform_id', 0);

        if ($contentId <= 0 || $platformId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $result = PublishPlatformService::publish($contentId, $platformId);
            return json(['code' => 0, 'msg' => '发布成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
