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

/**
 * 模板商店Banner模型 - V2.9.24 G-1
 * 用于模板商店首页轮播图管理
 */
class TemplateBanner extends Model
{
    protected $name = 'template_banner';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
        'target_type' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
    ];

    // 跳转类型常量
    const TARGET_URL = 1;      // 外部URL
    const TARGET_TEMPLATE = 2; // 模板详情
    const TARGET_CATEGORY = 3; // 分类页面

    public static array $targetTypeMap = [
        self::TARGET_URL => '外部链接',
        self::TARGET_TEMPLATE => '模板详情',
        self::TARGET_CATEGORY => '分类页面',
    ];

    /**
     * 获取跳转类型文本
     */
    public function getTargetTypeTextAttr($value, $data): string
    {
        return self::$targetTypeMap[$data['target_type']] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '禁用', 1 => '启用'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 获取是否有效期内
     */
    public function getIsActiveAttr($value, $data): bool
    {
        $now = time();
        if ($data['start_time'] > 0 && $now < $data['start_time']) {
            return false;
        }
        if ($data['end_time'] > 0 && $now > $data['end_time']) {
            return false;
        }
        return $data['status'] === 1;
    }

    /**
     * 关联目标模板
     */
    public function targetTemplate()
    {
        return $this->belongsTo(TemplateStore::class, 'target_id', 'id')
            ->field('id, name, banner_url');
    }

    /**
     * 关联目标分类
     */
    public function targetCategory()
    {
        return $this->belongsTo(TemplateStoreCategory::class, 'target_id', 'id')
            ->field('id, name');
    }

    /**
     * 查询作用域 — 仅启用
     */
    public function scopeActive($query)
    {
        $now = time();
        return $query->where('status', 1)
            ->where(function ($q) use ($now) {
                $q->where('start_time', 0)->whereOr('start_time', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->where('end_time', 0)->whereOr('end_time', '>=', $now);
            })
            ->order('sort', 'asc');
    }
}
