<?php
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
        'id'             => 'integer',
        'generate_mode'  => 'string',
        'cate_id'        => 'integer',
        'model_id'       => 'integer',
        'style'          => 'string',
        'fields_config'  => 'json',
        'image_config'   => 'json',
        'default_batch'  => 'integer',
        'status'         => 'integer',
        'sort'           => 'integer',
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
}
