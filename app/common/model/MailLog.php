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

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
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
        $log = self::create([
            'subscriber_id' => $data['subscriber_id'] ?? 0,
            'content_id'    => $data['content_id'] ?? 0,
            'email'         => $data['email'] ?? '',
            'subject'       => $data['subject'] ?? '',
            'status'        => $data['status'] ?? self::STATUS_PENDING,
            'error_msg'     => $data['error_msg'] ?? '',
            'sent_at'       => $data['sent_at'] ?? null,
            'create_time'   => $data['create_time'] ?? time(),
        ]);

        // V2.9.19 S-1c: 静默检测 — 发送失败时增加订阅者失败计数
        if (($data['status'] ?? self::STATUS_PENDING) == self::STATUS_FAILED && !empty($data['subscriber_id'])) {
            $sub = \app\common\model\Subscriber::find((int) $data['subscriber_id']);
            if ($sub && $sub->status == \app\common\model\Subscriber::STATUS_CONFIRMED) {
                $sub->fail_count += 1;
                if ($sub->fail_count >= 3) {
                    $sub->status     = \app\common\model\Subscriber::STATUS_INVALID;
                    $sub->invalid_at = date('Y-m-d H:i:s');
                }
                $sub->save();
            }
        }

        return $log;
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
