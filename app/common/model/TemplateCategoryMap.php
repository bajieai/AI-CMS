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
        'is_primary'  => 'integer',
        'confidence'  => 'integer',
        'created_by'  => 'integer',
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
     * 批量设置模板分类（V2.9.21 D-3 增强版）
     * 支持 is_primary/confidence/created_by 字段
     *
     * @param int   $templateId 模板ID
     * @param array $categories 分类ID数组，或 [category_id => ['is_primary'=>1,'confidence'=>95]] 格式
     * @param int   $createdBy  创建来源（1=人工，2=AI自动）
     */
    public static function setTemplateCategories(int $templateId, array $categories, int $createdBy = 1): void
    {
        // 删除旧关联
        self::where('template_id', $templateId)->delete();

        // 规范化数据格式
        $data = [];
        $hasPrimary = false;
        foreach ($categories as $key => $value) {
            if (is_int($key)) {
                // 简单格式: [1, 2, 3]
                $cid = (int) $value;
                $isPrimary = !$hasPrimary ? 1 : 0;
                $confidence = 100;
            } else {
                // 详细格式: [1 => ['is_primary'=>1,'confidence'=>95], 2 => [...]]
                $cid = (int) $key;
                $isPrimary = !empty($value['is_primary']) ? 1 : 0;
                $confidence = (int) ($value['confidence'] ?? 100);
            }

            // 确保只有一个主分类
            if ($isPrimary && $hasPrimary) {
                $isPrimary = 0;
            }
            if ($isPrimary) {
                $hasPrimary = true;
            }

            $data[] = [
                'template_id' => $templateId,
                'category_id' => $cid,
                'is_primary'  => $isPrimary,
                'confidence'  => max(0, min(100, $confidence)),
                'created_by'  => $createdBy,
            ];
        }

        if (!empty($data)) {
            self::insertAll($data);
        }
    }

    /**
     * 保存单条映射（自动推断 is_primary）
     * 如果该模板尚无主分类，则设为 primary
     *
     * @param int $templateId 模板ID
     * @param int $categoryId 分类ID
     * @param array $extra 额外字段 ['confidence'=>95, 'created_by'=>2]
     * @return self
     */
    public static function saveMap(int $templateId, int $categoryId, array $extra = []): self
    {
        $hasPrimary = self::where('template_id', $templateId)
            ->where('is_primary', 1)
            ->exists();

        $map = new self();
        $map->template_id = $templateId;
        $map->category_id = $categoryId;
        $map->is_primary  = $hasPrimary ? 0 : 1;
        $map->confidence  = max(0, min(100, (int) ($extra['confidence'] ?? 100)));
        $map->created_by  = (int) ($extra['created_by'] ?? 1);
        $map->save();

        return $map;
    }

    /**
     * 批量保存映射（自动推断主分类）
     *
     * @param int   $templateId 模板ID
     * @param array $categoryIds 分类ID数组
     * @param array $extra 额外字段 ['confidence'=>95, 'created_by'=>2]
     * @return array 创建的映射记录数组
     */
    public static function batchSaveMaps(int $templateId, array $categoryIds, array $extra = []): array
    {
        $hasPrimary = self::where('template_id', $templateId)
            ->where('is_primary', 1)
            ->exists();

        $confidence = max(0, min(100, (int) ($extra['confidence'] ?? 100)));
        $createdBy  = (int) ($extra['created_by'] ?? 1);

        $maps = [];
        foreach (array_unique($categoryIds) as $cid) {
            $map = new self();
            $map->template_id = $templateId;
            $map->category_id = (int) $cid;
            $map->is_primary  = $hasPrimary ? 0 : 1;
            $map->confidence  = $confidence;
            $map->created_by  = $createdBy;
            $map->save();

            $maps[] = $map;

            if (!$hasPrimary) {
                $hasPrimary = true;
            }
        }

        return $maps;
    }

    /**
     * 获取模板的主分类ID
     */
    public static function getPrimaryCategoryId(int $templateId): ?int
    {
        $row = self::where('template_id', $templateId)
            ->where('is_primary', 1)
            ->find();
        return $row ? (int) $row->category_id : null;
    }

    /**
     * 获取模板的分类列表（含 is_primary 标记）
     */
    public static function getCategoriesWithMeta(int $templateId): array
    {
        return self::where('template_id', $templateId)
            ->order('is_primary', 'desc')
            ->order('confidence', 'desc')
            ->select()
            ->toArray();
    }
}
