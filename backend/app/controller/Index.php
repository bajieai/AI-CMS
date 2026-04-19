<?php
declare(strict_types=1);

namespace app\controller;

use think\Response;

/**
 * 首页控制器
 */
class Index
{
    /**
     * 首页 - API 状态检查
     */
    public function index(): Response
    {
        return json([
            'code' => 200,
            'message' => 'AI-CMS API is running',
            'data' => [
                'version' => '1.0.0',
                'framework' => 'ThinkPHP',
                'timestamp' => time(),
            ],
        ]);
    }
}
