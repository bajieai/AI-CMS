<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 消息通知模型
 */
class Notification extends Model
{
    protected $name = 'notification';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'receiver_id' => 'integer',
        'is_read' => 'integer',
    ];

    public function getTypeTextAttr($value, $data): string
    {
        $map = [
            'system' => '系统通知',
            'review' => '审核通知',
            'publish' => '发布通知',
            'title' => '标题通知',
            'comment_reply' => '评论回复',
            'content_approve' => '内容审核',
        ];
        return $map[$data['type']] ?? '未知';
    }

    public function getIsReadTextAttr($value, $data): string
    {
        return $data['is_read'] ? '已读' : '未读';
    }
}