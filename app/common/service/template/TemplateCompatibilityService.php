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

namespace app\common\service\template;

/**
 * 模板兼容检查服务 (V2.9.29 C-5)
 * 
 * 检查模板与内容模型的兼容性
 */
class TemplateCompatibilityService
{
    /**
     * 预置5种模型
     */
    public const PRESET_MODELS = ['article', 'image', 'download', 'product', 'video'];

    /**
     * 模型标签映射
     */
    public const MODEL_LABELS = [
        'article'  => '文章',
        'image'    => '图片',
        'download' => '下载',
        'product'  => '产品',
        'video'    => '视频',
    ];

    /**
     * 解析模板的 support_models 字段
     */
    public function parseSupportModels(string $json): array
    {
        if (empty($json)) {
            return self::PRESET_MODELS;
        }
        $models = json_decode($json, true);
        return is_array($models) ? $models : self::PRESET_MODELS;
    }

    /**
     * 检查模板是否支持指定模型
     */
    public function isModelSupported(string $supportModelsJson, string $modelCode): bool
    {
        $supported = $this->parseSupportModels($supportModelsJson);
        return in_array($modelCode, $supported, true);
    }

    /**
     * 获取模板支持的模型标签列表
     */
    public function getSupportedModelTags(string $supportModelsJson): array
    {
        $supported = $this->parseSupportModels($supportModelsJson);
        $tags = [];
        foreach ($supported as $code) {
            if (isset(self::MODEL_LABELS[$code])) {
                $tags[] = [
                    'code'  => $code,
                    'label' => self::MODEL_LABELS[$code],
                ];
            }
        }
        return $tags;
    }

    /**
     * 获取不兼容的模型列表
     */
    public function getIncompatibleModels(string $supportModelsJson, array $siteModels): array
    {
        $supported = $this->parseSupportModels($supportModelsJson);
        $incompatible = [];
        foreach ($siteModels as $code) {
            if (!in_array($code, $supported, true)) {
                $incompatible[] = [
                    'code'  => $code,
                    'label' => self::MODEL_LABELS[$code] ?? $code,
                ];
            }
        }
        return $incompatible;
    }

    /**
     * 格式化 support_models 为JSON
     */
    public function formatSupportModels(array $models): string
    {
        return json_encode(array_values(array_intersect($models, self::PRESET_MODELS)));
    }
}
