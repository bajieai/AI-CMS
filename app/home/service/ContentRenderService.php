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

use app\common\service\content\ContentModelService;
use think\facade\View;

/**
 * 内容渲染核心服务 (V2.9.29 C-3)
 * 
 * 实现模板Fallback链：
 * 栏目自定义模板 → 模型默认模板 → 系统默认模板
 * 
 * 被ListRenderService和DetailRenderService调用
 */
class ContentRenderService
{
    private ContentModelService $modelService;

    public function __construct()
    {
        $this->modelService = new ContentModelService();
    }

    /**
     * 解析列表页模板路径
     * 
     * @param int $cateId 栏目ID
     * @return string 模板路径（不含.html后缀）
     */
    public function resolveListTemplate(int $cateId): string
    {
        return $this->modelService->resolveListTemplate($cateId);
    }

    /**
     * 解析详情页模板路径
     * 
     * @param int $cateId 栏目ID
     * @return string 模板路径
     */
    public function resolveDetailTemplate(int $cateId): string
    {
        return $this->modelService->resolveDetailTemplate($cateId);
    }

    /**
     * 渲染列表页
     * 
     * @param int $cateId 栏目ID
     * @param array $data 模板变量
     * @return string 渲染结果
     */
    public function renderList(int $cateId, array $data = []): string
    {
        $template = $this->resolveListTemplate($cateId);

        // 检查模板文件是否存在，不存在则fallback到系统默认
        if (!$this->templateExists($template)) {
            $template = 'list';
        }

        View::assign($data);
        return View::fetch($template);
    }

    /**
     * 渲染详情页
     * 
     * @param int $cateId 栏目ID
     * @param array $data 模板变量
     * @return string 渲染结果
     */
    public function renderDetail(int $cateId, array $data = []): string
    {
        $template = $this->resolveDetailTemplate($cateId);

        // 检查模板文件是否存在，不存在则fallback到系统默认
        if (!$this->templateExists($template)) {
            $template = 'detail';
        }

        View::assign($data);
        return View::fetch($template);
    }

    /**
     * 检查模板文件是否存在
     * 模板路径：template/themes/{skin}/pc/ 或 template/themes/{skin}/mobile/
     */
    public function templateExists(string $template): bool
    {
        // 获取当前皮肤
        $skin = cookie('theme_skin') ?: 'default';
        $isMobile = request()->isMobile();

        // 尝试多个可能的路径
        $paths = [
            root_path("template/themes/{$skin}/" . ($isMobile ? 'mobile' : 'pc') . "/{$template}.html"),
            root_path("template/themes/{$skin}/pc/{$template}.html"),
            root_path("template/themes/default/pc/{$template}.html"),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取栏目的模型code
     */
    public function getCateModelCode(int $cateId): string
    {
        return $this->modelService->getCateModelCode($cateId);
    }
}
