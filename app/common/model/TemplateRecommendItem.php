<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 推荐位模板关联模型 — V2.9.28 M-6
 */
class TemplateRecommendItem extends Model
{
    protected $name = 'template_recommend_item';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 关联推荐位
     */
    public function position()
    {
        return $this->belongsTo(TemplateRecommendPosition::class, 'position_id');
    }

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id');
    }
}
