<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Db;

/**
 * 评论模型
 */
class Comment extends Model
{
    protected $name = 'comment';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'member_id' => 'integer',
        'parent_id' => 'integer',
        'status' => 'integer',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '待审', 1 => '已通过', -1 => '已拒绝'];
        return $map[$data['status']] ?? '未知';
    }

    public static function onAfterInsert($model)
    {
        if ($model->status == 1) {
            Db::name('content')->where('id', $model->content_id)->inc('comment_count')->update();
        }
    }

    public static function onAfterUpdate($model)
    {
        $oldStatus = $model->getOrigin('status');
        $newStatus = $model->status;
        if ($oldStatus != $newStatus) {
            if ($newStatus == 1) {
                Db::name('content')->where('id', $model->content_id)->inc('comment_count')->update();
            } elseif ($oldStatus == 1) {
                Db::name('content')->where('id', $model->content_id)->where('comment_count', '>', 0)->dec('comment_count')->update();
            }
        }
    }

    public static function onAfterDelete($model)
    {
        if ($model->status == 1) {
            Db::name('content')->where('id', $model->content_id)->where('comment_count', '>', 0)->dec('comment_count')->update();
        }
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}