<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app;

use think\exception\Handle;
use think\exception\HttpException;
use think\Request;
use think\Response;

/**
 * AI-CMS 自定义异常处理器 - V2.9.4
 * 为404等HTTP异常提供自定义页面
 */
class ExceptionHandle extends Handle
{
    public function render(Request $request, \Throwable $e): Response
    {
        // 404 页面自定义渲染
        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            $file = root_path() . 'template' . DIRECTORY_SEPARATOR . '404.html';
            if (is_file($file)) {
                $content = file_get_contents($file);
                return Response::create($content, 'html', 404);
            }
        }

        // 其他异常走父类处理
        return parent::render($request, $e);
    }
}
