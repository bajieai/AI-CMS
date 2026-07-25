<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\h5\H5ConfigService;

/**
 * H5移动端配置后台控制器
 */
class H5ConfigController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * H5配置首页
     */
    public function index()
    {
        $this->assign('theme', H5ConfigService::getTheme());
        $this->assign('features', H5ConfigService::getFeatures());
        $this->assign('performance', H5ConfigService::getPerformance());
        $this->assign('pwa', H5ConfigService::getPwa());
        return $this->view('h5_config/index');
    }

    /**
     * 主题配置编辑页
     */
    public function theme()
    {
        $this->assign('theme', H5ConfigService::getTheme());
        return $this->view('h5_config/theme');
    }

    /**
     * 通用配置编辑页（JSON编辑器）
     */
    public function edit()
    {
        $key = $this->request->param('key', 'feature');
        $value = H5ConfigService::get($key, []);
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assign('key', $key);
        $this->assign('json', $json);
        return $this->view('h5_config/edit');
    }

    /**
     * PWA配置编辑页
     */
    public function pwa()
    {
        $this->assign('pwa', H5ConfigService::getPwa());
        return $this->view('h5_config/pwa');
    }

    /**
     * 保存配置
     */
    public function save()
    {
        $contentType = $this->request->header('content-type', '');
        $key = '';
        $value = null;

        if (strpos($contentType, 'application/json') !== false) {
            $body = json_decode($this->request->getInput(), true);
            $key = $body['key'] ?? '';
            $value = $body['value'] ?? null;
        } else {
            $key = $this->request->post('key', '');
            $rawValue = $this->request->post('value');
            if (is_string($rawValue)) {
                $decoded = json_decode($rawValue, true);
                $value = ($decoded !== null) ? $decoded : $rawValue;
            } else {
                $value = $rawValue;
            }
        }

        if (empty($key)) {
            return json(['code' => 1, 'msg' => '配置键不能为空']);
        }

        $allowedKeys = ['theme', 'feature', 'performance', 'pwa'];
        if (!in_array($key, $allowedKeys, true)) {
            return json(['code' => 1, 'msg' => '不支持的配置键: ' . $key]);
        }

        try {
            H5ConfigService::set($key, $value, 'general');
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }
}
