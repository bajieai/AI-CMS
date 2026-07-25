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

namespace app\admin\model;

use think\Model;

/**
 * 智能报表配置模型 - V2.9.39 DATA-DEEP-2
 * 对应表: i8j_data_report
 */
class DataReport extends Model
{
    protected $name = 'data_report';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'id'                 => 'integer',
        'ai_analysis'        => 'integer',
        'data_config'        => 'json',
        'chart_config'       => 'json',
        'schedule_config'    => 'json',
        'recipients'         => 'json',
        'prediction_config'  => 'json',
        'prediction_results' => 'json',
    ];

    // 报表类型常量
    public const TYPE_DAILY     = 'daily';
    public const TYPE_WEEKLY    = 'weekly';
    public const TYPE_MONTHLY   = 'monthly';
    public const TYPE_QUARTERLY = 'quarterly';
    public const TYPE_YEARLY    = 'yearly';
    public const TYPE_CUSTOM    = 'custom';
    public const TYPE_COMPARE   = 'compare';
    public const TYPE_TREND     = 'trend';
    public const TYPE_ANOMALY   = 'anomaly';
    public const TYPE_TARGET    = 'target';

    // 状态常量
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_PAUSED   = 'paused';
    public const STATUS_ARCHIVED = 'archived';

    protected static array $typeMap = [
        self::TYPE_DAILY     => '日报',
        self::TYPE_WEEKLY    => '周报',
        self::TYPE_MONTHLY   => '月报',
        self::TYPE_QUARTERLY => '季报',
        self::TYPE_YEARLY    => '年报',
        self::TYPE_CUSTOM    => '自定义',
        self::TYPE_COMPARE   => '对比分析',
        self::TYPE_TREND     => '趋势分析',
        self::TYPE_ANOMALY   => '异常检测',
        self::TYPE_TARGET    => '目标达成',
    ];

    protected static array $statusMap = [
        self::STATUS_ACTIVE   => '启用',
        self::STATUS_PAUSED   => '暂停',
        self::STATUS_ARCHIVED => '归档',
    ];

    /**
     * 报表类型文本
     */
    public function getReportTypeTextAttr($value, array $data): string
    {
        return self::$typeMap[$data['report_type'] ?? ''] ?? '未知';
    }

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value, array $data): string
    {
        return self::$statusMap[$data['status'] ?? ''] ?? '未知';
    }

    /**
     * 获取启用的报表列表
     */
    public static function getActiveReports(): array
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取需要定时发送的报表
     */
    public static function getScheduledReports(): array
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->whereNotNull('schedule_config')
            ->select()
            ->toArray();
    }

    /**
     * 获取所有报表类型
     */
    public static function getTypeMap(): array
    {
        return self::$typeMap;
    }

    /**
     * 获取所有状态
     */
    public static function getStatusMap(): array
    {
        return self::$statusMap;
    }
}
