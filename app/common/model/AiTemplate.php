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

namespace app\common\model;

use think\Model;

/**
 * AI内容模板模型 - V2.6新增
 */
class AiTemplate extends Model
{
    protected $name = 'ai_template';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'id'              => 'integer',
        'generate_mode'   => 'string',
        'cate_id'         => 'integer',
        'model_id'        => 'integer',
        'style'           => 'string',
        'fields_config'   => 'json',
        'image_config'    => 'json',
        'field_mapping'   => 'json',     // V2.9新增：字段映射规则
        'quality_config'  => 'json',     // V2.9新增：质量检测配置
        'default_batch'   => 'integer',
        'status'          => 'integer',
        'sort'            => 'integer',
        'source'          => 'string',   // V2.9.9新增：模板来源
    ];

    /**
     * 获取生成模式文本
     */
    public function getModeTextAttr($value, $data): string
    {
        return match ($data['generate_mode'] ?? 'nlp') {
            'example' => '参考示例',
            default   => 'NLP自然语言',
        };
    }

    /**
     * 获取风格文本
     */
    public function getStyleTextAttr($value, $data): string
    {
        $map = [
            'default'   => '默认',
            'formal'    => '正式',
            'casual'    => '通俗',
            'marketing' => '营销',
            'technical' => '技术',
        ];
        return $map[$data['style'] ?? 'default'] ?? '默认';
    }

    /**
     * 解析 fields_config 为数组
     */
    public function getFieldsArrayAttr($value, $data): array
    {
        $config = $data['fields_config'] ?? null;
        if (empty($config)) return [];
        
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($config) ? $config : [];
    }

    /**
     * 解析 image_config 为数组（带默认值）
     */
    public function getImageConfigArrayAttr($value, $data): array
    {
        $config = $data['image_config'] ?? null;
        if (empty($config)) {
            return [
                'thumb'  => '0',   // 0不上传
                'images' => '0',   // 不配图
                'count'  => 0,
                'source' => '0',
            ];
        }
        
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            return is_array($decoded) ? array_merge([
                'thumb' => '0', 'images' => '0', 'count' => 0, 'source' => '0',
            ], $decoded) : [
                'thumb' => '0', 'images' => '0', 'count' => 0, 'source' => '0',
            ];
        }
        
        return is_array($config) ? array_merge([
            'thumb' => '0', 'images' => '0', 'count' => 0, 'source' => '0',
        ], $config) : [
            'thumb' => '0', 'images' => '0', 'count' => 0, 'source' => '0',
        ];
    }

    /**
     * 解析 field_mapping 为数组（V2.9新增）
     * 结构: {mappings:[{ai_output_field, cms_field, transform_rule}], variables:[{name, default}], image_config_override:{}}
     */
    public function getFieldMappingArrayAttr($value, $data): array
    {
        $mapping = $data['field_mapping'] ?? null;
        if (empty($mapping)) {
            return [
                'mappings'            => [],
                'variables'            => [],
                'image_config_override' => [],
            ];
        }

        if (is_string($mapping)) {
            $decoded = json_decode($mapping, true);
            if (!is_array($decoded)) {
                return ['mappings' => [], 'variables' => [], 'image_config_override' => []];
            }
            return array_merge([
                'mappings'             => [],
                'variables'             => [],
                'image_config_override' => [],
            ], $decoded);
        }

        return array_merge([
            'mappings'             => [],
            'variables'             => [],
            'image_config_override' => [],
        ], is_array($mapping) ? $mapping : []);
    }

    /**
     * 解析 quality_config 为数组（V2.9新增）
     * 结构: {min_score, max_retry, action_on_low_quality, check_items}
     */
    public function getQualityConfigArrayAttr($value, $data): array
    {
        $config = $data['quality_config'] ?? null;
        if (empty($config)) {
            return [
                'min_score'               => 70,
                'max_retry'               => 2,
                'action_on_low_quality'    => 'notify',  // notify/auto_retry/reject
                'check_items'              => ['spelling', 'readability', 'seo'],
            ];
        }

        if (is_string($config)) {
            $decoded = json_decode($config, true);
            if (!is_array($decoded)) {
                return ['min_score' => 70, 'max_retry' => 2, 'action_on_low_quality' => 'notify', 'check_items' => ['spelling', 'readability', 'seo']];
            }
            return array_merge([
                'min_score'              => 70,
                'max_retry'              => 2,
                'action_on_low_quality'  => 'notify',
                'check_items'            => ['spelling', 'readability', 'seo'],
            ], $decoded);
        }

        return array_merge([
            'min_score'              => 70,
            'max_retry'              => 2,
            'action_on_low_quality'  => 'notify',
            'check_items'            => ['spelling', 'readability', 'seo'],
        ], is_array($config) ? $config : []);
    }
}
