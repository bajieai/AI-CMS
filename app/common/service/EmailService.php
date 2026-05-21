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

namespace app\common\service;

use app\common\model\EmailLog;
use app\common\model\EmailQueue;
use app\common\model\EmailTemplate;
use app\common\traits\RedisQueueTrait;
use think\facade\Log;

/**
 * 邮件服务 - V2.5
 * SMTP发送 + 队列处理 + 模板渲染
 */
class EmailService
{
    use RedisQueueTrait;
    /**
     * 缓存队列键名
     */
    protected static string $queueKey = 'email_queue_pending';

    /**
     * 根据模板发送邮件（同步发送）
     */
    public static function sendByTemplate(string $templateCode, string $toEmail, array $vars = []): bool
    {
        $template = EmailTemplate::where('code', $templateCode)->where('is_enabled', 1)->find();
        if (!$template) {
            Log::warning("邮件模板不存在或已禁用: {$templateCode}");
            return false;
        }

        $subject = self::renderVars($template->subject, $vars);
        $body = self::renderVars($template->body, $vars);

        $result = self::send($toEmail, $subject, $body);

        // 记录日志
        EmailLog::create([
            'template_code' => $templateCode,
            'to_email' => $toEmail,
            'subject' => $subject,
            'status' => $result ? 1 : 2,
            'error_msg' => $result ? '' : 'SMTP发送失败',
            'send_time' => $result ? time() : 0,
            'create_time' => time(),
        ]);

        return $result;
    }

    /**
     * 将邮件加入发送队列（异步）
     * V2.7: 先写DB持久化，再入Redis队列
     */
    public static function queue(string $templateCode, string $toEmail, array $vars = []): bool
    {
        // 先写DB持久化（status=0）
        $dbId = EmailQueue::enqueue($templateCode, $toEmail, $vars);
        // 再入Redis队列（携带db_id，处理时用于更新状态）
        return self::queuePush(self::$queueKey, [
            'db_id'        => $dbId,
            'template_code' => $templateCode,
            'to_email'      => $toEmail,
            'vars'          => $vars,
            'retry'          => 0,
            'create_time'    => time(),
        ]);
    }

    /**
     * 处理邮件发送队列
     * @return array ['success' => int, 'fail' => int]
     */
    public static function processQueue(int $limit = 100): array
    {
        $success = 0;
        $fail = 0;
        $remaining = [];

        for ($i = 0; $i < $limit; $i++) {
            $item = self::queuePop(self::$queueKey);
            if ($item === null) {
                break;
            }

            $dbId  = $item['db_id'] ?? 0;
            $result = self::sendByTemplate($item['template_code'], $item['to_email'], $item['vars'] ?? []);
            if ($result) {
                $success++;
                // 更新DB状态为已发
                if ($dbId > 0) {
                    EmailQueue::markSent($dbId);
                }
            } else {
                $fail++;
                if ($dbId > 0) {
                    // 通过DB记录重试次数，超过max_retries则标记为失败并不再重试
                    $shouldRetry = EmailQueue::markFailed($dbId, 'SMTP发送失败');
                    if ($shouldRetry) {
                        $item['retry'] = ($item['retry'] ?? 0) + 1;
                        $remaining[] = $item;
                    }
                } else {
                    // 无DB记录，按原逻辑处理
                    $item['retry'] = ($item['retry'] ?? 0) + 1;
                    if ($item['retry'] < 3) {
                        $remaining[] = $item;
                    }
                }
            }
        }

        // 将重试项重新入队（保持FIFO顺序）
        foreach ($remaining as $item) {
            self::queuePush(self::$queueKey, $item);
        }

        Log::info("邮件队列处理完成: 成功{$success}封，失败{$fail}封");
        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * 测试发送邮件
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testSend(string $toEmail): array
    {
        $subject = 'AI-CMS 邮件发送测试';
        $body = '<h2>测试邮件</h2><p>这是一封来自 AI-CMS 系统的测试邮件。</p><p>发送时间：' . date('Y-m-d H:i:s') . '</p>';

        $result = self::send($toEmail, $subject, $body);

        EmailLog::create([
            'template_code' => 'test',
            'to_email' => $toEmail,
            'subject' => $subject,
            'status' => $result ? 1 : 2,
            'error_msg' => $result ? '' : 'SMTP测试发送失败',
            'send_time' => $result ? time() : 0,
            'create_time' => time(),
        ]);

        return [
            'success' => $result,
            'message' => $result ? '测试邮件发送成功' : '测试邮件发送失败，请检查SMTP配置',
        ];
    }

    /**
     * 底层SMTP发送
     */
    protected static function send(string $toEmail, string $subject, string $body): bool
    {
        $config = self::getSmtpConfig();
        if (empty($config['host']) || empty($config['username'])) {
            Log::warning('SMTP未配置，跳过邮件发送');
            return false;
        }

        try {
            $headers = self::buildHeaders($config, $toEmail, $subject);
            $message = self::buildMessage($headers, $body);

            $port = (int) $config['port'];
            $ssl = (bool) $config['ssl'];
            $host = $ssl ? 'ssl://' . $config['host'] : $config['host'];

            $socket = @fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket) {
                Log::error("SMTP连接失败: {$errstr} ({$errno})");
                return false;
            }

            self::smtpCommand($socket, '', 220);
            $hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::smtpCommand($socket, 'EHLO ' . $hostName, 250);

            if ($ssl || $port === 587) {
                self::smtpCommand($socket, 'STARTTLS', 220);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::smtpCommand($socket, 'EHLO ' . $hostName, 250);
            }

            // AUTH LOGIN
            self::smtpCommand($socket, 'AUTH LOGIN', 334);
            self::smtpCommand($socket, base64_encode($config['username']), 334);
            self::smtpCommand($socket, base64_encode($config['password']), 235);

            self::smtpCommand($socket, 'MAIL FROM: <' . $config['from_email'] . '>', 250);
            self::smtpCommand($socket, 'RCPT TO: <' . $toEmail . '>', 250);
            self::smtpCommand($socket, 'DATA', 354);

            fwrite($socket, $message . "\r\n.\r\n");
            self::smtpResponse($socket, 250);

            self::smtpCommand($socket, 'QUIT', 221);
            fclose($socket);

            return true;
        } catch (\Exception $e) {
            Log::error('SMTP发送异常: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取SMTP配置
     */
    protected static function getSmtpConfig(): array
    {
        return [
            'host' => ConfigService::get('smtp_host', ''),
            'port' => ConfigService::get('smtp_port', '465'),
            'username' => ConfigService::get('smtp_username', ''),
            'password' => ConfigService::get('smtp_password', ''),
            'from_email' => ConfigService::get('smtp_from_email', ''),
            'from_name' => ConfigService::get('smtp_from_name', ''),
            'ssl' => ConfigService::get('smtp_ssl', '1') === '1',
        ];
    }

    /**
     * 构建邮件头
     */
    protected static function buildHeaders(array $config, string $toEmail, string $subject): string
    {
        $from = $config['from_email'];
        $fromName = $config['from_name'] ?: 'AI-CMS';

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
        $headers .= "To: <{$toEmail}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "X-Mailer: AI-CMS V2.5\r\n";

        return $headers;
    }

    /**
     * 构建完整邮件内容
     */
    protected static function buildMessage(string $headers, string $body): string
    {
        return $headers . "\r\n" . $body;
    }

    /**
     * 发送SMTP命令
     */
    protected static function smtpCommand($socket, string $command, int $expectedCode): void
    {
        if ($command !== '') {
            fwrite($socket, $command . "\r\n");
        }
        self::smtpResponse($socket, $expectedCode);
    }

    /**
     * 读取SMTP响应
     */
    protected static function smtpResponse($socket, int $expectedCode): void
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new \Exception("SMTP响应错误: {$response}");
        }
    }

    /**
     * 渲染模板变量 {{var}} → value
     */
    protected static function renderVars(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }
}
