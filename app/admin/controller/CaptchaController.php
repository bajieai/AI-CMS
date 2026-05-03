<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\CaptchaService;

/**
 * 验证码管理后台控制器 - V2.5新增
 */
class CaptchaController extends AdminBaseController
{
    /**
     * 验证码配置
     */
    public function config()
    {
        if ($this->request->isPost()) {
            $data = [
                'captcha_enabled'      => (int) $this->request->post('captcha_enabled', 0),
                'captcha_type'         => $this->request->post('captcha_type', 'math'),
                'captcha_login'        => (int) $this->request->post('captcha_login', 0),
                'captcha_register'     => (int) $this->request->post('captcha_register', 1),
                'captcha_comment'      => (int) $this->request->post('captcha_comment', 0),
                'captcha_form'         => (int) $this->request->post('captcha_form', 0),
                'captcha_fail_limit'   => (int) $this->request->post('captcha_fail_limit', 3),
            ];

            try {
                foreach ($data as $key => $value) {
                    \app\common\service\ConfigService::set($key, $value);
                }
                \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_CONFIG);
                return json(['code' => 0, 'msg' => '配置保存成功']);
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }

        $config = [
            'captcha_enabled'    => \app\common\service\ConfigService::get('captcha_enabled', 1),
            'captcha_type'       => \app\common\service\ConfigService::get('captcha_type', 'math'),
            'captcha_login'      => \app\common\service\ConfigService::get('captcha_login', 0),
            'captcha_register'   => \app\common\service\ConfigService::get('captcha_register', 1),
            'captcha_comment'    => \app\common\service\ConfigService::get('captcha_comment', 0),
            'captcha_form'       => \app\common\service\ConfigService::get('captcha_form', 0),
            'captcha_fail_limit' => \app\common\service\ConfigService::get('captcha_fail_limit', 3),
        ];

        $this->assign('config', $config);
        return $this->view('/captcha_config');
    }
}
