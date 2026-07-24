<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\mini\MiniPageConfigService;
use app\common\service\mini\MiniComponentLibraryService;
use app\common\service\mini\MiniStatsService;
use app\common\service\mini\MiniMessageService;

/**
 * 小程序/移动端管理
 * V2.9.37 MINI-FULL
 */
class MiniManageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * SDK管理页面
     */
    public function sdk()
    {
        $sdkPath = public_path() . 'sdk/mini/ai-cms-sdk.js';
        $sdkExists = file_exists($sdkPath);
        $sdkVersion = '1.0.0';
        $sdkSize = $sdkExists ? filesize($sdkPath) : 0;
        return $this->view('/mini_sdk', [
            'sdk_exists'  => $sdkExists,
            'sdk_version' => $sdkVersion,
            'sdk_size'    => $sdkSize,
        ]);
    }

    /**
     * H5管理页面
     */
    public function h5()
    {
        return $this->view('/mini_h5', []);
    }

    /**
     * 页面配置管理
     */
    public function pageConfig()
    {
        $service = new MiniPageConfigService();
        $pageType = $this->request->get('page_type', '');
        $platform = $this->request->get('platform', 'all');
        $configs = $service->getConfigs($pageType, $platform);
        $layouts = $service->getDefaultLayouts();
        return $this->view('/mini_page_config', [
            'configs'    => $configs,
            'layouts'    => $layouts,
            'page_type'  => $pageType,
            'platform'   => $platform,
        ]);
    }

    /**
     * 保存页面配置
     */
    public function savePageConfig()
    {
        $data = $this->request->post();
        $service = new MiniPageConfigService();
        $id = $service->saveConfig($data);
        return json(['success' => $id > 0, 'id' => $id, 'msg' => $id > 0 ? '保存成功' : '保存失败']);
    }

    /**
     * 发布页面配置
     */
    public function publishPageConfig()
    {
        $id = (int) $this->request->post('id', 0);
        $service = new MiniPageConfigService();
        $result = $service->publishConfig($id);
        return json(['success' => $result, 'msg' => $result ? '发布成功' : '发布失败']);
    }

    /**
     * 回滚页面配置
     */
    public function rollbackPageConfig()
    {
        $id = (int) $this->request->post('id', 0);
        $service = new MiniPageConfigService();
        $result = $service->rollbackConfig($id);
        return json(['success' => $result, 'msg' => $result ? '回滚成功' : '回滚失败']);
    }

    /**
     * 导出配置
     */
    public function exportPageConfig()
    {
        $id = (int) $this->request->get('id', 0);
        $service = new MiniPageConfigService();
        $json = $service->exportConfig($id);
        return download($json, 'page_config_' . $id . '.json');
    }

    /**
     * 组件库页面
     */
    public function components()
    {
        $service = new MiniComponentLibraryService();
        $categories = $service->getCategories();
        $components = $service->getComponents('');
        $templates = $service->getTemplates();
        return $this->view('/mini_components', [
            'categories' => $categories,
            'components' => $components,
            'templates'  => $templates,
        ]);
    }

    /**
     * 统计看板
     */
    public function stats()
    {
        $service = new MiniStatsService();
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $overview = $service->getOverview($startDate, $endDate);
        $pageRank = $service->getPageRank(20);
        return $this->view('/mini_stats', [
            'overview'   => $overview,
            'page_rank'  => $pageRank,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
    }

    /**
     * 消息管理
     */
    public function message()
    {
        $service = new MiniMessageService();
        $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $endDate = date('Y-m-d 23:59:59');
        $stats = $service->getStats($startDate, $endDate);
        return $this->view('/mini_message', [
            'stats' => $stats,
        ]);
    }

    /**
     * 发送消息
     */
    public function sendMessage()
    {
        $memberId = (int) $this->request->post('member_id', 0);
        $type = $this->request->post('type', 'system');
        $title = $this->request->post('title', '');
        $content = $this->request->post('content', '');
        $channel = $this->request->post('channel', 'station');
        if ($memberId <= 0 || empty($title)) {
            return json(['success' => false, 'msg' => '参数错误']);
        }
        $service = new MiniMessageService();
        $id = $service->send($memberId, $type, [
            'title'   => $title,
            'content' => $content,
            'channel' => $channel,
        ]);
        return json(['success' => $id > 0, 'id' => $id, 'msg' => $id > 0 ? '发送成功' : '发送失败']);
    }
}
