<?php
declare(strict_types=1);
namespace app\common\service\api;

/**
 * API文档生成服务 (V2.9.29 D-5)
 * 自动生成OpenAPI/Swagger格式文档
 */
class ApiDocGenerator
{
    /**
     * 生成OpenAPI文档
     */
    public function generateOpenAPISpec(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'AI-CMS API',
                'version' => '2.9.29',
                'description' => 'AI-CMS 开放API平台',
            ],
            'servers' => [
                ['url' => '/api', 'description' => 'API服务器'],
            ],
            'paths' => $this->getPaths(),
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                    ],
                ],
            ],
        ];
    }

    private function getPaths(): array
    {
        return [
            '/content' => [
                'get' => $this->buildPath('获取内容列表', ['page' => 'int', 'limit' => 'int'], 'content:read'),
                'post' => $this->buildPath('创建内容', ['title' => 'string', 'content' => 'string'], 'content:write'),
            ],
            '/content/{id}' => [
                'get' => $this->buildPath('获取内容详情', ['id' => 'int'], 'content:read'),
                'put' => $this->buildPath('更新内容', ['id' => 'int', 'title' => 'string'], 'content:write'),
                'delete' => $this->buildPath('删除内容', ['id' => 'int'], 'content:delete'),
            ],
            '/category' => [
                'get' => $this->buildPath('获取栏目列表', [], 'category:read'),
                'post' => $this->buildPath('创建栏目', ['name' => 'string', 'type' => 'int'], 'category:write'),
            ],
            '/template' => [
                'get' => $this->buildPath('获取模板列表', [], 'template:read'),
            ],
            '/file/upload' => [
                'post' => $this->buildPath('上传文件', ['file' => 'file'], 'file:write'),
            ],
        ];
    }

    private function buildPath(string $summary, array $params, string $scope): array
    {
        return [
            'summary' => $summary,
            'security' => [['ApiKeyAuth' => []]],
            'x-required-scope' => $scope,
            'responses' => [
                '200' => ['description' => '成功'],
                '401' => ['description' => '未认证'],
                '403' => ['description' => '无权限'],
                '429' => ['description' => '频率超限'],
            ],
        ];
    }
}
