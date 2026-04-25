<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 审核记录模型
 */
class Review extends Model
{
    protected $name = 'review';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 获取操作文本
     */
    public function getActionTextAttr($value, $data): string
    {
        $map = ['approve' => '通过', 'reject' => '驳回'];
        return $map[$data['action']] ?? '未知';
    }

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * 关联审核人
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
