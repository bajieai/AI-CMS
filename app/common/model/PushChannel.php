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
 * 推送通道配置模型 - V2.9.18
 */
class PushChannel extends Model
{
    protected $name = 'push_channel';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id'           => 'integer',
        'trigger_mode' => 'integer',
        'status'       => 'integer',
    ];

    /** Webhook 通道 */
    const TYPE_WEBHOOK = 'webhook';
    /** 微信推送通道 */
    const TYPE_WECHAT = 'wechat_push';
    /** 站内广播通道 */
    const TYPE_BROADCAST = 'broadcast';

    /** 触发：手动 */
    const TRIGGER_MANUAL = 0;
    /** 触发：自动 */
    const TRIGGER_AUTO = 1;

    /** 状态：禁用 */
    const STATUS_DISABLED = 0;
    /** 状态：启用 */
    const STATUS_ENABLED = 1;

    /**
     * 获取启用的自动推送通道
     */
    public static function getAutoChannels(): array
    {
        return self::where('status', self::STATUS_ENABLED)
            ->where('trigger_mode', self::TRIGGER_AUTO)
            ->select()
            ->toArray();
    }

    /**
     * 获取启用的通道（全部或按类型）
     */
    public static function getEnabledChannels(?string $type = null): array
    {
        $query = self::where('status', self::STATUS_ENABLED);
        if ($type) {
            $query->where('type', $type);
        }
        return $query->select()->toArray();
    }

    /**
     * 解析配置 JSON
     */
    public function getConfigAttr($value): array
    {
        if (empty($value)) return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 编码配置 JSON
     */
    public function setConfigAttr($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    public function getTypeTextAttr($value, $data): string
    {
        $map = [
            self::TYPE_WEBHOOK   => 'Webhook',
            self::TYPE_WECHAT    => '微信推送',
            self::TYPE_BROADCAST => '站内广播',
        ];
        return $map[$data['type'] ?? ''] ?? '未知';
    }

    public function getStatusTextAttr($value, $data): string
    {
        return ($data['status'] ?? 0) ? '启用' : '禁用';
    }

    public function getTriggerModeTextAttr($value, $data): string
    {
        return ($data['trigger_mode'] ?? 0) ? '自动' : '手动';
    }
}
