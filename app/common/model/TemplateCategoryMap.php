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
 * 模板-分类映射模型 (V2.9.20 B-1)
 */
class TemplateCategoryMap extends Model
{
    protected $name = 'template_category_map';

    // 不使用自动时间戳
    protected $autoWriteTimestamp = false;

    protected $type = [
        'template_id' => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id');
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(TemplateCategory::class, 'category_id');
    }

    /**
     * 根据模板ID获取分类列表
     */
    public static function getCategoriesByTemplateId(int $templateId): array
    {
        return self::where('template_id', $templateId)
            ->column('category_id');
    }

    /**
     * 根据分类ID获取模板ID列表
     */
    public static function getTemplatesByCategoryId(int $categoryId): array
    {
        return self::where('category_id', $categoryId)
            ->column('template_id');
    }

    /**
     * 批量设置模板分类
     */
    public static function setTemplateCategories(int $templateId, array $categoryIds): void
    {
        // 删除旧关联
        self::where('template_id', $templateId)->delete();

        // 插入新关联
        $data = [];
        foreach (array_unique($categoryIds) as $cid) {
            $data[] = [
                'template_id' => $templateId,
                'category_id' => $cid,
            ];
        }
        if (!empty($data)) {
            self::insertAll($data);
        }
    }
}
