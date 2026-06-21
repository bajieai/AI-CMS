<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\home;

use app\common\model\Content;
use app\common\model\ContentModel;
use app\common\model\ContentModelTemplateMap;
use think\facade\Cache;

/**
 * V2.9.27 S-4: 模型模板解析器
 * 根据内容模型和内容自身的template字段，解析前台展示模板
 */
class ModelTemplateResolver
{
    /**
     * 默认模板映射（内容类型 -> 模板文件名）
     */
    private static array $defaultTemplates = [
        1 => 'content/product_show',    // 产品
        2 => 'content/case_show',       // 案例
        3 => 'content/article_show',    // 新闻/文章
        4 => 'content/download_show',   // 下载
        5 => 'content/job_show',        // 招聘
        6 => 'content/page_show',       // 单页
    ];

    /**
     * 解析内容的前台展示模板
     * 优先级：内容自身template > 模型template_file > 默认类型模板
     * @param Content $content 内容对象
     * @return string 模板路径（不含.html后缀）
     */
    public static function resolve(Content $content): string
    {
        $type = (int)($content->type ?? 1);

        // 1. 内容自身指定的模板（S-5c 一键切换）
        if (!empty($content->template)) {
            $template = 'content/' . $content->template;
            if (self::templateExists($template)) {
                return $template;
            }
        }

        // 2. 内容模型定义的模板
        $modelId = (int)($content->model_id ?? 0);
        if ($modelId > 0) {
            $model = self::getModel($modelId);
            if ($model && !empty($model->template_file)) {
                if (self::templateExists($model->template_file)) {
                    return $model->template_file;
                }
            }
        }

        // 3. 默认类型模板
        return self::$defaultTemplates[$type] ?? 'content/article_show';
    }

    /**
     * 获取可用的模板列表（供一键切换S-5c使用）
     * @param int $modelId 模型ID
     * @param int $type 内容类型
     * @return array [['id'=>int, 'name'=>string, 'template'=>string, 'is_current'=>bool], ...]
     */
    public static function getAvailableTemplates(int $modelId, int $type): array
    {
        $templates = [];

        // 默认模板
        $defaultTemplate = self::$defaultTemplates[$type] ?? 'content/article_show';
        $templates[] = [
            'id' => 0,
            'name' => '默认模板',
            'template' => '',
            'is_current' => false,
        ];

        // 模型推荐模板（S-5 模型与模板推荐联动）
        if ($modelId > 0) {
            $recommended = ContentModelTemplateMap::getRecommendedTemplates($modelId, [], 10);
            foreach ($recommended as $tmpl) {
                $templates[] = [
                    'id' => $tmpl->id,
                    'name' => $tmpl->name,
                    'template' => $tmpl->code ?? '',
                    'is_current' => false,
                ];
            }
        }

        // 预置专属模板
        $presetTemplates = self::getPresetTemplates($type);
        foreach ($presetTemplates as $code => $name) {
            $templates[] = [
                'id' => -1,
                'name' => $name,
                'template' => $code,
                'is_current' => false,
            ];
        }

        return $templates;
    }

    /**
     * 获取内容模型的预置专属模板列表
     */
    public static function getPresetTemplates(int $type): array
    {
        return match ($type) {
            1 => ['product_show' => '产品详情模板', 'product_gallery' => '产品图册模板'],
            2 => ['case_show' => '案例详情模板', 'case_timeline' => '案例时间线模板'],
            3 => ['article_show' => '文章详情模板', 'article_magazine' => '杂志风格模板', 'image_show' => '图集展示模板', 'video_show' => '视频播放模板'],
            4 => ['download_show' => '下载详情模板'],
            5 => ['job_show' => '招聘详情模板'],
            6 => ['page_show' => '单页模板'],
            default => ['article_show' => '默认文章模板'],
        };
    }

    /**
     * 检查模板文件是否存在
     */
    public static function templateExists(string $templatePath): bool
    {
        // 去掉可能的视图前缀
        $templatePath = preg_replace('/^content\//', '', $templatePath);

        $paths = [
            root_path() . 'template/home/default/' . $templatePath . '.html',
            root_path() . 'template/home/default/content/' . $templatePath . '.html',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取内容模型（带缓存）
     */
    private static function getModel(int $modelId): ?ContentModel
    {
        return Cache::tag('content_model')->remember(
            'content_model_' . $modelId,
            function () use ($modelId) {
                return ContentModel::find($modelId);
            },
            3600
        );
    }
}
