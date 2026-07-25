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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\api\ApiDocGenerator;

/**
 * API文档管理 - V2.9.1 M10
 */
class ApiDocController extends AdminBaseController
{
    /**
     * API文档首页
     */
    public function index()
    {
        $generator = new ApiDocGenerator();
        $spec = $generator->generateOpenAPISpec();

        $this->assign([
            'spec' => $spec,
            'paths' => $spec['paths'] ?? [],
        ]);
        return $this->view('/api_doc_index');
    }

    public function export()
    {
        $generator = new ApiDocGenerator();
        $spec = $generator->generateOpenAPISpec();

        $md = "# {$spec['info']['title']} API 文档\n\n";
        $md .= "版本: {$spec['info']['version']}\n\n";
        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $info) {
                $md .= "## " . strtoupper($method) . " `{$path}`\n";
                $md .= "- 说明: {$info['summary']}\n";
                $md .= "- 权限: `{$info['x-required-scope']}`\n\n";
            }
        }

        return response($md, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="api-doc-' . date('Ymd') . '.md"',
        ]);
    }
}
