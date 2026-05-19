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
 * 会员收藏模型
 */
class MemberFavorite extends Model
{
    protected $name = 'member_favorite';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'member_id' => 'integer',
        'content_id' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}