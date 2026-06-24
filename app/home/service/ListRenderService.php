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

namespace app\home\service;

/**
 * 列表页渲染服务 (V2.9.29 C-3)
 * 
 * 列表页按模型选择模板，被CateController::listing()调用
 * 
 * Fallback链：栏目自定义列表模板 → 模型默认列表模板 → 系统默认list
 */
class ListRenderService
{
    private ContentRenderService $renderService;

    public function __construct()
    {
        $this->renderService = new ContentRenderService();
    }

    /**
     * 渲染列表页
     * 
     * @param int $cateId 栏目ID
     * @param array $data 模板变量（列表数据、分页、栏目信息等）
     * @return string 渲染结果HTML
     */
    public function render(int $cateId, array $data = []): string
    {
        return $this->renderService->renderList($cateId, $data);
    }

    /**
     * 获取列表页模板路径
     */
    public function getTemplate(int $cateId): string
    {
        return $this->renderService->resolveListTemplate($cateId);
    }

    /**
     * 获取栏目对应的模型code
     */
    public function getModelCode(int $cateId): string
    {
        return $this->renderService->getCateModelCode($cateId);
    }
}
