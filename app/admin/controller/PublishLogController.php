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
use app\common\service\PublishPlatformService;
use app\common\model\PublishLog;

/**
 * V2.9.4 发布状态看板控制器
 */
class PublishLogController extends AdminBaseController
{
    /**
     * 发布看板首页
     */
    public function index()
    {
        $platform = $this->request->get('platform', '');
        $status = $this->request->get('status', '');
        $page = (int) $this->request->get('page', 1);
        $dateFrom = $this->request->get('date_from', '');
        $dateTo = $this->request->get('date_to', '');

        $filters = array_filter([
            'platform' => $platform,
            'status' => $status !== '' ? $status : '',
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ], function ($v) { return $v !== ''; });

        $result = PublishPlatformService::getPublishLogList($filters, $page, 20);
        $summary = PublishPlatformService::getPublishSummary();

        $this->assign('logs', $result['list']);
        $this->assign('total', $result['total']);
        $this->assign('page', $result['page']);
        $this->assign('limit', $result['limit']);
        $this->assign('summary', $summary);
        $this->assign('filters', $filters);
        $this->assign('platform_options', [
            'weixin' => '微信公众号',
            'toutiao' => '头条号',
            'zhihu' => '知乎',
        ]);

        return $this->view('/publish_log');
    }

    /**
     * 手动重试
     */
    public function retry()
    {
        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $result = PublishPlatformService::retryPublish($id);
            return json(['code' => 0, 'msg' => '重试成功', 'data' => $result]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '重试失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 发布摘要API（供AJAX调用）
     */
    public function summary()
    {
        $summary = PublishPlatformService::getPublishSummary();
        return json(['code' => 0, 'data' => $summary]);
    }
}
