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

namespace app\home\controller;

use app\common\model\TemplateStore;
use think\Controller;

/**
 * 模板前台预览控制器 - V2.9.12新增
 */
class TemplatePreviewController extends Controller
{
    /**
     * 前台iframe预览壳
     */
    public function preview(string $slug)
    {
        $store = TemplateStore::where('slug', $slug)->find();
        if (empty($store)) {
            return $this->error('模板不存在');
        }

        // 预览内容URL：优先使用已安装的主题目录，否则使用占位预览
        $previewUrl = '/skin/' . $slug . '/index.html';

        $this->assign([
            'store' => $store,
            'preview_url' => $previewUrl,
        ]);

        return $this->fetch('/template_preview');
    }
}
