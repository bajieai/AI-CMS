<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\PluginMarketService;
use think\facade\Config;

/**
 * 插件市场后台控制器 - V2.9.2 M25
 */
class PluginMarketController extends AdminBaseController
{
    /**
     * 市场浏览页
     */
    public function index()
    {
        $service = new PluginMarketService();
        $enabled = Config::get('plugin.market_enabled', 1);

        $keyword = $this->request->get('keyword', '');
        $category = $this->request->get('category', '');
        $page = (int) $this->request->get('page', 1);

        $plugins = [];
        $total = 0;
        $categories = [];

        if ($enabled) {
            $result = $service->getMarketList([
                'keyword'  => $keyword,
                'category' => $category,
                'page'     => $page,
                'limit'    => 20,
            ]);
            $plugins = $result['data'] ?? [];
            $total = $result['total'] ?? 0;
            $categories = $service->getCategories();
        }

        // V2.9.3 M25: 推荐位（取前4个未安装或高版本插件）
        $featured = [];
        if (!empty($plugins)) {
            foreach ($plugins as $p) {
                if (!$p['is_installed'] || $p['has_update']) {
                    $featured[] = $p;
                }
                if (count($featured) >= 4) break;
            }
            // 如果不足4个，补普通插件
            if (count($featured) < 4) {
                foreach ($plugins as $p) {
                    $found = false;
                    foreach ($featured as $f) {
                        if (($f['code'] ?? '') === ($p['code'] ?? '')) { $found = true; break; }
                    }
                    if (!$found) { $featured[] = $p; }
                    if (count($featured) >= 4) break;
                }
            }
        }

        $this->assign('enabled', $enabled);
        $this->assign('plugins', $plugins);
        $this->assign('featured', $featured);
        $this->assign('categories', $categories);
        $this->assign('keyword', $keyword);
        $this->assign('category', $category);
        $this->assign('page', $page);
        $this->assign('total', $total);

        return $this->view('/plugin_market_index');
    }

    /**
     * V2.9.3 M25: 插件详情页
     */
    public function detail()
    {
        $code = $this->request->param('code', '');
        if (empty($code)) {
            return redirect('/admin/plugin_market/index');
        }

        $service = new PluginMarketService();
        $result = $service->getMarketDetail($code);

        if (!$result['success']) {
            $this->assign('error', $result['msg']);
            $this->assign('plugin', null);
        } else {
            $this->assign('plugin', $result['data']);
            $this->assign('error', '');
        }

        // 保留搜索/分类上下文以便返回
        $this->assign('keyword', $this->request->get('keyword', ''));
        $this->assign('category', $this->request->get('category', ''));

        return $this->view('/plugin_market_detail');
    }

    /**
     * 从市场安装插件
     */
    public function install()
    {
        $code = $this->request->post('code', '');
        $downloadUrl = $this->request->post('download_url', '');

        if (empty($code) || empty($downloadUrl)) {
            return json(['code' => 1, 'msg' => '参数不完整']);
        }

        $service = new PluginMarketService();
        $result = $service->installFromMarket($code, $downloadUrl);

        if ($result['success']) {
            return json(['code' => 0, 'msg' => $result['msg']]);
        }

        return json(['code' => 1, 'msg' => $result['msg']]);
    }

    /**
     * 本地上传ZIP安装
     */
    public function upload()
    {
        $file = $this->request->file('plugin_zip');
        if (!$file) {
            return json(['code' => 1, 'msg' => '请选择插件ZIP文件']);
        }

        // 校验扩展名
        $ext = strtolower($file->getOriginalExtension());
        if ($ext !== 'zip') {
            return json(['code' => 1, 'msg' => '仅支持 .zip 格式']);
        }

        // 校验大小（最大50MB）
        $size = $file->getSize();
        if ($size > 50 * 1024 * 1024) {
            return json(['code' => 1, 'msg' => '文件大小超过50MB限制']);
        }

        $service = new PluginMarketService();
        $result = $service->uploadAndInstall($file->getRealPath(), $file->getOriginalName());

        if ($result['success']) {
            return json(['code' => 0, 'msg' => $result['msg']]);
        }

        return json(['code' => 1, 'msg' => $result['msg']]);
    }

    /**
     * 检查更新
     */
    public function checkUpdates()
    {
        $service = new PluginMarketService();
        $updates = $service->checkUpdates();

        return json([
            'code' => 0,
            'msg'  => 'success',
            'data' => $updates,
            'count'=> count($updates),
        ]);
    }
}
