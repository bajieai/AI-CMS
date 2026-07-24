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
 * 分享日志模型 - V2.9.9
 * 记录内容分享行为，用于追踪统计
 */
class ShareLog extends Model
{
    protected $pk = 'id';

    protected $type = [
        'id'         => 'integer',
        'content_id' => 'integer',
        'member_id'  => 'integer',
        'created_at' => 'integer',
    ];

    protected $autoWriteTimestamp = false;

    /**
     * 记录分享日志
     */
    public static function log(int $contentId, string $channel, ?int $memberId = 0, string $ip = '', string $referer = ''): self
    {
        return self::create([
            'content_id' => $contentId,
            'channel'    => $channel,
            'member_id'  => $memberId ?: 0,
            'ip'         => $ip,
            'referer'    => $referer,
            'created_at' => time(),
        ]);
    }

    /**
     * 按渠道统计
     */
    public static function statsByChannel(?int $startTime = null, ?int $endTime = null): array
    {
        $query = self::field('channel, COUNT(*) as count')
            ->group('channel')
            ->order('count', 'desc');

        if ($startTime && $endTime) {
            $query->whereBetween('created_at', [$startTime, $endTime]);
        }

        return $query->select()->toArray();
    }

    /**
     * 热门分享内容TOP N
     */
    public static function topContent(int $limit = 10, ?int $startTime = null, ?int $endTime = null): array
    {
        $query = self::alias('sl')
            ->field('sl.content_id, c.title, COUNT(*) as share_count')
            ->leftJoin('content c', 'sl.content_id = c.id')
            ->group('sl.content_id')
            ->order('share_count', 'desc')
            ->limit($limit);

        if ($startTime && $endTime) {
            $query->whereBetween('sl.created_at', [$startTime, $endTime]);
        }

        return $query->select()->toArray();
    }
}
