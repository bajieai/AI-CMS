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

namespace app\common\service\home;

use app\common\model\Content;
use app\common\model\ContentModel;
use app\common\service\content\TypeContentService;

/**
 * V2.9.27 S-4: 内容模型展示渲染服务
 * 为前台模板提供模型感知的数据渲染
 */
class ContentRenderService
{
    /**
     * 渲染内容详情数据
     * 合并基础内容和模型扩展数据
     * @param Content $content 内容对象
     * @return array 渲染数据
     */
    public static function renderDetail(Content $content): array
    {
        $data = $content->toArray();
        $modelId = (int)($content->model_id ?? 0);

        // 获取模型扩展字段数据
        if ($modelId > 0) {
            $modelFields = TypeContentService::getModelFieldsWithData($content->id, $modelId);
            $data['model_fields'] = $modelFields;
            $data['has_model_fields'] = !empty($modelFields);

            // 模型信息
            $model = ContentModel::find($modelId);
            if ($model) {
                $data['model_info'] = $model->toArray();
            }
        } else {
            $data['model_fields'] = [];
            $data['has_model_fields'] = false;
        }

        // SEO增强 (S-6)
        $seoData = ModelSeoService::generate($content, $model ?? null);
        $data['seo_title'] = $seoData['title'];
        $data['seo_keywords'] = $seoData['keywords'];
        $data['seo_description'] = $seoData['description'];

        // 解析模板 (S-4)
        $data['resolved_template'] = ModelTemplateResolver::resolve($content);

        // 关联内容 (S-3e)
        $data['related_contents'] = TypeContentService::getRelations($content->id, 'related', 5);
        $data['recommended_contents'] = TypeContentService::getRelations($content->id, 'recommended', 3);

        return $data;
    }

    /**
     * 渲染列表项数据（轻量级，不加载扩展字段）
     * @param Content $content
     * @return array
     */
    public static function renderListItem(Content $content): array
    {
        $data = $content->toArray();
        $modelId = (int)($content->model_id ?? 0);

        // 列表页只加载模型名称
        if ($modelId > 0) {
            $model = ContentModel::find($modelId);
            $data['model_name'] = $model ? $model->name : '';
        }

        // 列表页标题SEO
        if ($modelId > 0 && isset($model)) {
            $data['seo_title'] = ModelSeoService::generateTitleOnly($content, $model);
        }

        return $data;
    }

    /**
     * 获取模型专属分类列表 (S-3d)
     * @param int $modelId 模型ID（0=通用）
     * @param int $type 内容类型
     * @return array
     */
    public static function getModelCategories(int $modelId, int $type): array
    {
        $cacheKey = 'model_cates_' . $modelId . '_' . $type;
        return \think\facade\Cache::tag('content_model')->remember(
            $cacheKey,
            function () use ($modelId, $type) {
                return \app\common\model\Cate::where('status', 1)
                    ->where('type', $type)
                    ->where(function ($query) use ($modelId) {
                        if ($modelId > 0) {
                            $query->where('model_id', $modelId)->whereOr('model_id', 0);
                        }
                    })
                    ->order('sort', 'asc')
                    ->select()
                    ->toArray();
            },
            3600
        );
    }
}
