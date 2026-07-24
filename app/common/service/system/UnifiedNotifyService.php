<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;

/**
 * 统一通知中心服务
 * V2.9.38 SYS-INTEG-5
 * 通道适配器模式: SmsService/MailServiceV2/NotificationService/WechatTemplateService各实现NotifyChannelInterface
 * 
 * 通知场景→默认通道→默认模板映射表:
 * | 场景       | 默认通道         | 默认模板           |
 * | 注册       | sms,email        | welcome_sms,welcome_email |
 * | 支付       | sms,email,in_app | pay_success_sms,pay_success_email,pay_success_inapp |
 * | 审核       | in_app,email     | audit_result_inapp,audit_result_email |
 * | 系统       | in_app           | system_notice_inapp |
 * | 安全       | sms,email        | security_alert_sms,security_alert_email |
 * | 运营       | in_app,sms       | ops_notice_inapp,ops_notice_sms |
 * | 自定义     | (用户配置)       | (用户配置)         |
 */
class UnifiedNotifyService
{
    protected array $channels = [];
    protected NotifyChannelManager $channelManager;

    public function __construct()
    {
        $this->channelManager = new NotifyChannelManager();
        // 注册通道
        $this->channels['sms'] = new SmsService();
        $this->channels['email'] = new MailServiceV2();
        $this->channels['in_app'] = new InAppNotifyChannel();
        $this->channels['wechat'] = new WechatTemplateChannel();
    }

    /**
     * 发送通知(自动路由多通道)
     */
    public function send($to, string $scenario, array $params = [], ?array $channels = null): array
    {
        // 获取场景配置
        $scenarioConfig = $this->getScenarioConfig($scenario);
        $targetChannels = $channels ?? $scenarioConfig['channels'] ?? ['in_app'];
        
        $results = [];
        foreach ($targetChannels as $channel) {
            if (!isset($this->channels[$channel])) continue;
            try {
                $templateCode = $scenarioConfig['templates'][$channel] ?? '';
                $result = match($channel) {
                    'sms' => $this->channels['sms']->send($to, $templateCode, $params),
                    'email' => $this->channels['email']->send($to, $templateCode, $params),
                    'in_app' => $this->channels['in_app']->send($to, $templateCode, $params),
                    'wechat' => $this->channels['wechat']->send($to, $templateCode, $params),
                    default => ['error' => 'Unknown channel'],
                };
                $results[$channel] = ['status' => 'success', 'result' => $result];
                $this->logNotify($to, $scenario, $channel, 'success', $result);
            } catch (\Throwable $e) {
                $results[$channel] = ['status' => 'failed', 'error' => $e->getMessage()];
                $this->logNotify($to, $scenario, $channel, 'failed', ['error' => $e->getMessage()]);
            }
        }
        
        return $results;
    }

    public function sendBatch(array $recipients, string $scenario, array $params = [], ?array $channels = null): array
    {
        $results = [];
        foreach ($recipients as $to) {
            $results[$to] = $this->send($to, $scenario, $params, $channels);
        }
        return $results;
    }

    protected function getScenarioConfig(string $scenario): array
    {
        $configs = [
            'register' => ['channels' => ['sms', 'email'], 'templates' => ['sms' => 'welcome_sms', 'email' => 'welcome_email']],
            'payment' => ['channels' => ['sms', 'email', 'in_app'], 'templates' => ['sms' => 'pay_success_sms', 'email' => 'pay_success_email', 'in_app' => 'pay_success_inapp']],
            'audit' => ['channels' => ['in_app', 'email'], 'templates' => ['in_app' => 'audit_result_inapp', 'email' => 'audit_result_email']],
            'system' => ['channels' => ['in_app'], 'templates' => ['in_app' => 'system_notice_inapp']],
            'security' => ['channels' => ['sms', 'email'], 'templates' => ['sms' => 'security_alert_sms', 'email' => 'security_alert_email']],
            'operation' => ['channels' => ['in_app', 'sms'], 'templates' => ['in_app' => 'ops_notice_inapp', 'sms' => 'ops_notice_sms']],
        ];
        return $configs[$scenario] ?? ['channels' => ['in_app'], 'templates' => []];
    }

    protected function logNotify($to, string $scenario, string $channel, string $status, array $result): void
    {
        Db::name('notify_log')->insert([
            'notify_to' => is_array($to) ? json_encode($to) : $to,
            'notify_scenario' => $scenario, 'notify_channel' => $channel,
            'notify_priority' => 'normal', 'status' => $status,
            'channel_result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
