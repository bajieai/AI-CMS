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
 * 邮件发送日志模型 - V2.9.18 D-3
 */
class MailLog extends Model
{
    protected $name = 'mail_log';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id'            => 'integer',
        'subscriber_id' => 'integer',
        'content_id'    => 'integer',
        'status'        => 'integer',
    ];

    /** 待发送 */
    const STATUS_PENDING = 0;
    /** 已发送 */
    const STATUS_SENT = 1;
    /** 失败 */
    const STATUS_FAILED = 2;

    /**
     * 记录邮件日志
     */
    public static function record(array $data): self
    {
        return self::create([
            'subscriber_id' => $data['subscriber_id'] ?? 0,
            'content_id'    => $data['content_id'] ?? 0,
            'email'         => $data['email'] ?? '',
            'subject'       => $data['subject'] ?? '',
            'status'        => $data['status'] ?? self::STATUS_PENDING,
            'error_msg'     => $data['error_msg'] ?? '',
            'sent_at'       => $data['sent_at'] ?? null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING => '待发送',
            self::STATUS_SENT    => '已发送',
            self::STATUS_FAILED  => '失败',
        ];
        return $map[$data['status'] ?? 0] ?? '未知';
    }
}
