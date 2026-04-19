<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 信息模型
 */
class Article extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_articles';

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
     * 时间戳格式
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 类型转换
     */
    protected $type = [
        'user_id' => 'integer',
        'category_id' => 'integer',
        'status' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'is_top' => 'integer',
        'is_recommend' => 'integer',
        'published_at' => 'timestamp',
    ];

    /**
     * 作者关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 分类关联
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * 标签关联
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'i8j_aicms_article_tags', 'article_id', 'tag_id');
    }

    /**
     * 状态变更日志
     */
    public function statusLogs()
    {
        return $this->hasMany(ArticleStatusLog::class, 'article_id');
    }

    /**
     * 状态常量
     */
    const STATUS_DRAFT = 0;       // 草稿
    const STATUS_PENDING = 1;     // 待审核
    const STATUS_PUBLISHED = 2;  // 已发布
    const STATUS_ARCHIVED = 3;   // 已归档
    const STATUS_REJECTED = 4;   // 已拒绝

    /**
     * 状态文本映射
     */
    const STATUS_TEXT = [
        self::STATUS_DRAFT => '草稿',
        self::STATUS_PENDING => '待审核',
        self::STATUS_PUBLISHED => '已发布',
        self::STATUS_ARCHIVED => '已归档',
        self::STATUS_REJECTED => '已拒绝',
    ];

    /**
     * 允许的状态转换
     */
    const ALLOWED_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_PENDING, self::STATUS_PUBLISHED],
        self::STATUS_PENDING => [self::STATUS_PUBLISHED, self::STATUS_REJECTED, self::STATUS_DRAFT],
        self::STATUS_PUBLISHED => [self::STATUS_ARCHIVED, self::STATUS_DRAFT],
        self::STATUS_ARCHIVED => [self::STATUS_PUBLISHED],
        self::STATUS_REJECTED => [self::STATUS_DRAFT, self::STATUS_PENDING],
    ];

    /**
     * 获取允许的状态转换
     */
    public function allowedTransitions(): array
    {
        return self::ALLOWED_TRANSITIONS[$this->status] ?? [];
    }

    /**
     * 检查是否可以转换到指定状态
     */
    public function canTransition(int $targetStatus): bool
    {
        return in_array($targetStatus, $this->allowedTransitions());
    }

    /**
     * 转换状态
     */
    public function transitionTo(int $targetStatus, string $reason = '', int $operatorId = 0): bool
    {
        if (!$this->canTransition($targetStatus)) {
            return false;
        }
        
        $oldStatus = $this->status;
        $this->status = $targetStatus;
        
        // 发布时设置发布时间
        if ($targetStatus === self::STATUS_PUBLISHED && empty($this->published_at)) {
            $this->published_at = time();
        }
        
        $this->save();
        
        // 记录状态变更日志
        ArticleStatusLog::create([
            'article_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $targetStatus,
            'reason' => $reason,
            'operator_id' => $operatorId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return true;
    }

    /**
     * 获取状态文本
     */
    public function getStatusText(): string
    {
        return self::STATUS_TEXT[$this->status] ?? '未知';
    }

    /**
     * 是否可以编辑
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * 是否可以删除
     */
    public function canDelete(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
            self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * 设置标签
     */
    public function setTags(array $tagIds): void
    {
        $this->tags()->detach();
        if (!empty($tagIds)) {
            $this->tags()->saveAll($tagIds);
        }
    }

    /**
     * 获取标签ID列表
     */
    public function getTagIds(): array
    {
        return $this->tags()->column('id');
    }

    /**
     * 获取文章完整信息
     */
    public function getFullInfo(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'summary' => $this->summary,
            'cover_image' => $this->cover_image,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'nickname' => $this->user->nickname,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'tags' => $this->tags->toArray(),
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'is_top' => $this->is_top,
            'is_recommend' => $this->is_recommend,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * 根据Slug查找
     */
    public static function findBySlug(string $slug): ?Article
    {
        return self::where('slug', '=', $slug)->find();
    }

    /**
     * 获取已发布的文章
     */
    public static function published(): Article
    {
        return self::where('status', '=', self::STATUS_PUBLISHED);
    }

    /**
     * 增加浏览数
     */
    public function incrementViewCount(): void
    {
        $this->inc('view_count');
        $this->save();
    }

    /**
     * 增加点赞数
     */
    public function incrementLikeCount(): void
    {
        $this->inc('like_count');
        $this->save();
    }

    /**
     * 软删除
     */
    public function softDelete(): bool
    {
        $this->delete_time = time();
        return $this->save();
    }

    /**
     * 恢复删除
     */
    public function restore(): bool
    {
        $this->delete_time = null;
        return $this->save();
    }
}
