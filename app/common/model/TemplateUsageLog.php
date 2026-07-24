<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.25 N-2: 模板使用日志模型
 */
class TemplateUsageLog extends Model
{
    protected $name = 'template_usage_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    const EVENT_VIEW = 'view';
    const EVENT_PREVIEW = 'preview';
    const EVENT_INSTALL = 'install';
    const EVENT_ACTIVATE = 'activate';
    const EVENT_CUSTOM = 'custom';

    /**
     * 记录使用日志
     */
    public static function record(int $templateId, int $memberId, string $eventType, array $extra = []): self
    {
        $now = time();
        return self::create([
            'template_id' => $templateId,
            'member_id' => $memberId,
            'event_type' => $eventType,
            'device' => self::detectDevice(),
            'ip' => request()->ip() ?: '',
            'user_agent' => substr(request()->header('user-agent', ''), 0, 500),
            'referer' => substr(request()->header('referer', ''), 0, 500),
            'extra' => json_encode($extra),
            'create_date' => date('Y-m-d', $now),
            'create_time' => $now,
        ]);
    }

    /**
     * 检测设备类型
     */
    protected static function detectDevice(): string
    {
        $ua = strtolower(request()->header('user-agent', ''));
        if (preg_match('/tablet|ipad/', $ua)) return 'tablet';
        if (preg_match('/mobile|android|iphone/', $ua)) return 'mobile';
        return 'pc';
    }
}
