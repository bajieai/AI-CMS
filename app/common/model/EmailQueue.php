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
 * 邮件队列持久化模型 - V2.7 P0-2
 */
class EmailQueue extends Model
{
    protected $name = 'email_queue';
    protected $autoWriteTimestamp = false;

    // status 常量
    public const STATUS_PENDING = 0;   // 待发送
    public const STATUS_SENT    = 1;   // 已发送
    public const STATUS_FAILED  = 2;   // 失败（超过最大重试次数）

    protected $type = [
        'status'      => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'create_time' => 'integer',
        'sent_time'   => 'integer',
    ];

    /**
     * 入队：写入DB（status=0）
     */
    public static function enqueue(string $templateCode, string $toEmail, array $vars = []): int
    {
        $model = self::create([
            'template_code' => $templateCode,
            'to_email'      => $toEmail,
            'vars'          => json_encode($vars, JSON_UNESCAPED_UNICODE),
            'status'        => self::STATUS_PENDING,
            'retry_count'   => 0,
            'max_retries'   => 3,
            'error_msg'     => '',
            'create_time'   => time(),
            'sent_time'     => 0,
        ]);
        return $model->id;
    }

    /**
     * 标记发送成功
     */
    public static function markSent(int $id): void
    {
        self::where('id', $id)->update([
            'status'   => self::STATUS_SENT,
            'sent_time' => time(),
        ]);
    }

    /**
     * 标记失败（增加重试计数，超过max_retries则标记为失败）
     */
    public static function markFailed(int $id, string $errorMsg = ''): bool
    {
        $record = self::where('id', $id)->find();
        if (!$record) {
            return false;
        }
        $newCount = $record->retry_count + 1;
        if ($newCount >= $record->max_retries) {
            // 超过最大重试次数，标记为失败
            $record->status    = self::STATUS_FAILED;
            $record->error_msg = mb_substr($errorMsg, 0, 500);
            $record->save();
            return false; // 不再重试
        }
        // 更新重试计数
        $record->retry_count = $newCount;
        $record->error_msg  = mb_substr($errorMsg, 0, 500);
        $record->save();
        return true; // 可以重试
    }

    /**
     * 扫描待发送记录（启动恢复用）
     * @return array [[id, template_code, to_email, vars], ...]
     */
    public static function scanPending(int $limit = 100): array
    {
        $list = self::where('status', self::STATUS_PENDING)
            ->order('create_time', 'asc')
            ->limit($limit)
            ->select();
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'db_id'        => $item->id,
                'template_code' => $item->template_code,
                'to_email'      => $item->to_email,
                'vars'          => json_decode($item->vars, true) ?: [],
            ];
        }
        return $result;
    }
}
