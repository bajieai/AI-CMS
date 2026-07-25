<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板包组合模型 — V2.9.28 M-4
 */
class TemplatePack extends Model
{
    protected $name = 'template_pack';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'price' => 'float',
        'original_price' => 'float',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 关联包内模板
     */
    public function items()
    {
        return $this->hasMany(TemplatePackItem::class, 'pack_id');
    }

    /**
     * 关联模板（通过中间表）
     */
    public function templates()
    {
        return $this->belongsToMany(TemplateStore::class, TemplatePackItem::class, 'template_id', 'pack_id');
    }
}
