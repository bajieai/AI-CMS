<?php

declare(strict_types=1);

use think\facade\Db;
use think\facade\Cache;

/**
 * 短信通知插件主类
 * 演示Action钩子发送短信通知
 */
class SmsNotifyPlugin
{
    /**
     * 内容审核通过后：通知作者
     */
    public function onContentAudit(array $data): void
    {
        $config = require __DIR__ . '/../config.php';
        if (empty($config['settings']['notify_audit'])) {
            return;
        }

        // 获取作者手机号
        $author = Db::name('member')
            ->where('id', $data['author_id'] ?? 0)
            ->field('mobile')
            ->find();

        if (empty($author['mobile'])) {
            return;
        }

        $status = $data['status'] === 'approved' ? '已通过' : '已驳回';
        $this->sendSms($author['mobile'], 'SMS_AUDIT_TEMPLATE', [
            'title'  => mb_substr($data['title'] ?? '', 0, 20),
            'status' => $status,
        ]);
    }

    /**
     * 用户注册后：通知管理员（可选）
     */
    public function onUserRegister(array $data): void
    {
        $config = require __DIR__ . '/../config.php';
        if (empty($config['settings']['notify_register'])) {
            return;
        }

        $adminMobile = $config['settings']['admin_mobile'] ?? '';
        if (empty($adminMobile)) {
            return;
        }

        $this->sendSms($adminMobile, 'SMS_REGISTER_TEMPLATE', [
            'username' => $data['username'] ?? '新用户',
        ]);
    }

    /**
     * 登录失败：连续失败超阈值时通知用户
     */
    public function onLoginFail(array $data): void
    {
        $config = require __DIR__ . '/../config.php';
        if (empty($config['settings']['notify_login_fail'])) {
            return;
        }

        $threshold = $config['settings']['login_fail_threshold'] ?? 5;

        // 统计最近30分钟失败次数
        $failCount = Cache::get('login_fail_' . ($data['username'] ?? ''), 0) + 1;
        Cache::set('login_fail_' . ($data['username'] ?? ''), $failCount, 1800);

        if ($failCount >= $threshold) {
            // 查找用户手机号并发送告警短信
            $user = Db::name('member')
                ->where('username', $data['username'] ?? '')
                ->whereOr('email', $data['username'] ?? '')
                ->field('mobile')
                ->find();

            if (!empty($user['mobile'])) {
                $this->sendSms($user['mobile'], 'SMS_LOGIN_FAIL_TEMPLATE', [
                    'count' => $failCount,
                ]);
            }
        }
    }

    /**
     * 发送短信（支持阿里云/腾讯云）
     */
    protected function sendSms(string $mobile, string $templateCode, array $params): bool
    {
        $config = require __DIR__ . '/../config.php';
        $provider = $config['settings']['sms_provider'] ?? 'aliyun';

        try {
            switch ($provider) {
                case 'aliyun':
                    return $this->sendViaAliyun($mobile, $templateCode, $params, $config['settings']);
                case 'tencent':
                    return $this->sendViaTencent($mobile, $templateCode, $params, $config['settings']);
                default:
                    return false;
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error("[SmsNotify] 发送失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 阿里云短信
     */
    protected function sendViaAliyun(string $mobile, string $templateCode, array $params, array $settings): bool
    {
        // 实际场景中调用阿里云SDK
        // $sdk = new \AlibabaCloud\Client\AlibabaCloud();
        // $result = $sdk::rpc()
        //     ->product('Dysmsapi')
        //     ->action('SendSms')
        //     ->options(['query' => [
        //         'PhoneNumbers'  => $mobile,
        //         'SignName'      => $settings['sms_sign_name'],
        //         'TemplateCode'  => $templateCode,
        //         'TemplateParam' => json_encode($params),
        //     ]])
        //     ->request();
        \think\facade\Log::info("[SmsNotify] 阿里云短信发送: mobile={$mobile}, template={$templateCode}");
        return true;
    }

    /**
     * 腾讯云短信
     */
    protected function sendViaTencent(string $mobile, string $templateCode, array $params, array $settings): bool
    {
        // 实际场景中调用腾讯云SDK
        \think\facade\Log::info("[SmsNotify] 腾讯云短信发送: mobile={$mobile}, template={$templateCode}");
        return true;
    }
}
