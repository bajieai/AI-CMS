<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 私信会话模型 - V2.6
 */
class MessageConversation extends Model
{
    protected $name = 'message_conversation';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'user_id_1' => 'integer',
        'user_id_2' => 'integer',
        'last_message_id' => 'integer',
        'last_message_time' => 'integer',
        'unread_count_1' => 'integer',
        'unread_count_2' => 'integer',
    ];
}
