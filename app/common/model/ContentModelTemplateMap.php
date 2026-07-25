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
 * V2.9.27 S-5: 内容模型-模板映射模型
 */
class ContentModelTemplateMap extends Model
{
    protected $name = 'content_model_template_map';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'model_id' => 'integer',
        'template_id' => 'integer',
        'priority' => 'integer',
        'is_default' => 'integer',
        'status' => 'integer',
    ];

    // JSON字段
    protected $json = ['tag_match'];

    /**
     * 关联内容模型
     */
    public function contentModel()
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id');
    }

    /**
     * 查询作用域 — 启用状态
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 查询作用域 — 按模型筛选
     */
    public function scopeByModel($query, int $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    /**
     * 获取模型的推荐模板列表
     * @param int $modelId 模型ID
     * @param array $tags 内容标签（用于匹配）
     * @param int $limit 数量限制
     * @return array
     */
    public static function getRecommendedTemplates(int $modelId, array $tags = [], int $limit = 5): array
    {
        $query = self::where('model_id', $modelId)
            ->where('status', 1)
            ->order('is_default', 'desc')
            ->order('priority', 'desc')
            ->order('sort', 'asc')
            ->limit($limit);

        $maps = $query->select();
        $result = [];
        foreach ($maps as $map) {
            $template = TemplateStore::find($map->template_id);
            if ($template && $template->status >= 0) {
                $result[] = $template;
            }
        }

        return $result;
    }
}
