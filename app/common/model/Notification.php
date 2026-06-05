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
            'content_approve' => '内容审核通过',
            'content_reject' => '内容审核驳回',
            'reward_receive' => '收到打赏',
            'push' => '内容推送', // V2.9.18 D-1: 站内广播推送
        ];
        return $map[$data['type']] ?? '未知';
    }

    public function getIsReadTextAttr($value, $data): string
    {
        return $data['is_read'] ? '已读' : '未读';
    }
}