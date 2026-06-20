<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板驳回理由模板模型 — V2.9.26 P-3
 */
class TemplateRejectReason extends Model
{
    protected $name = 'template_reject_reason';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 获取启用的理由列表
     */
    public static function getActiveReasons(string $category = ''): array
    {
        $query = self::where('status', 1);
        if ($category !== '') {
            $query->where('category', $category);
        }
        return $query->order('sort', 'asc')->select()->toArray();
    }
}
