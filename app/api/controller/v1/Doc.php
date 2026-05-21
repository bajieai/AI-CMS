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
            'authentication' => [
                'description' => '支持两种认证模式，由Token的auth_type字段决定，两者互斥',
                'modes' => [
                    'bearer' => [
                        'description' => 'Bearer Token认证，适用于服务端调用',
                        'header' => 'Authorization: Bearer <your_token>',
                        'example' => [
                            'method' => 'GET',
                            'url' => '/api/v1/content',
                            'headers' => [
                                'Authorization' => 'Bearer abc123def456...',
                            ],
                        ],
                    ],
                    'hmac' => [
                        'description' => 'HMAC-SHA256签名认证，适用于高安全场景，防篡改防重放',
                        'required_headers' => [
                            'Authorization'  => 'hmac <your_token_id>',
                            'X-Timestamp'    => 'Unix时间戳（秒），请求发出时的当前时间',
                            'X-Nonce'        => '随机字符串（UUID推荐），同一Nonce 1小时内不可重复使用',
                            'X-Signature'    => 'HMAC-SHA256签名值（十六进制）',
                        ],
                        'signature_calculation' => [
                            'step1' => '构造签名载荷（Payload），格式为：METHOD\\nURL\\nTIMESTAMP\\nNONCE\\nBODY',
                            'step2' => '使用Token的secret_key作为密钥，对Payload进行HMAC-SHA256运算',
                            'step3' => '将签名结果转为十六进制字符串，放入X-Signature头',
                        ],
                        'payload_format' => "{METHOD}\\n{FULL_URL}\\n{TIMESTAMP}\\n{NONCE}\\n{REQUEST_BODY}",
                        'payload_example' => "GET\\nhttps://example.com/api/v1/content\\n1714368000\\nuuid-1234-5678\\n",
                        'signature_example' => [
                            'payload' => "GET\nhttps://example.com/api/v1/content\n1714368000\nuuid-1234-5678\n",
                            'secret_key' => 'your_secret_key_from_token_creation',
                            'php_code' => "hash_hmac('sha256', \$payload, \$secretKey)",
                            'result' => 'a1b2c3d4e5f6...(64位十六进制字符串)',
                        ],
                        'constraints' => [
                            'timestamp_validity' => '请求时间戳与服务器时间差不得超过5分钟（300秒）',
                            'nonce_uniqueness' => '同一Nonce在1小时内不可重复使用（防重放攻击）',
                            'empty_body' => 'GET请求的Body部分为空字符串，但Payload中的换行符\\n仍需保留',
                        ],
                        'full_example' => [
                            'method' => 'GET',
                            'url' => '/api/v1/content',
                            'headers' => [
                                'Authorization' => 'hmac tk_abc123',
                                'X-Timestamp'   => '1714368000',
                                'X-Nonce'       => 'uuid-1234-5678',
                                'X-Signature'   => 'a1b2c3d4e5f6...(HMAC-SHA256签名)',
                            ],
                        ],
                    ],
                ],
            ],
            'scopes' => [
                'description' => 'Token通过scopes字段控制API访问权限，创建Token时指定，多个scope用逗号分隔',
                'available_scopes' => [
                    'content:read'  => '内容读取（列表/详情/搜索）',
                    'content:write' => '内容写入（创建/编辑/删除）',
                    'member:read'   => '会员信息读取',
                    'member:write'  => '会员信息写入',
                    'media:read'    => '媒体资源读取',
                    'media:write'   => '媒体上传',
                    'cate:read'     => '分类读取',
                    'admin:all'     => '管理员全权限（通配所有scope）',
                ],
            ],
            'endpoints' => [
                [
                    'path' => '/content',
                    'method' => 'GET',
                    'params' => ['page', 'limit', 'cate_id', 'type'],
                    'description' => '内容列表',
                    'required_scope' => 'content:read',
                ],
                [
                    'path' => '/content/:id',
                    'method' => 'GET',
                    'params' => [],
                    'description' => '内容详情',
                    'required_scope' => 'content:read',
                ],
                [
                    'path' => '/cate',
                    'method' => 'GET',
                    'params' => [],
                    'description' => '分类列表',
                    'required_scope' => 'cate:read',
                ],
                [
                    'path' => '/cate/tree',
                    'method' => 'GET',
                    'params' => [],
                    'description' => '分类树形结构',
                    'required_scope' => 'cate:read',
                ],
                [
                    'path' => '/comment',
                    'method' => 'GET',
                    'params' => ['content_id', 'page', 'limit'],
                    'description' => '评论列表',
                    'required_scope' => 'content:read',
                ],
                [
                    'path' => '/comment',
                    'method' => 'POST',
                    'params' => ['content_id', 'nickname', 'email', 'content', 'parent_id'],
                    'description' => '提交评论',
                    'required_scope' => 'content:write',
                ],
                [
                    'path' => '/media',
                    'method' => 'GET',
                    'params' => ['page', 'limit', 'filetype'],
                    'description' => '媒体资源列表',
                    'required_scope' => 'media:read',
                ],
            ],
        ];

        return json(['code' => 0, 'msg' => 'success', 'data' => $doc]);
    }
}
