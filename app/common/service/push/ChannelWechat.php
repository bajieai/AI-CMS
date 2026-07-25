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

namespace app\common\service\push;

/**
 * 微信推送通道 - V2.9.18 D-1
 * 
 * 通过 Server酱 / PushPlus 等免费服务推送内容到微信
 * 配置格式: {"token":"xxx", "service":"server_chan|pushplus"}
 */
class ChannelWechat implements PushChannelInterface
{
    /** Server酱 API */
    const SERVER_CHAN_URL = 'https://sctapi.ftqq.com/%s.send';
    /** PushPlus API */
    const PUSHPLUS_URL = 'https://www.pushplus.plus/send';

    /**
     * 推送到微信
     */
    public function push(array $payload, array $config): array
    {
        $token   = $config['token'] ?? '';
        $service = $config['service'] ?? 'server_chan';

        if (empty($token)) {
            return $this->failResult('微信推送 Token 未配置');
        }

        $title   = $payload['title'] ?? '';
        $summary = $payload['summary'] ?? '';
        $url     = $payload['url'] ?? '';

        if ($service === 'pushplus') {
            return $this->pushByPushPlus($token, $title, $summary, $url);
        }
        return $this->pushByServerChan($token, $title, $summary, $url);
    }

    private function pushByServerChan(string $token, string $title, string $summary, string $url): array
    {
        $apiUrl = sprintf(self::SERVER_CHAN_URL, $token);

        $desp = $summary;
        if ($url) {
            $desp .= "\n\n链接：" . $url;
        }

        $postData = http_build_query([
            'title' => $title,
            'desp'  => $desp,
        ]);

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $responseBody = curl_exec($ch);
        $responseCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        $durationMs   = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        if ($curlError) {
            return [
                'success'       => false,
                'response_code' => 0,
                'response_body' => $curlError,
                'duration_ms'   => $durationMs,
                'error_msg'     => $curlError,
            ];
        }

        return [
            'success'       => $responseCode >= 200 && $responseCode < 300,
            'response_code' => $responseCode,
            'response_body' => mb_substr($responseBody, 0, 1000),
            'duration_ms'   => $durationMs,
            'error_msg'     => $responseCode >= 200 ? '' : "HTTP {$responseCode}",
        ];
    }

    private function pushByPushPlus(string $token, string $title, string $summary, string $url): array
    {
        $startTime = microtime(true);

        $payload = [
            'token'   => $token,
            'title'   => $title,
            'content' => $summary,
            'url'     => $url,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::PUSHPLUS_URL,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $responseBody = curl_exec($ch);
        $responseCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        $durationMs   = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        if ($curlError) {
            return [
                'success'       => false,
                'response_code' => 0,
                'response_body' => $curlError,
                'duration_ms'   => $durationMs,
                'error_msg'     => $curlError,
            ];
        }

        return [
            'success'       => $responseCode >= 200 && $responseCode < 300,
            'response_code' => $responseCode,
            'response_body' => mb_substr($responseBody, 0, 1000),
            'duration_ms'   => $durationMs,
            'error_msg'     => $responseCode >= 200 ? '' : "HTTP {$responseCode}",
        ];
    }

    private function failResult(string $error): array
    {
        return [
            'success'       => false,
            'response_code' => 0,
            'response_body' => '',
            'duration_ms'   => 0,
            'error_msg'     => $error,
        ];
    }
}
