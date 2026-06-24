<?php

declare(strict_types=1);

namespace app\home\service;

use app\common\controller\FrontBaseController;
use app\common\model\Cate;
use app\common\model\Content;

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
     * 渲染详情页（静态方法，供Controller直接调用）
     * 
     * @param FrontBaseController $controller 前台控制器实例
     * @param string $defaultTemplate 默认模板（如 '/detail'）
     * @param Cate|null $cate 当前栏目对象
     * @param Content|null $content 内容对象
     * @param array $extraData 额外模板变量
     * @return \think\Response
     */
    public static function render(
        FrontBaseController $controller,
        string $defaultTemplate,
        ?Cate $cate,
        ?Content $content = null,
        array $extraData = []
    ): \think\Response {
        $template = $defaultTemplate;

        // Fallback链：栏目自定义模板 → 模型默认模板 → 系统默认
        if ($cate) {
            // 1. 栏目自定义详情模板
            if (!empty($cate->detail_template)) {
                $template = '/' . $cate->detail_template;
            } elseif (!empty($cate->model_id)) {
                // 2. 模型默认详情模板
                $modelCode = self::getModelCodeByModelId((int) $cate->model_id);
                if ($modelCode) {
                    $modelTemplate = '/detail_' . $modelCode;
                    $instance = new self();
                    if ($instance->renderService->templateExists('detail_' . $modelCode)) {
                        $template = $modelTemplate;
                    }
                }
            }
        }

        // 也检查内容自身的model_id
        if ($template === $defaultTemplate && $content && !empty($content->model_id)) {
            $modelCode = self::getModelCodeByModelId((int) $content->model_id);
            if ($modelCode) {
                $modelTemplate = '/detail_' . $modelCode;
                $instance = new self();
                if ($instance->renderService->templateExists('detail_' . $modelCode)) {
                    $template = $modelTemplate;
                }
            }
        }

        if (!empty($extraData)) {
            $controller->assign($extraData);
        }

        return $controller->view($template);
    }

    /**
     * 根据model_id获取模型code
     */
    private static function getModelCodeByModelId(int $modelId): string
    {
        $model = \app\common\model\ContentModel::find($modelId);
        return $model ? ($model->code ?? '') : '';
    }

    /**
     * 获取详情页模板路径（实例方法）
     */
    public function getTemplate(int $cateId): string
    {
        return $this->renderService->resolveDetailTemplate($cateId);
    }

    /**
     * 获取栏目对应的模型code（实例方法）
     */
    public function getModelCode(int $cateId): string
    {
        return $this->renderService->getCateModelCode($cateId);
    }
}
