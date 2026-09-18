<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Log;

/**
 * 短信宝适配器 - V2.9.55
 *
 * 短信宝（smsbao.com）：门槛低（个人注册即可）、按条计费、无需模板报备，
 * 适合低成本起步与真实短信测试。
 *
 * API 文档: https://www.smsbao.com/open.html
 *   发送: GET https://api.smsbao.com/sms?u={账号}&p={MD5密码}&m={手机号}&c={urlencode(【签名】内容)}
 *   返回状态码: 0=成功 30=密码错误 40=账号不存在 41=余额不足 43=IP限制 50=内容敏感 51=手机号错误
 *
 * 配置（config/sms.php 的 smsbao 段 / .env）:
 *   SMS_SMSBAO_USERNAME  短信宝账号
 *   SMS_SMSBAO_PASSWORD  短信宝密码（明文，内部 MD5 后传输）
 *   SMS_SMSBAO_SIGN_NAME 短信签名（短信宝后台申请，如"AI-CMS"，不含括号）
 */
class SmsbaoSmsAdapter implements SmsAdapterInterface
{
    /** 短信宝 API 状态码 → 中文错误 */
    private const STATUS_MAP = [
        '0'  => '发送成功',
        '30' => '密码错误',
        '40' => '账号不存在',
        '41' => '余额不足',
        '43' => 'IP地址限制',
        '50' => '内容含有敏感词',
        '51' => '手机号码不正确',
        '-1' => '参数不全',
        '-2' => '服务器空间不支持，请确认支持curl或者fsocket',
        '-3' => '短信数量超过限制',
        '-4' => '短信内容为空',
    ];

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 发送短信（真实调用短信宝 HTTP API）
     *
     * 短信宝为内容型短信（无模板概念），$templateCode 忽略，
     * 内容 = 【签名】+ 内容模板（params.code 填充），默认验证码文案。
     */
    public function send(string $mobile, string $templateCode, array $params): array
    {
        $username = trim((string) ($this->config['username'] ?? ''));
        $password = (string) ($this->config['password'] ?? '');
        $signName = trim((string) ($this->config['sign_name'] ?? ''));

        if ($username === '' || $password === '') {
            throw new \RuntimeException('短信宝账号或密码未配置');
        }
        if ($signName === '') {
            throw new \RuntimeException('短信宝签名未配置（短信宝后台申请的签名，不含【】括号）');
        }

        // 内容模板：优先取配置，缺省为验证码文案
        $template = trim((string) ($this->config['content_template'] ?? ''))
            ?: '您的验证码为{code}，5分钟内有效，请勿泄露给他人。';
        $content = str_replace(
            ['{code}', '{expire}'],
            [(string) ($params['code'] ?? ''), (string) ($params['expire'] ?? '5')],
            $template
        );
        // 短信宝要求内容前带【签名】
        $fullContent = '【' . $signName . '】' . $content;

        $url = 'https://api.smsbao.com/sms?' . http_build_query([
            'u' => $username,
            'p' => md5($password),
            'm' => $mobile,
            'c' => $fullContent, // http_build_query 自动 urlencode
        ]);

        [$status, $body] = $this->httpGet($url, 10);

        if ($status !== '0') {
            $msg = self::STATUS_MAP[$status] ?? ("未知错误码{$status}");
            Log::error("[Smsbao] 发送失败: mobile={$mobile} status={$status} msg={$msg}");
            throw new \RuntimeException("短信宝发送失败({$status}): {$msg}");
        }

        Log::info("[Smsbao] 发送成功: mobile={$mobile}");
        return [
            'channel' => 'smsbao',
            'mobile'  => $mobile,
            'msg_id'  => uniqid('smsbao_'),
            'status'  => 'sent',
            'raw'     => $body,
        ];
    }

    /**
     * 查询余额（短信宝扩展能力，返回剩余短信条数；异常返回 -1）
     */
    public function getBalance(): int
    {
        $username = trim((string) ($this->config['username'] ?? ''));
        $password = (string) ($this->config['password'] ?? '');
        if ($username === '' || $password === '') {
            return -1;
        }
        try {
            [, $body] = $this->httpGet('https://api.smsbao.com/query?' . http_build_query([
                'u' => $username, 'p' => md5($password),
            ]), 10);
            // 返回格式 "状态,剩余条数"，如 "0,98"
            $parts = explode(',', trim($body));
            return (int) ($parts[1] ?? -1);
        } catch (\Throwable) {
            return -1;
        }
    }

    /**
     * GET 请求，返回 [状态码字符串, 原始响应体]
     */
    protected function httpGet(string $url, int $timeout = 10): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false, // 短信宝证书链兼容
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('短信宝网络请求失败: ' . $err);
        }
        return [trim((string) $body), (string) $body];
    }

    public function getName(): string
    {
        return 'smsbao';
    }
}
