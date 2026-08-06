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
use app\common\service\ConfigService;
use app\common\service\UpgradeService;
use think\facade\Request;

/**
 * 在线升级控制器 - V2.9.44
 */
class OnlineUpgradeController extends AdminBaseController
{
    /**
     * 在线升级首页
     */
    public function index()
    {
        $upgradeService = new UpgradeService();
        $currentVersion = $upgradeService->getCurrentVersion();

        // 获取最近5条升级历史
        $history = $upgradeService->getHistory(1, 5);

        return $this->view('/online_upgrade', [
            'currentVersion' => $currentVersion,
            'history' => $history,
        ]);
    }

    /**
     * 升级配置页面
     */
    public function config()
    {
        if (Request::isPost()) {
            $data = Request::post();
            ConfigService::set('upgrade_check_enabled', ($data['upgrade_check_enabled'] ?? 0) ? 1 : 0, 'system', '是否开启升级检查');
            ConfigService::set('upgrade_channel', in_array($data['upgrade_channel'] ?? '', ['stable', 'beta']) ? $data['upgrade_channel'] : 'stable', 'system', '升级通道');
            ConfigService::set('gitee_token', trim($data['gitee_token'] ?? ''), 'system', 'Gitee 私人令牌');
            ConfigService::set('upgrade_last_check', time(), 'system', '上次升级检查时间');

            return $this->success('配置已保存');
        }

        $config = [
            'upgrade_check_enabled' => (int) ConfigService::get('upgrade_check_enabled', 1),
            'upgrade_channel'       => ConfigService::get('upgrade_channel', 'stable'),
            'gitee_token'           => ConfigService::get('gitee_token', ''),
            'upgrade_last_check'    => (int) ConfigService::get('upgrade_last_check', 0),
            'upgrade_latest_version'=> ConfigService::get('upgrade_latest_version', ''),
        ];

        return $this->view('/online_upgrade_config', [
            'config' => $config,
        ]);
    }

    /**
     * 检查最新版本
     */
    public function check()
    {
        $upgradeService = new UpgradeService();
        $result = $upgradeService->checkLatest();

        if (!$result['success']) {
            return $this->error($result['msg']);
        }

        return $this->success($result['msg'], $result['data']);
    }

    /**
     * 环境检查
     */
    public function environment()
    {
        $upgradeService = new UpgradeService();
        $result = $upgradeService->checkEnvironment();

        if (!$result['success']) {
            return $this->error('环境检查未通过', 1, $result);
        }

        return $this->success('环境检查通过', $result);
    }

    /**
     * 执行升级
     */
    public function execute()
    {
        $downloadUrl = Request::post('download_url', '', 'trim');
        $toVersion = Request::post('to_version', '', 'trim');

        if (empty($downloadUrl) || empty($toVersion)) {
            return $this->error('缺少升级参数');
        }

        // 升级过程较长，关闭输出缓冲
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        @ob_end_clean();

        $upgradeService = new UpgradeService();
        $result = $upgradeService->upgrade($downloadUrl, $toVersion);

        return $this->jsonResult($result);
    }

    /**
     * 获取升级进度
     */
    public function progress()
    {
        $logId = (int) Request::param('log_id', 0);
        if ($logId <= 0) {
            return $this->error('参数错误');
        }

        $upgradeService = new UpgradeService();
        $result = $upgradeService->getProgress($logId);

        return $this->jsonResult($result);
    }

    /**
     * 升级历史
     */
    public function history()
    {
        $page = (int) Request::param('page', 1);
        $limit = (int) Request::param('limit', 20);

        $upgradeService = new UpgradeService();
        $result = $upgradeService->getHistory($page, $limit);

        return $this->success('获取成功', $result);
    }

    /**
     * 回滚到指定升级记录
     */
    public function rollback()
    {
        $logId = (int) Request::post('log_id', 0);
        if ($logId <= 0) {
            return $this->error('请选择升级记录');
        }

        @set_time_limit(0);

        $upgradeService = new UpgradeService();
        $result = $upgradeService->rollbackByLogId($logId);

        return $this->jsonResult($result);
    }

    /**
     * 清除版本检查缓存
     */
    public function clearCache()
    {
        $upgradeService = new UpgradeService();
        $upgradeService->clearLatestCache();
        return $this->success('缓存已清除');
    }

    /**
     * 统一包装结果
     */
    protected function jsonResult(array $result): \think\Response
    {
        if ($result['success']) {
            return $this->success($result['msg'], $result['data'] ?? []);
        }
        return $this->error($result['msg'], 1, $result['data'] ?? []);
    }
}
