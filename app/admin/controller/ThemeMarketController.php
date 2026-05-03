<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ThemeMarketService;

/**
 * 模板市场管理后台控制器 - V2.5新增
 */
class ThemeMarketController extends AdminBaseController
{
    /**
     * 主题列表
     */
    public function index()
    {
        $type = $this->request->param('type', 'frontend');
        $themes = ThemeMarketService::getThemes($type);

        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $themes]);
        }

        $this->assign('themes', $themes);
        $this->assign('type', $type);
        return $this->view('/theme_market_index');
    }

    /**
     * 扫描并同步主题
     */
    public function scan()
    {
        try {
            $result = ThemeMarketService::scanAndSync();
            return json(['code' => 0, 'msg' => "扫描完成：新增{$result['added']}个，更新{$result['updated']}个"]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 安装主题
     */
    public function install()
    {
        $sourcePath = $this->request->post('source_path', '');
        $type = $this->request->post('type', 'frontend');

        if (empty($sourcePath)) {
            return json(['code' => 1, 'msg' => '请提供主题源路径']);
        }

        try {
            $result = ThemeMarketService::installTheme($sourcePath, $type);
            return json(['code' => 0, 'msg' => '主题安装成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 卸载主题
     */
    public function uninstall()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            ThemeMarketService::uninstallTheme($id);
            return json(['code' => 0, 'msg' => '主题卸载成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 检查更新
     */
    public function checkUpdate()
    {
        try {
            $updates = ThemeMarketService::checkUpdates();
            return json(['code' => 0, 'msg' => 'success', 'data' => $updates]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
