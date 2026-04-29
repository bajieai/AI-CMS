<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 会员点赞模型
 */
class MemberLike extends Model
{
    protected $name = 'member_like';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'member_id' => 'integer',
        'content_id' => 'integer',
    ];

    public static function onAfterInsert($model)
    {
        Db::name('content')->where('id', $model->content_id)->inc('like_count')->update();
    }

    public static function onAfterDelete($model)
    {
        Db::name('content')->where('id', $model->content_id)->where('like_count', '>', 0)->dec('like_count')->update();
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}