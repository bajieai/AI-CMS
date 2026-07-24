<?php
declare(strict_types=1);

namespace app\common\service\platform;

use think\facade\Db;

/**
 * API文档增强服务
 * V2.9.38 OPEN-PLAT-3
 * 复用ApiDocService已有OpenAPI生成，增加Swagger UI集成+在线测试
 */
class ApiDocEnhanceService
{
    /**
     * 生成OpenAPI规范
     */
    public function generateOpenApiSpec(): array
    {
        $endpoints = $this->getAllEndpoints();
        $paths = [];
        foreach ($endpoints as $ep) {
            $paths[$ep['path']][$ep['method']] = [
                'summary' => $ep['desc'],
                'operationId' => str_replace('/', '_', $ep['path']) . '_' . strtolower($ep['method']),
                'tags' => [$ep['tag'] ?? 'default'],
                'parameters' => $ep['parameters'] ?? [],
                'responses' => ['200' => ['description' => '成功']],
            ];
        }
        return [
            'openapi' => '3.0.0',
            'info' => ['title' => 'AI-CMS API', 'version' => '2.9.38', 'description' => 'AI-CMS 开放API文档'],
            'servers' => [['url' => rtrim(request()->rootDomain(), '/') . '/api/v1']],
            'paths' => $paths,
        ];
    }

    /**
     * 获取Swagger UI
     */
    public function getSwaggerUi(): string
    {
        $spec = json_encode($this->generateOpenApiSpec(), JSON_UNESCAPED_UNICODE);
        return <<<HTML
<link rel="stylesheet" href="/assets/lib/swagger-ui/swagger-ui.css">
<div id="swagger-ui"></div>
<script src="/assets/lib/swagger-ui/swagger-ui-bundle.js"></script>
<script>
SwaggerUIBundle({url: "/api-docs/openapi.yaml", dom_id: "#swagger-ui"});
</script>
HTML;
    }

    /**
     * 在线测试API
     */
    public function testApi(string $method, string $path, array $params = [], string $apiKey = '', string $apiSecret = ''): array
    {
        // 生成HMAC-SHA256签名
        $timestamp = time();
        $nonce = md5(uniqid());
        $signature = hash_hmac('sha256', $method . $path . $timestamp . $nonce, $apiSecret);
        
        // 发起HTTP请求
        $url = rtrim(request()->rootDomain(), '/') . '/api/v1' . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . (strtoupper($method) === 'GET' ? '?' . http_build_query($params) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey,
                'X-Timestamp: ' . $timestamp,
                'X-Nonce: ' . $nonce,
                'X-Signature: ' . $signature,
            ],
            CURLOPT_POSTFIELDS => strtoupper($method) !== 'GET' ? json_encode($params) : '',
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'request' => ['method' => $method, 'path' => $path, 'params' => $params],
            'response' => ['code' => $httpCode, 'body' => $response],
        ];
    }

    /**
     * 获取变更日志
     */
    public function getChangeLog(int $page = 1, int $limit = 20): array
    {
        $query = Db::name('api_doc_changelog')->order('id', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    public function addChangeLog(string $version, string $title, string $content): bool
    {
        Db::name('api_doc_changelog')->insert([
            'version' => $version, 'title' => $title, 'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * 搜索文档
     */
    public function searchDocs(string $keyword): array
    {
        $endpoints = $this->getAllEndpoints();
        return array_filter($endpoints, fn($ep) => 
            stripos($ep['desc'], $keyword) !== false || stripos($ep['path'], $keyword) !== false
        );
    }

    protected function getAllEndpoints(): array
    {
        return [
            ['method' => 'get', 'path' => '/contents', 'desc' => '获取内容列表', 'tag' => '内容'],
            ['method' => 'get', 'path' => '/contents/{id}', 'desc' => '获取内容详情', 'tag' => '内容'],
            ['method' => 'post', 'path' => '/contents', 'desc' => '创建内容', 'tag' => '内容'],
            ['method' => 'put', 'path' => '/contents/{id}', 'desc' => '更新内容', 'tag' => '内容'],
            ['method' => 'delete', 'path' => '/contents/{id}', 'desc' => '删除内容', 'tag' => '内容'],
            ['method' => 'get', 'path' => '/categories', 'desc' => '获取分类列表', 'tag' => '分类'],
            ['method' => 'get', 'path' => '/categories/{id}', 'desc' => '获取分类详情', 'tag' => '分类'],
            ['method' => 'post', 'path' => '/files/upload', 'desc' => '上传文件', 'tag' => '文件'],
            ['method' => 'get', 'path' => '/files/{id}', 'desc' => '下载文件', 'tag' => '文件'],
            ['method' => 'get', 'path' => '/users/{id}', 'desc' => '获取用户信息', 'tag' => '用户'],
            ['method' => 'post', 'path' => '/ai/write', 'desc' => 'AI写作', 'tag' => 'AI'],
            ['method' => 'post', 'path' => '/ai/translate', 'desc' => 'AI翻译', 'tag' => 'AI'],
            ['method' => 'post', 'path' => '/ai/quality', 'desc' => 'AI质检', 'tag' => 'AI'],
            ['method' => 'get', 'path' => '/templates', 'desc' => '获取模板列表', 'tag' => '模板'],
        ];
    }
}
