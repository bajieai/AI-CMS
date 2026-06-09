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
use think\model\concern\SoftDelete;

/**
 * 内容模型
 */
class Content extends Model
{
    // 表名（不含前缀）
    protected $name = 'content';

    // 自动时间戳（使用int时间戳）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 软删除（使用status=-1代替物理删除）
    // 不使用ThinkPHP内置SoftDelete，手动处理

    // 类型转换
    protected $type = [
        'type' => 'integer',
        'status' => 'integer',
        'cate_id' => 'integer',
        'user_id' => 'integer',
        'sort' => 'integer',
        'is_top' => 'integer',
        'views' => 'integer',
        'publish_time' => 'integer',
        'hotness' => 'integer',
        'is_recommend' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'min_level_id' => 'integer',
        'is_paid' => 'integer',
        'is_chapter' => 'integer',
        'parent_id' => 'integer',
        'chapter_sort' => 'integer',
        'is_free_chapter' => 'integer',
        'chapter_price' => 'float',
        'chapter_count' => 'integer',
        'chapter_title' => 'string',
        'quality_score' => 'integer',
        'seo_score' => 'integer',
        'lang' => 'string',
        'translation_of' => 'integer',
        'model_id' => 'integer',
    ];

    /**
     * 获取URL（模型获取器）
     * 模板中使用 {$field.url}
     */
    public function getUrlAttr($value, $data): string
    {
        $typeMap = [
            1 => 'product',
            2 => 'case',
            3 => 'news',
            4 => 'download',
            5 => 'job',
            6 => 'page',
        ];

        $typeSlug = $typeMap[$data['type']] ?? 'info';
        return "/{$typeSlug}/{$data['id']}";
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '草稿', 1 => '待审', 2 => '已发布', -1 => '已删除'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data): string
    {
        $map = [1 => '产品', 2 => '案例', 3 => '新闻', 4 => '下载', 5 => '招聘', 6 => '单页'];
        return $map[$data['type']] ?? '未知';
    }

    /**
     * 关联分类
     */
    public function cate()
    {
        return $this->belongsTo(Cate::class, 'cate_id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 关联标签（多对多）
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, ContentTag::class, 'tag_id', 'content_id');
    }

    /**
     * 关联扩展数据
     */
    public function ext()
    {
        return $this->hasOne(ContentExt::class, 'content_id');
    }

    /**
     * 关联评论
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'content_id');
    }

    /**
     * 关联点赞
     */
    public function likes()
    {
        return $this->hasMany(MemberLike::class, 'content_id');
    }

    /**
     * 关联收藏
     */
    public function favorites()
    {
        return $this->hasMany(MemberFavorite::class, 'content_id');
    }

    /**
     * V2.9.2 M19a: 关联翻译内容（一条原始内容对应多条翻译）
     */
    public function translations()
    {
        return $this->hasMany(Content::class, 'translation_of', 'id');
    }

    /**
     * V2.9.2 M19a: 关联原始内容
     */
    public function original()
    {
        return $this->belongsTo(Content::class, 'translation_of');
    }

    /**
     * V2.9.2 M19a: 查询作用域 — 只查询原始内容（非翻译）
     */
    public function scopeOriginal($query)
    {
        return $query->where('translation_of', 0);
    }

    /**
     * V2.9.2 M19a: 查询作用域 — 只查询翻译内容
     */
    public function scopeTranslated($query)
    {
        return $query->where('translation_of', '>', 0);
    }

    /**
     * V2.9.2 M19a: 查询作用域 — 按语言筛选
     */
    public function scopeByLang($query, string $langCode)
    {
        return $query->where('lang', $langCode);
    }

    /**
     * V2.9.20 A-1: 关联内容模型定义
     */
    public function contentModel()
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    /**
     * V2.9.20 A-1: 获取扩展字段值
     * 从 content_ext.data JSON 中读取指定字段值
     */
    public function getFieldValue(string $fieldName, $default = null)
    {
        if (!$this->ext || empty($this->ext->data)) {
            return $default;
        }
        return $this->ext->data[$fieldName] ?? $default;
    }

    /**
     * V2.9.20 A-1: 查询作用域 — 按内容模型筛选
     */
    public function scopeByModelId($query, int $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    /**
     * V2.9.20 A-1: 查询作用域 — 按内容类型自动匹配默认模型
     */
    public function scopeWithDefaultModel($query, int $type)
    {
        $model = ContentModel::getDefaultByType($type);
        if ($model) {
            return $query->where('model_id', $model->id);
        }
        return $query;
    }
}
