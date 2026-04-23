<?php
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
}
