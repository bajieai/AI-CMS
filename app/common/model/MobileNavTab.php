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
use think\facade\Cache;

/**
 * 移动端底部导航Tab模型 - V2.9.24 H-2
 */
class MobileNavTab extends Model
{
    protected $name = 'mobile_nav_tab';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'is_enabled' => 'integer',
        'require_login' => 'integer',
        'show_badge' => 'integer',
    ];

    // Tab 类型常量
    const TYPE_HOME = 'home';
    const TYPE_CATEGORY = 'category';
    const TYPE_MEMBER = 'member';
    const TYPE_MESSAGE = 'message';
    const TYPE_CUSTOM = 'custom';

    public static array $typeMap = [
        self::TYPE_HOME => '首页',
        self::TYPE_CATEGORY => '分类',
        self::TYPE_MEMBER => '用户中心',
        self::TYPE_MESSAGE => '消息',
        self::TYPE_CUSTOM => '自定义链接',
    ];

    /**
     * 获取启用的导航Tab列表（缓存）
     */
    public static function getEnabledTabs(): array
    {
        return Cache::remember('enabled_tabs', function () {
            return self::where('is_enabled', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
        }, 3600);
    }

    /**
     * 清除缓存
     */
    public static function clearCache(): void
    {
        Cache::clear();
    }

    /**
     * 查询作用域 — 只查询启用的
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', 1);
    }

    /**
     * 查询作用域 — 按排序
     */
    public function scopeSorted($query)
    {
        return $query->order('sort', 'asc')->order('id', 'asc');
    }
}
