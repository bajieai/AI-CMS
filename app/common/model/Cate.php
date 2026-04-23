<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 分类模型
 * 注意：模型名Cate，表名cate（不使用ContentCategory全称）
 */
class Cate extends Model
{
    protected $name = 'cate';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'type' => 'integer',
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 获取URL（模型获取器）
     */
    public function getUrlAttr($value, $data): string
    {
        $typeMap = [1 => 'product', 2 => 'case', 3 => 'news', 4 => 'download', 5 => 'job', 6 => 'page'];
        $typeSlug = $typeMap[$data['type']] ?? 'info';
        return "/{$typeSlug}?cate_id={$data['id']}";
    }

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(Cate::class, 'parent_id');
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(Cate::class, 'parent_id');
    }
}
