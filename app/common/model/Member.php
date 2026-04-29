<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 前台会员模型
 */
class Member extends Model
{
    protected $name = 'member';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'status' => 'integer',
        'last_login_time' => 'integer',
    ];

    protected $hidden = ['password'];

    public function setPasswordAttr($value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status'] ? '正常' : '禁用';
    }

    public function oauth()
    {
        return $this->hasMany(MemberOauth::class, 'member_id');
    }

    public function likes()
    {
        return $this->hasMany(MemberLike::class, 'member_id');
    }

    public function favorites()
    {
        return $this->hasMany(MemberFavorite::class, 'member_id');
    }
}