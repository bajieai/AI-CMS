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
use app\common\service\ApiDocGenerator;

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
        $docs = $generator->scan();

        // 按分组整理
        $groups = [];
        foreach ($docs as $doc) {
            $groups[$doc['group']]['desc'] = $doc['group_desc'];
            $groups[$doc['group']]['items'][] = $doc;
        }

        $this->app->view->assign('groups', $groups);
        $this->app->view->assign('total', count($docs));
        return $this->app->view->fetch('/api_doc_index');
    }

    /**
     * 导出Markdown
     */
    public function export()
    {
        $generator = new ApiDocGenerator();
        $docs = $generator->scan();
        $markdown = $generator->toMarkdown($docs);

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="api-doc-' . date('Ymd') . '.md"',
        ]);
    }
}
