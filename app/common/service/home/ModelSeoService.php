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
use think\facade\Cache;

/**
 * V2.9.27 S-6: 模型SEO服务
 * 基于内容模型定义的SEO模板，动态生成SEO标题/关键词/描述
 */
class ModelSeoService
{
    /**
     * 占位符模式
     */
    private const PLACEHOLDER_PATTERN = '/\{(\$[a-z_]+)\}/i';

    /**
     * 根据内容模型生成SEO数据
     * @param Content $content 内容对象
     * @param ContentModel|null $model 内容模型定义
     * @return array ['title' => string, 'keywords' => string, 'description' => string]
     */
    public static function generate(Content $content, ?ContentModel $model = null): array
    {
        // 默认SEO数据（使用内容自身字段）
        $default = [
            'title' => $content->title ?? '',
            'keywords' => $content->seo_keywords ?? '',
            'description' => $content->seo_description ?? '',
        ];

        // 如果没有模型或模型未定义SEO模板，返回默认
        if (!$model || (empty($model->seo_title) && empty($model->seo_keywords) && empty($model->seo_description))) {
            return $default;
        }

        // 准备变量
        $vars = self::prepareVars($content);

        // 应用模板
        return [
            'title' => self::applyTemplate($model->seo_title, $vars) ?: $default['title'],
            'keywords' => self::applyTemplate($model->seo_keywords, $vars) ?: $default['keywords'],
            'description' => self::applyTemplate($model->seo_description, $vars) ?: $default['description'],
        ];
    }

    /**
     * 批量生成SEO数据（列表页用，只解析标题模板）
     */
    public static function generateTitleOnly(Content $content, ?ContentModel $model = null): string
    {
        if (!$model || empty($model->seo_title)) {
            return $content->title ?? '';
        }

        $vars = self::prepareVars($content);
        return self::applyTemplate($model->seo_title, $vars) ?: ($content->title ?? '');
    }

    /**
     * 准备模板变量
     */
    private static function prepareVars(Content $content): array
    {
        $vars = [
            '$title'       => $content->title ?? '',
            '$site_name'   => self::getSiteName(),
            '$cate_name'   => $content->cate ? $content->cate->name : '',
            '$author'      => $content->author ?? '',
            '$source'      => '',
            '$create_time' => !empty($content->create_time) ? date('Y-m-d', (int)$content->create_time) : '',
            '$description' => mb_substr(strip_tags($content->content ?? ''), 0, 100),
        ];

        // 合并扩展字段
        if ($content->ext && $content->ext->data) {
            foreach ($content->ext->data as $key => $value) {
                $vars['$' . $key] = (string)$value;
            }
        }

        return $vars;
    }

    /**
     * 应用模板替换
     */
    private static function applyTemplate(string $template, array $vars): string
    {
        if (empty($template)) {
            return '';
        }

        // 替换所有占位符 {$field_name}
        $result = preg_replace_callback(self::PLACEHOLDER_PATTERN, function ($matches) use ($vars) {
            $key = $matches[1]; // 如 $title
            return $vars[$key] ?? '';
        }, $template);

        // 清理多余空格
        $result = preg_replace('/\s+/', ' ', $result);
        return trim($result);
    }

    /**
     * 获取站点名称（带缓存）
     */
    private static function getSiteName(): string
    {
        return Cache::remember('site_name', function () {
            $config = \app\common\model\Config::getValue('site_name', '八界AI-CMS');
            return $config;
        }, 3600);
    }

    /**
     * 预览SEO模板效果
     * @param string $template SEO模板字符串
     * @param array $sampleData 示例数据
     * @return string
     */
    public static function previewTemplate(string $template, array $sampleData = []): string
    {
        if (empty($template)) {
            return '';
        }

        $defaultSample = [
            '$title' => '示例标题',
            '$site_name' => '示例站点',
            '$cate_name' => '示例分类',
            '$author' => '示例作者',
            '$source' => '示例来源',
            '$create_time' => date('Y-m-d'),
            '$description' => '这是示例描述内容',
        ];

        $vars = array_merge($defaultSample, $sampleData);
        return self::applyTemplate($template, $vars);
    }
}
