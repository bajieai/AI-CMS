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
 * 后台问题反馈模型 - V2.9.63
 *
 * 状态机：0 待处理 / 1 处理中 / 2 已解决 / 3 已答复 / 4 已忽略
 * 上报机：0 待上报 / 1 已上报 / 2 失败
 * 通知机：0 未通知 / 1 已通知 / 2 失败
 */
class Feedback extends Model
{
    protected $name = 'feedback';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 关闭时间字段读取时自动转 datetime 字符串
     * 保持 int 时间戳，模板 / 控制器统一用 date() 格式化
     */
    protected $dateFormat = false;

    /** 类型映射 */
    public const TYPE_MAP = [
        'bug'         => '程序缺陷',
        'enhancement' => '功能建议',
        'question'    => '使用疑问',
    ];

    /** 处理状态映射 */
    public const STATUS_MAP = [
        0 => '待处理',
        1 => '处理中',
        2 => '已解决',
        3 => '已答复',
        4 => '已忽略',
    ];

    /** 上报状态映射 */
    public const GATEWAY_MAP = [
        0 => '待上报',
        1 => '已上报',
        2 => '上报失败',
    ];

    /** 通知状态映射 */
    public const NOTIFY_MAP = [
        0 => '未通知',
        1 => '已通知',
        2 => '通知失败',
    ];

    protected $type = [
        'admin_id'       => 'integer',
        'status'         => 'integer',
        'gateway_status' => 'integer',
        'notify_status'  => 'integer',
        'reply_time'     => 'integer',
        'create_time'    => 'integer',
        'update_time'    => 'integer',
    ];

    public function getTypeTextAttr($value, $data): string
    {
        return self::TYPE_MAP[$data['type'] ?? ''] ?? (string) ($data['type'] ?? '');
    }

    public function getStatusTextAttr($value, $data): string
    {
        return self::STATUS_MAP[(int) ($data['status'] ?? 0)] ?? '未知';
    }

    public function getGatewayStatusTextAttr($value, $data): string
    {
        return self::GATEWAY_MAP[(int) ($data['gateway_status'] ?? 0)] ?? '未知';
    }

    public function getNotifyStatusTextAttr($value, $data): string
    {
        return self::NOTIFY_MAP[(int) ($data['notify_status'] ?? 0)] ?? '未知';
    }

    /**
     * 附件 JSON 转数组
     */
    public function getAttachmentsListAttr($value, $data): array
    {
        $raw = $data['attachments'] ?? '';
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 环境信息 JSON 转数组
     */
    public function getEnvListAttr($value, $data): array
    {
        $raw = $data['env'] ?? '';
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
