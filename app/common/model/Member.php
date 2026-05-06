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
        'level_id' => 'integer',
        'vip_expire_time' => 'integer',
        'last_login_time' => 'integer',
    ];

    protected $hidden = ['password'];

    public function setPasswordAttr($value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public function getStatusTextAttr($value, $data): string
    {
        return match ((int) $data['status']) {
            1 => '正常',
            2 => '待审核',
            default => '禁用',
        };
    }

    public function getStatusBadgeAttr($value, $data): string
    {
        return match ((int) $data['status']) {
            1 => 'bg-success',
            2 => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
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

    public function level()
    {
        return $this->belongsTo(MemberLevel::class, 'level_id');
    }
}