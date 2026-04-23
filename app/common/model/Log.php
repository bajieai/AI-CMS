<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 操作日志模型
 */
class Log extends Model
{
    protected $name = 'log';

    // 自动时间戳（仅create_time）
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'user_id' => 'integer',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->bind(['username']);
    }
}
