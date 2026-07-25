<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\channel\DistributionConfigService;

/**
 * 分发配置中心控制器 — V2.9.34 CD-5
 */
class ChannelConfigController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $service = new DistributionConfigService();
        $channels = $service->getChannels();
        $templates = $service->getTemplates();
        $strategies = $service->getStrategies();
        $stats = $service->getStats();
        $this->assign('channels', $channels);
        $this->assign('templates', $templates);
        $this->assign('strategies', $strategies);
        $this->assign('stats', $stats);
        $this->assign('menuActive', 'channel_config');
        return $this->view('/channel_config/index');
    }

    public function saveWechat()
    {
        $data = $this->request->post();
        $service = new DistributionConfigService();
        $result = $service->saveTemplate($data);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function savePlatform()
    {
        $data = $this->request->post();
        $service = new DistributionConfigService();
        $result = $service->saveStrategy($data);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function test()
    {
        $channelId = (int)$this->request->param('channel_id', 0);
        $type = (string)$this->request->param('type', 'wechat');
        $service = new DistributionConfigService();
        $result = $service->testChannel($channelId, $type);
        if ($result['success'] ?? false) {
            return $this->success('连接成功', $result);
        }
        return $this->error($result['message'] ?? '连接失败');
    }
}
