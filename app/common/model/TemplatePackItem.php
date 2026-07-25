<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板包关联模型 — V2.9.28 M-4
 */
class TemplatePackItem extends Model
{
    protected $name = 'template_pack_item';
    protected $autoWriteTimestamp = false;

    /**
     * 关联模板包
     */
    public function pack()
    {
        return $this->belongsTo(TemplatePack::class, 'pack_id');
    }

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id');
    }
}
