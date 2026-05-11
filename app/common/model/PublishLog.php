<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 发布记录模型 - V2.5新增，V2.9.4增强
 * V2.9.4新增字段: platform/action/error_msg/retry_count/update_time
 */
class PublishLog extends Model
{
    protected $name = 'publish_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'content_id' => 'integer',
        'platform_id' => 'integer',
        'status' => 'integer',
        'retry_count' => 'integer',
    ];

    /**
     * 状态常量
     */
    const STATUS_PENDING = 0;  // 待重试
    const STATUS_SUCCESS = 1;  // 成功
    const STATUS_FAILED = 2;   // 失败

    /**
     * 关联发布平台
     */
    public function platform()
    {
        return $this->belongsTo(PublishPlatform::class, 'platform_id');
    }

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * V2.9.4: 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待重试',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
        ];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * V2.9.4: 获取平台名称（兼容新旧数据）
     */
    public function getPlatformNameAttr($value, $data): string
    {
        // 优先使用新字段
        if (!empty($data['platform'])) {
            $map = [
                'weixin' => '微信公众号',
                'toutiao' => '头条号',
                'zhihu' => '知乎',
            ];
            return $map[$data['platform']] ?? $data['platform'];
        }
        // 兼容旧数据：通过platform_id关联查询
        if (!empty($data['platform_id'])) {
            $platform = PublishPlatform::find($data['platform_id']);
            $map = ['weixin' => '微信公众号', 'toutiao' => '头条号', 'zhihu' => '知乎'];
            return $platform ? ($map[$platform->name] ?? $platform->name) : '未知平台';
        }
        return '未知平台';
    }

    /**
     * V2.9.4: 获取状态徽章CSS类
     */
    public function getStatusBadgeAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_SUCCESS => 'bg-success',
            self::STATUS_FAILED => 'bg-danger',
        ];
        return $map[$data['status']] ?? 'bg-secondary';
    }
}
