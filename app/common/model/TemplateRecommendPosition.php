<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 推荐位定义模型 — V2.9.28 M-6
 */
class TemplateRecommendPosition extends Model
{
    protected $name = 'template_recommend_position';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    const TYPE_MANUAL = 1;  // 人工推荐
    const TYPE_RULE = 2;    // 规则推荐
    const TYPE_AI = 3;      // AI推荐（预留）

    protected $type = [
        'type' => 'integer',
        'max_count' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'config' => 'json',
    ];

    /**
     * 关联推荐项
     */
    public function items()
    {
        return $this->hasMany(TemplateRecommendItem::class, 'position_id');
    }

    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data): string
    {
        $map = [
            self::TYPE_MANUAL => '人工推荐',
            self::TYPE_RULE => '规则推荐',
            self::TYPE_AI => 'AI推荐',
        ];
        return $map[$data['type']] ?? '未知';
    }
}
