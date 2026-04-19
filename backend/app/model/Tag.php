<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 标签模型
 */
class Tag extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_tags';

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
        'status' => 'integer',
    ];

    /**
     * 获取内容数量（动态计算）
     */
    public function getContentCountAttr($value): int
    {
        if ($value !== null) {
            return (int)$value;
        }
        // 动态计算
        return $this->articles()->count();
    }

    /**
     * 信息关联
     */
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'i8j_aicms_article_tags', 'tag_id', 'article_id');
    }

    /**
     * 获取标签列表(带搜索)
     */
    public static function search(string $keyword, int $limit = 20): array
    {
        $query = self::where('status', '=', 1);
        
        if (!empty($keyword)) {
            $query->where('name', 'like', "%{$keyword}%");
        }
        
        // 改为按 id 排序，避免数据库缺少 content_count 列
        return $query->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取热门标签
     */
    public static function getHotTags(int $limit = 10): array
    {
        // 改为按 id 排序，避免数据库缺少 content_count 列
        return self::where('status', '=', 1)
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 更新信息计数
     */
    public function updateContentCount(): void
    {
        $this->content_count = $this->articles()->count();
        $this->save();
    }

    /**
     * 批量更新计数
     */
    public static function batchUpdateCounts(): void
    {
        $tags = self::where('status', '=', 1)->select();
        
        foreach ($tags as $tag) {
            $count = Db::name('article_tags')
                ->where('tag_id', '=', $tag->id)
                ->count();
            $tag->content_count = $count;
            $tag->save();
        }
    }

    /**
     * 查找或创建标签
     */
    public static function findOrCreate(string $name): Tag
    {
        $name = trim($name);
        $tag = self::where('name', '=', $name)->find();
        
        if (!$tag) {
            $tag = self::create([
                'name' => $name,
                'slug' => self::generateSlug($name),
                'status' => 1,
                'content_count' => 0,
            ]);
        }
        
        return $tag;
    }

    /**
     * 生成slug
     */
    protected static function generateSlug(string $name): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]/u', '-', $name);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = strtolower($slug);
        
        // 确保唯一
        $count = 0;
        $originalSlug = $slug;
        while (self::where('slug', '=', $slug)->count() > 0) {
            $count++;
            $slug = $originalSlug . '-' . $count;
        }
        
        return $slug;
    }

    /**
     * 根据Slug查找
     */
    public static function findBySlug(string $slug): ?Tag
    {
        return self::where('slug', '=', $slug)->find();
    }

    /**
     * 获取标签信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'content_count' => $this->content_count,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}
