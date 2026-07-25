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
 * Webhook 推送通道 - V2.9.18 D-1
 * 
 * 通过 HTTP POST/PUT 将内容推送到第三方 Webhook 端点
 */
class ChannelWebhook implements PushChannelInterface
{
    /**
     * 执行 Webhook 推送
     */
    public function push(array $payload, array $config): array
    {
        $url    = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'POST');
        $format = strtolower($config['format'] ?? 'json');
        $headers = $config['headers'] ?? [];
        $timeout = (int) ($config['timeout'] ?? 30);

        if (empty($url)) {
            return $this->failResult('Webhook URL 未配置');
        }

        // 构建请求体
        if ($format === 'xml') {
            $body = $this->arrayToXml($payload);
            $headers['Content-Type'] = 'application/xml';
        } else {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $headers['Content-Type'] = 'application/json';
        }

        // 构建 header 字符串
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "$key: $value";
        }

        $startTime = microtime(true);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $responseBody   = curl_exec($ch);
        $responseCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError      = curl_error($ch);
        $durationMs     = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        // 截断过长的响应体
        $responseBody = mb_substr((string) $responseBody, 0, 2000);

        if ($curlError) {
            return [
                'success'       => false,
                'response_code' => 0,
                'response_body' => $responseBody ?: $curlError,
                'duration_ms'   => $durationMs,
                'error_msg'     => $curlError,
            ];
        }

        $success = $responseCode >= 200 && $responseCode < 300;

        return [
            'success'       => $success,
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'duration_ms'   => $durationMs,
            'error_msg'     => $success ? '' : "HTTP {$responseCode}",
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

    private function arrayToXml(array $data, string $root = 'push'): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<{$root}>";
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= $this->arrayToXml($value, $key);
            } else {
                $xml .= "<{$key}>" . htmlspecialchars((string) $value, ENT_XML1, 'UTF-8') . "</{$key}>";
            }
        }
        $xml .= "</{$root}>";
        return $xml;
    }
}
