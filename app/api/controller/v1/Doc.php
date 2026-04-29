<?php
declare(strict_types=1);

namespace app\api\controller\v1;

/**
 * API文档接口
 * 返回当前V1 API的所有可用端点说明
 */
class Doc
{
    public function index()
    {
        $doc = [
            'version' => 'v1',
            'base_url' => '/api/v1',
            'authentication' => 'Bearer Token or HMAC-SHA256 (via ApiAuth middleware)',
            'endpoints' => [
                [
                    'path' => '/content',
                    'method' => 'GET',
                    'params' => ['page', 'limit', 'cate_id', 'type'],
                    'description' => '内容列表',
                ],
                [
                    'path' => '/content/:id',
                    'method' => 'GET',
                    'params' => [],
                    'description' => '内容详情',
                ],
                [
                    'path' => '/cate',
                    'method' => 'GET',
                    'params' => [],
                    'description' => '分类列表',
                ],
                [
                    'path' => '/cate/tree',
                    'method' => 'GET',
                    'params' => [],
                    'description' => '分类树形结构',
                ],
                [
                    'path' => '/comment',
                    'method' => 'GET',
                    'params' => ['content_id', 'page', 'limit'],
                    'description' => '评论列表',
                ],
                [
                    'path' => '/comment',
                    'method' => 'POST',
                    'params' => ['content_id', 'nickname', 'email', 'content', 'parent_id'],
                    'description' => '提交评论',
                ],
                [
                    'path' => '/media',
                    'method' => 'GET',
                    'params' => ['page', 'limit', 'filetype'],
                    'description' => '媒体资源列表',
                ],
            ],
        ];

        return json(['code' => 0, 'msg' => 'success', 'data' => $doc]);
    }
}
