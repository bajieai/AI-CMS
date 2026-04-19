<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 分类模型
 */
class Category extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_categories';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     */
    protected $type = [
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'content_count' => 'integer',
    ];

    /**
     * 父级分类
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 子级分类
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 文章关联
     */
    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    /**
     * 获取完整路径
     */
    public function getPath(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' / ', $path);
    }

    /**
     * 获取祖先ID列表
     */
    public function getAncestorIds(): array
    {
        $ids = [];
        $parent = $this->parent;
        
        while ($parent) {
            $ids[] = $parent->id;
            $parent = $parent->parent;
        }
        
        return $ids;
    }

    /**
     * 获取后代ID列表
     */
    public function getDescendantIds(): array
    {
        $ids = [$this->id];
        $children = $this->children;
        
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        
        return $ids;
    }

    /**
     * 获取分类树
     */
    public static function getTree(): array
    {
        $categories = self::where('status', '=', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
        
        return self::buildTree($categories);
    }

    /**
     * 构建树形结构
     */
    public static function buildTree(array $categories, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] === $parentId) {
                $children = self::buildTree($categories, $category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    /**
     * 检查是否会创建循环
     * 如果将当前分类移动到指定父分类，是否会形成循环
     */
    public function wouldCreateCycle(int $newParentId): bool
    {
        if ($newParentId === 0) {
            return false;
        }
        
        if ($newParentId === $this->id) {
            return true;
        }
        
        // 检查新父分类是否是当前分类的后代
        $descendantIds = $this->getDescendantIds();
        return in_array($newParentId, $descendantIds);
    }

    /**
     * 设置父分类
     */
    public function setParent(int $parentId): bool
    {
        // 检查循环
        if ($this->wouldCreateCycle($parentId)) {
            return false;
        }
        
        $this->parent_id = $parentId;
        $this->level = $this->calculateLevel($parentId);
        return $this->save();
    }

    /**
     * 计算层级
     */
    protected function calculateLevel(int $parentId): int
    {
        if ($parentId === 0) {
            return 1;
        }
        
        $parent = self::find($parentId);
        return $parent ? $parent->level + 1 : 1;
    }

    /**
     * 获取所有祖先链
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors[] = $parent;
            $parent = $parent->parent;
        }
        
        return array_reverse($ancestors);
    }

    /**
     * 获取所有后代
     */
    public function getDescendants(): array
    {
        $descendants = [];
        $children = $this->children;
        
        foreach ($children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }
        
        return $descendants;
    }

    /**
     * 更新文章计数
     */
    public function updateArticleCount(): void
    {
        $this->article_count = $this->articles()->count();
        $this->save();
    }

    /**
     * 获取导航分类
     */
    public static function getNavCategories(): array
    {
        return self::where('status', '=', 1)
            ->where('status', '=', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取扁平列表(带层级缩进)
     */
    public static function getFlatList(): array
    {
        $tree = self::getTree();
        return self::flattenTree($tree);
    }

    /**
     * 扁平化树
     */
    protected static function flattenTree(array $tree, int $level = 0): array
    {
        $result = [];
        
        foreach ($tree as $item) {
            $item['level'] = $level;
            $result[] = $item;
            
            if (!empty($item['children'])) {
                $result = array_merge($result, self::flattenTree($item['children'], $level + 1));
            }
        }
        
        return $result;
    }

    /**
     * 获取分类信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'level' => $this->level,
            'description' => $this->description,
            'icon' => '',
            'sort' => $this->sort_order,
            'status' => $this->status,
            'is_nav' => 0,
            'content_count' => $this->content_count,
            'path' => $this->getPath(),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        return $this->status === self::STATUS_ENABLED ? '正常' : '禁用';
    }
}
