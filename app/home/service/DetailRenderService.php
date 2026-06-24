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
 * 详情页渲染服务 (V2.9.29 C-3)
 * 
 * 详情页按模型选择模板，被ContentController::detail()调用
 * 
 * Fallback链：栏目自定义详情模板 → 模型默认详情模板 → 系统默认detail
 */
class DetailRenderService
{
    private ContentRenderService $renderService;

    public function __construct()
    {
        $this->renderService = new ContentRenderService();
    }

    /**
     * 渲染详情页
     * 
     * @param int $cateId 栏目ID
     * @param array $data 模板变量（内容详情、上下篇、关联内容等）
     * @return string 渲染结果HTML
     */
    public function render(int $cateId, array $data = []): string
    {
        return $this->renderService->renderDetail($cateId, $data);
    }

    /**
     * 获取详情页模板路径
     */
    public function getTemplate(int $cateId): string
    {
        return $this->renderService->resolveDetailTemplate($cateId);
    }

    /**
     * 获取栏目对应的模型code
     */
    public function getModelCode(int $cateId): string
    {
        return $this->renderService->getCateModelCode($cateId);
    }
}
