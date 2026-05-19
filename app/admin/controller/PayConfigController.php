<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use think\facade\Config;

/**
 * V2.9.4 支付配置控制器
 */
class PayConfigController extends AdminBaseController
{
    /**
     * 支付配置页
     */
    public function index()
    {
        $this->assign('pay_enabled', Config::get('pay_enabled', 0));
        $this->assign('pay_wechat_enabled', Config::get('pay_wechat_enabled', 0));
        $this->assign('pay_alipay_enabled', Config::get('pay_alipay_enabled', 0));
        $this->assign('pay_wechat_app_id', Config::get('pay_wechat_app_id', ''));
        $this->assign('pay_wechat_mch_id', Config::get('pay_wechat_mch_id', ''));
        $this->assign('pay_wechat_sandbox', Config::get('pay_wechat_sandbox', 1));
        $this->assign('pay_alipay_app_id', Config::get('pay_alipay_app_id', ''));
        $this->assign('pay_alipay_sandbox', Config::get('pay_alipay_sandbox', 1));

        return $this->view('/pay_config');
    }

    /**
     * 保存支付配置
     */
    public function save()
    {
        $data = $this->request->post();

        $configMap = [
            'pay_enabled' => 'pay_enabled',
            'pay_wechat_enabled' => 'pay_wechat_enabled',
            'pay_alipay_enabled' => 'pay_alipay_enabled',
            'pay_wechat_app_id' => 'pay_wechat_app_id',
            'pay_wechat_mch_id' => 'pay_wechat_mch_id',
            'pay_wechat_api_key' => 'pay_wechat_api_key',
            'pay_wechat_sandbox' => 'pay_wechat_sandbox',
            'pay_wechat_notify_url' => 'pay_wechat_notify_url',
            'pay_alipay_app_id' => 'pay_alipay_app_id',
            'pay_alipay_sandbox' => 'pay_alipay_sandbox',
            'pay_alipay_notify_url' => 'pay_alipay_notify_url',
            'pay_alipay_return_url' => 'pay_alipay_return_url',
        ];

        try {
            foreach ($configMap as $field => $configKey) {
                if (isset($data[$field])) {
                    \app\common\model\Config::where('name', $configKey)->update(['value' => $data[$field]]);
                }
            }

            // 清除配置缓存
            \app\common\service\CacheService::clearByTag(\think\facade\Config::get('cache.tag.config', 'i8j_config'));

            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }
}
