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
 * 模板分类模型 (V2.9.20 B-1)
 * [修正：小扣-1] 新增 type 列(varchar20) 用于区分分类维度
 */
class TemplateCategory extends Model
{
    protected $name = 'template_category';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    // 分类维度常量
    const TYPE_CONTENT_MODEL = 'content_model';
    const TYPE_INDUSTRY = 'industry';
    const TYPE_STYLE = 'style';

    public static array $typeMap = [
        self::TYPE_CONTENT_MODEL => '内容模型',
        self::TYPE_INDUSTRY => '行业',
        self::TYPE_STYLE => '风格',
    ];

    /**
     * 获取维度文本
     */
    public function getTypeTextAttr($value, $data): string
    {
        return self::$typeMap[$data['type']] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [self::STATUS_DISABLED => '禁用', self::STATUS_ENABLED => '启用'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('status', self::STATUS_ENABLED)
            ->order('sort', 'asc');
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 按维度获取分类树
     */
    public static function getTreeByType(string $type): array
    {
        $list = self::where('type', $type)
            ->where('status', self::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return self::buildTree($list, 0);
    }

    /**
     * 获取全部分类（按维度分组）
     */
    public static function getGrouped(): array
    {
        $result = [];
        foreach (self::$typeMap as $type => $label) {
            $result[$type] = [
                'label' => $label,
                'items' => self::getTreeByType($type),
            ];
        }
        return $result;
    }

    /**
     * 构建树形结构
     */
    private static function buildTree(array $data, int $parentId): array
    {
        $tree = [];
        foreach ($data as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = self::buildTree($data, $item['id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
