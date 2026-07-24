<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI配图任务模型 - V2.9.12
 *
 * 状态：0排队中 1生成中 2已完成 3失败
 */
class AiImageTask extends Model
{
    protected $name = 'ai_image_task';
    protected $pk = 'id';

    public const STATUS_QUEUED  = 0;
    public const STATUS_RUNNING = 1;
    public const STATUS_DONE    = 2;
    public const STATUS_FAILED  = 3;

    protected $schema = [
        'id'            => 'int',
        'content_id'    => 'int',
        'member_id'     => 'int',
        'prompt'        => 'text',
        'image_url'     => 'string',
        'provider'      => 'string',
        'status'        => 'int',
        'error_msg'     => 'string',
        'create_time'   => 'int',
        'update_time'   => 'int',
        'complete_time' => 'int',
    ];

    protected $autoWriteTimestamp = true;

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value): string
    {
        $map = [
            self::STATUS_QUEUED  => '排队中',
            self::STATUS_RUNNING => '生成中',
            self::STATUS_DONE    => '已完成',
            self::STATUS_FAILED  => '失败',
        ];
        return $map[$value] ?? '未知';
    }

    /**
     * 创建配图任务
     */
    public static function createTask(int $contentId, int $memberId, string $prompt, string $provider = ''): self
    {
        return self::create([
            'content_id' => $contentId,
            'member_id'  => $memberId,
            'prompt'     => $prompt,
            'provider'   => $provider,
            'status'     => self::STATUS_QUEUED,
        ]);
    }

    /**
     * 获取某内容的配图任务列表
     */
    public static function getByContentId(int $contentId): array
    {
        return self::where('content_id', $contentId)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
    }
}
