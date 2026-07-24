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

namespace app\common\service\ai;

use GuzzleHttp\Client;
use think\facade\Log;

/**
 * 外部API调用节点处理器 — V2.9.39 AI-DEEP-3
 *
 * 允许工作流调用外部HTTP API
 * 支持：GET/POST/PUT/DELETE方法、自定义请求头、请求体变量替换
 */
class ExternalApiNodeHandler
{
    /** 默认超时（秒） */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * 执行外部API调用节点
     * @param array $config 节点配置
     * @param array $targetIds 目标内容ID列表
     * @param array $context 上游节点输出上下文
     * @return array ['output' => [], 'ai_calls' => int, 'ai_cost' => float]
     */
    public function execute(array $config, array $targetIds, array $context = []): array
    {
        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'GET');
        $headers = $config['headers'] ?? [];
        $body = $config['body'] ?? '';
        $timeout = (int) ($config['timeout'] ?? self::DEFAULT_TIMEOUT);

        if (empty($url)) {
            throw new \RuntimeException('ExternalApi节点缺少url配置');
        }

        // 变量替换
        $url = $this->replaceVariables($url, $context);
        if (is_array($body)) {
            $body = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        $body = $this->replaceVariables($body, $context);

        // 替换请求头中的变量
        $processedHeaders = [];
        foreach ($headers as $key => $value) {
            $processedHeaders[$key] = $this->replaceVariables((string) $value, $context);
        }

        try {
            $client = new Client([
                'timeout' => $timeout,
                'verify'  => false,
            ]);

            $requestOptions = [];
            if (!empty($processedHeaders)) {
                $requestOptions['headers'] = $processedHeaders;
            }
            if (!empty($body) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $requestOptions['body'] = $body;
            }

            $response = $client->request($method, $url, $requestOptions);
            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            // 尝试解析JSON
            $parsed = json_decode($responseBody, true);
            $output = is_array($parsed) ? $parsed : ['raw' => $responseBody];

            return [
                'output' => [
                    'status_code' => $statusCode,
                    'response'    => $output,
                    'url'         => $url,
                    'method'      => $method,
                ],
                'ai_calls' => 0,
                'ai_cost'  => 0,
            ];
        } catch (\Throwable $e) {
            Log::error("ExternalApiNodeHandler failed: " . $e->getMessage());
            throw new \RuntimeException("外部API调用失败: " . $e->getMessage());
        }
    }

    /**
     * 变量替换
     * @param string $template 模板字符串
     * @param array $context 上下文数据
     * @return string
     */
    private function replaceVariables(string $template, array $context): string
    {
        $result = $template;

        foreach ($context as $nodeId => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_scalar($value)) {
                        $result = str_replace('{' . $nodeId . '.' . $key . '}', (string) $value, $result);
                    }
                }
            } elseif (is_scalar($data)) {
                $result = str_replace('{' . $nodeId . '}', (string) $data, $result);
            }
        }

        return $result;
    }

    /**
     * 获取节点配置schema
     * @return array
     */
    public static function getConfigSchema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'url',
                    'label' => 'API URL',
                    'type' => 'text',
                    'required' => true,
                    'description' => '支持变量替换：{node_id.field}',
                ],
                [
                    'name' => 'method',
                    'label' => 'HTTP方法',
                    'type' => 'select',
                    'options' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'default' => 'GET',
                ],
                [
                    'name' => 'headers',
                    'label' => '请求头',
                    'type' => 'key_value',
                    'required' => false,
                ],
                [
                    'name' => 'body',
                    'label' => '请求体',
                    'type' => 'textarea',
                    'required' => false,
                    'description' => 'POST/PUT/PATCH请求的请求体，支持JSON和变量替换',
                ],
                [
                    'name' => 'timeout',
                    'label' => '超时（秒）',
                    'type' => 'number',
                    'default' => self::DEFAULT_TIMEOUT,
                ],
            ],
        ];
    }
}
