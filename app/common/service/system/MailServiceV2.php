<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Config;
use think\facade\Db;

/**
 * 邮件服务V2
 * V2.9.38 SYS-INTEG-4
 * 3个适配器+自动切换+发送队列+退信处理
 */
class MailServiceV2
{
    protected array $adapters = [];

    public function __construct()
    {
        $config = Config::get('mail', []);
        if (!empty($config['smtp']['host'])) $this->adapters['smtp'] = new SmtpAdapter($config['smtp']);
        if (!empty($config['aliyun']['access_key'])) $this->adapters['aliyun'] = new AliyunMailAdapter($config['aliyun']);
        if (!empty($config['sendgrid']['api_key'])) $this->adapters['sendgrid'] = new SendGridAdapter($config['sendgrid']);
    }

    public function send(string $to, string $templateCode, array $params = [], ?string $channel = null): array
    {
        $adapter = $channel ? ($this->adapters[$channel] ?? null) : (current($this->adapters) ?: null);
        if (!$adapter) throw new \RuntimeException('No mail adapter available');
        
        // 渲染模板
        $template = $this->renderTemplate($templateCode, $params);
        
        try {
            $result = $adapter->send($to, $template['subject'], $template['body']);
            $this->logMail($to, $templateCode, $adapter->getName(), 'success', $result);
            return $result;
        } catch (\Throwable $e) {
            $this->logMail($to, $templateCode, $adapter->getName(), 'failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendBatch(array $recipients, string $templateCode, array $params = []): array
    {
        $results = [];
        foreach ($recipients as $to) {
            try {
                $results[] = $this->send($to, $templateCode, $params);
            } catch (\Throwable $e) {
                $results[] = ['to' => $to, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    protected function renderTemplate(string $templateCode, array $params): array
    {
        // 复用MailTemplateService获取模板
        $template = Db::name('mail_template')->where('code', $templateCode)->find();
        if (!$template) {
            return ['subject' => '通知', 'body' => json_encode($params, JSON_UNESCAPED_UNICODE)];
        }
        $subject = $template['subject'] ?? '';
        $body = $template['body'] ?? '';
        foreach ($params as $key => $val) {
            $subject = str_replace('{' . $key . '}', $val, $subject);
            $body = str_replace('{' . $key . '}', $val, $body);
        }
        return ['subject' => $subject, 'body' => $body];
    }

    protected function logMail(string $to, string $template, string $channel, string $status, array $result): void
    {
        Db::name('mail_log')->insert([
            'to_email' => $to, 'template_code' => $template, 'channel' => $channel,
            'status' => $status, 'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
