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
 * 邮件订阅者模型 - V2.9.18 D-3
 * 表: i8j_subscriber（支持 Double Opt-in）
 */
class Subscriber extends Model
{
    protected $name = 'subscriber';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'subscribed_at';
    protected $updateTime = false;

    protected $type = [
        'status' => 'integer',
    ];

    /** 状态：待确认 */
    const STATUS_PENDING = 0;
    /** 状态：已确认 */
    const STATUS_CONFIRMED = 1;
    /** 状态：已退订 */
    const STATUS_UNSUBSCRIBED = 2;

    /**
     * 获取所有已确认的订阅者
     */
    public static function getConfirmed(): array
    {
        return self::where('status', self::STATUS_CONFIRMED)->select()->toArray();
    }

    /**
     * 根据 token 查找
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('confirm_token', $token)->find();
    }

    /**
     * 生成唯一确认 token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function getStatusTextAttr($value, $data): string
    {
        $map = [
            self::STATUS_PENDING       => '待确认',
            self::STATUS_CONFIRMED     => '已确认',
            self::STATUS_UNSUBSCRIBED  => '已退订',
        ];
        return $map[$data['status'] ?? 0] ?? '未知';
    }
}
