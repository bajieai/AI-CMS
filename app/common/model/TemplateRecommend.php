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
 * 模板推荐位模型 - V2.9.24 G-2
 * 用于模板商店首页推荐位配置
 */
class TemplateRecommend extends Model
{
    protected $name = 'template_recommend';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'position' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'recommend_type' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
    ];

    // 推荐位位置常量
    const POSITION_HOME_TOP = 1;     // 首页顶部
    const POSITION_HOME_HOT = 2;     // 首页热门
    const POSITION_HOME_NEW = 3;     // 首页新品
    const POSITION_HOME_FEATURED = 4; // 首页精选

    public static array $positionMap = [
        self::POSITION_HOME_TOP => '首页顶部推荐',
        self::POSITION_HOME_HOT => '热门推荐',
        self::POSITION_HOME_NEW => '新品推荐',
        self::POSITION_HOME_FEATURED => '精选推荐',
    ];

    // 推荐类型常量
    const TYPE_MANUAL = 1;    // 手动指定
    const TYPE_AUTO_HOT = 2;  // 自动热门（按安装量）
    const TYPE_AUTO_NEW = 3;  // 自动最新（按发布时间）

    public static array $recommendTypeMap = [
        self::TYPE_MANUAL => '手动指定',
        self::TYPE_AUTO_HOT => '自动热门',
        self::TYPE_AUTO_NEW => '自动最新',
    ];

    /**
     * 获取位置文本
     */
    public function getPositionTextAttr($value, $data): string
    {
        return self::$positionMap[$data['position']] ?? '未知';
    }

    /**
     * 获取推荐类型文本
     */
    public function getRecommendTypeTextAttr($value, $data): string
    {
        return self::$recommendTypeMap[$data['recommend_type']] ?? '未知';
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
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id', 'id')
            ->field('id, name, thumb, price, install_count, rating_avg');
    }

    /**
     * 查询作用域 — 按位置
     */
    public function scopeByPosition($query, int $position)
    {
        return $query->where('position', $position)
            ->where('status', 1)
            ->order('sort', 'asc');
    }

    /**
     * 查询作用域 — 有效期内
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
            });
    }
}
