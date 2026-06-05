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
 * 推送日志模型 - V2.9.18
 */
class PushLog extends Model
{
    protected $name = 'push_log';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id'            => 'integer',
        'channel_id'    => 'integer',
        'content_id'    => 'integer',
        'response_code' => 'integer',
        'duration_ms'   => 'integer',
        'status'        => 'integer',
    ];

    /** 状态：待发送 */
    const STATUS_PENDING = 0;
    /** 状态：成功 */
    const STATUS_SUCCESS = 1;
    /** 状态：失败 */
    const STATUS_FAILED = 2;

    /**
     * 记录推送日志
     */
    public static function record(array $data): self
    {
        return self::create([
            'channel_id'    => $data['channel_id'] ?? 0,
            'content_id'    => $data['content_id'] ?? 0,
            'request_url'   => $data['request_url'] ?? '',
            'request_body'  => $data['request_body'] ?? '',
            'response_code' => $data['response_code'] ?? 0,
            'response_body' => $data['response_body'] ?? '',
            'duration_ms'   => $data['duration_ms'] ?? 0,
            'status'        => $data['status'] ?? self::STATUS_PENDING,
            'error_msg'     => $data['error_msg'] ?? '',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待发送',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED  => '失败',
        ];
        return $map[$data['status'] ?? 0] ?? '未知';
    }

    /**
     * 关联推送通道
     */
    public function channel()
    {
        return $this->belongsTo(PushChannel::class, 'channel_id', 'id');
    }
}
