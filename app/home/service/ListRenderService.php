<?php

declare(strict_types=1);

namespace app\home\service;

use app\common\controller\FrontBaseController;
use app\common\model\Cate;

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
     * 渲染列表页（静态方法，供Controller直接调用）
     * 
     * @param FrontBaseController $controller 前台控制器实例
     * @param string $defaultTemplate 默认模板（如 '/list'）
     * @param Cate|null $cate 当前栏目对象
     * @return string 解析后的模板路径
     */
    public static function resolveTemplate(string $defaultTemplate, ?Cate $cate): string
    {
        $template = $defaultTemplate;

        // Fallback链：栏目自定义模板 → 模型默认模板 → 系统默认
        if ($cate) {
            // 1. 栏目自定义列表模板
            if (!empty($cate->list_template)) {
                $template = '/' . $cate->list_template;
            } elseif (!empty($cate->model_id)) {
                // 2. 模型默认列表模板
                $modelCode = self::getModelCodeByModelId((int) $cate->model_id);
                if ($modelCode) {
                    $modelTemplate = '/list_' . $modelCode;
                    $instance = new self();
                    if ($instance->renderService->templateExists('list_' . $modelCode)) {
                        $template = $modelTemplate;
                    }
                }
            }
        }

        return $template;
    }

    /**
     * 渲染列表页（静态方法，供Controller直接调用）
     * 
     * @param FrontBaseController $controller 前台控制器实例
     * @param string $defaultTemplate 默认模板（如 '/list'）
     * @param Cate|null $cate 当前栏目对象
     * @param array $extraData 额外模板变量
     * @return string 解析后的模板路径
     * @deprecated 请使用 resolveTemplate() 配合 Controller 自行 view()
     */
    public static function render(
        FrontBaseController $controller,
        string $defaultTemplate,
        ?Cate $cate,
        array $extraData = []
    ): string {
        return self::resolveTemplate($defaultTemplate, $cate);
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
     * 获取列表页模板路径（实例方法）
     */
    public function getTemplate(int $cateId): string
    {
        return $this->renderService->resolveListTemplate($cateId);
    }

    /**
     * 获取栏目对应的模型code（实例方法）
     */
    public function getModelCode(int $cateId): string
    {
        return $this->renderService->getCateModelCode($cateId);
    }
}
