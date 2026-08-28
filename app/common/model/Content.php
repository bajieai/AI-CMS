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

use app\common\support\ContentTypeMap;
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
        'play_count' => 'integer',
        'download_count' => 'integer',
        // V2.9.50: publish_time 强制 int（非 ThinkPHP 时间字段，类型转换安全有效）。
        // 注意：create_time / update_time 是 ThinkPHP 时间字段（受 $createTime/$updateTime 注册），
        // ThinkPHP 会自动将其格式化为 'Y-m-d H:i:s' 字符串输出，此处声明 integer 会被覆盖且冲突，
        // 故不在此声明。模板中已改为兼容 int/datetime 两种形态的写法（见 detail_info.html 等）。
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
     * V2.9.50: create_time 获取器 — 强制返回 int 时间戳
     *
     * 背景（重要）：本项目 $autoWriteTimestamp='int' 且 $createTime='create_time'，
     * ThinkPHP 会把 create_time / update_time 注册为「时间字段」，读取时自动格式化成
     * 'Y-m-d H:i:s' 字符串（如 '2026-04-23 19:48:15'），而非返回数据库里的 int 时间戳。
     *
     * 但全项目代码约定是「create_time 为 int 时间戳」：
     *   - SeoService::buildJsonLd():  date('c', (int)$data['create_time'])
     *   - SchemaService::generateContent(): 同样按 int 处理
     *   - 模板：date('Y-m-d', $vo.create_time)
     * 传入格式化字符串会导致 (int) 强转得到 2026 → date() 输出 1970-01-01。
     *
     * 此处用获取器覆盖 ThinkPHP 的自动格式化，统一返回 int 时间戳（原始值），
     * 一处修复全局生效。原始值通过 getData() 获取，已是 int。
     */
    public function getCreateTimeAttr($value): int
    {
        return (int) ($this->getData('create_time') ?? 0);
    }

    /**
     * V2.9.50: update_time 获取器 — 强制返回 int 时间戳（同上）
     */
    public function getUpdateTimeAttr($value): int
    {
        return (int) ($this->getData('update_time') ?? 0);
    }

    /**
     * 获取URL（模型获取器）
     * 模板中使用 {$field.url} 或 {$vo.url}
     * URL体系：
     *   内容所属分类有seo_url：/{seo_url}/{id} 如 /news/1
     *   无seo_url：/{type_slug}/{id} 如 /info/1
     */
    public function getUrlAttr($value, $data): string
    {
        $typeSlug = ContentTypeMap::slugByType()[(int) ($data['type'] ?? ContentTypeMap::INFO)] ?? 'info';

        // 尝试从关联分类获取 seo_url（安全访问，避免获取器中触发查询异常）
        try {
            $cate = $this->getAttr('cate');
            if ($cate && !empty($cate->seo_url)) {
                return '/' . $cate->seo_url . '/' . $data['id'];
            }
        } catch (\Throwable $e) {
            // 关联未加载或查询失败，回退到 type_slug 格式
        }

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
        $map = ContentTypeMap::nameByType();
        return $map[(int) ($data['type'] ?? 0)] ?? '未知';
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
