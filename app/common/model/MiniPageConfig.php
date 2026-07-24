<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 移动端页面配置模型
 * V2.9.37 MINI-FULL-3
 */
class MiniPageConfig extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    // JSON字段自动转换
    protected $json = ['page_layout', 'page_style'];
    protected $jsonAssoc = true;

    protected $type = [
        'version'      => 'integer',
        'is_published' => 'integer',
    ];

    // 获取已发布配置
    public function scopePublished($query, string $pageType = '', string $platform = 'all')
    {
        $query->where('is_published', 1);
        if ($pageType) {
            $query->where('page_type', $pageType);
        }
        $query->where('platform', 'in', ['all', $platform]);
        return $query->order('version', 'desc');
    }

    // 获取最新版本
    public function getLatestVersion(string $pageType, string $platform = 'all'): ?static
    {
        return static::where('page_type', $pageType)
            ->where('platform', 'in', ['all', $platform])
            ->order('version', 'desc')
            ->find();
    }
}
