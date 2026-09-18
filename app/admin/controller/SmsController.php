<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ConfigService;
use app\common\service\system\SmsService;
use think\facade\Json;

/**
 * 短信服务控制器
 * V2.9.38 SYS-INTEG-3 / V2.9.62 新增后台短信通道配置
 */
class SmsController extends AdminBaseController
{
    protected SmsService $service;

    public function __construct()
    {
        parent::__construct(app());
        $this->service = new SmsService();
    }

    public function index()
    {
        $available = $this->service->getAvailableAdapters();
        $this->assign('availableAdapters', $available);
        return $this->view('/sms/index');
    }

    public function templates()
    {
        $templates = \think\facade\Db::name('sms_template')->order('id', 'desc')->paginate(20);
        return $this->view('/sms/templates', ['templates' => $templates]);
    }

    public function logs()
    {
        $page = (int) $this->request->param('page', 1);
        try {
            $query = \think\facade\Db::name('sms_log')->order('id', 'desc');
            $total = $query->count();
            $list = $query->page($page, 20)->select()->toArray();
        } catch (\Throwable $e) {
            $total = 0;
            $list = [];
        }
        return $this->view('/sms/logs', ['total' => $total, 'list' => $list, 'page' => $page]);
    }

    /**
     * 短信通道配置（GET 渲染表单 / POST 保存）
     * V2.9.62: 此前短信凭据只能写在 .env，后台无配置入口；现提供可视化配置，保存到 i8j_config(sms_* 扁平键)
     */
    public function config()
    {
        // 密码/密钥类字段：留空表示不修改（保留原值）
        $passwordFields = ['sms_smsbao_password', 'sms_aliyun_access_secret', 'sms_tencent_secret_key', 'sms_qiniu_secret_key'];
        $fields = array_merge(
            ['sms_smsbao_username', 'sms_smsbao_password', 'sms_smsbao_sign_name', 'sms_smsbao_content_template'],
            ['sms_aliyun_access_key', 'sms_aliyun_access_secret', 'sms_aliyun_sign_name'],
            ['sms_tencent_secret_id', 'sms_tencent_secret_key', 'sms_tencent_sdk_app_id', 'sms_tencent_sign_name'],
            ['sms_qiniu_access_key', 'sms_qiniu_secret_key', 'sms_qiniu_sign_name'],
            ['sms_default']
        );
        $remarks = [
            'sms_smsbao_username'         => '短信宝账号',
            'sms_smsbao_password'         => '短信宝密码(明文，MD5前)',
            'sms_smsbao_sign_name'        => '短信宝签名(不含【】括号)',
            'sms_smsbao_content_template' => '短信宝验证码模板({code}=验证码,{expire}=分钟数，留空用默认)',
            'sms_aliyun_access_key'       => '阿里云AccessKey ID',
            'sms_aliyun_access_secret'    => '阿里云AccessKey Secret',
            'sms_aliyun_sign_name'        => '阿里云短信签名',
            'sms_tencent_secret_id'       => '腾讯云SecretId',
            'sms_tencent_secret_key'      => '腾讯云SecretKey',
            'sms_tencent_sdk_app_id'      => '腾讯云SDK AppID',
            'sms_tencent_sign_name'       => '腾讯云短信签名',
            'sms_qiniu_access_key'        => '七牛云AccessKey',
            'sms_qiniu_secret_key'        => '七牛云SecretKey',
            'sms_qiniu_sign_name'         => '七牛云短信签名',
            'sms_default'                 => '默认短信通道(smsbao/aliyun/tencent/qiniu，留空按优先级自动选择)',
        ];

        if ($this->request->isPost()) {
            foreach ($fields as $key) {
                $value = $this->request->post($key, '');
                if (in_array($key, $passwordFields, true) && $value === '') {
                    continue; // 保留原值
                }
                ConfigService::set($key, $value, 'sms', $remarks[$key] ?? '');
            }
            return Json::success('配置保存成功');
        }

        $config = [];
        foreach ($fields as $key) {
            $config[$key] = ConfigService::get($key, '');
        }
        $this->assign('config', $config);
        return $this->view('/sms/config');
    }

    /**
     * 发送测试短信（后台「发送测试」按钮）
     * V2.9.62: 修复原测试表单发送 content 但控制器读 template_code 的错位 bug，统一改为发送测试验证码
     */
    public function send()
    {
        $mobile = $this->request->param('mobile', '');
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return json(['code' => 1, 'msg' => '请输入正确的手机号']);
        }
        try {
            $result = $this->service->sendVerifyCode($mobile, 'test');
            return Json::success('测试短信已发送，请查收手机', $result);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '发送失败：' . $e->getMessage()]);
        }
    }
}
