<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 第三方登录绑定模型
 * V2.9.38 SYS-INTEG-1
 */
class OauthUser extends Model
{
    protected $name = 'oauth_user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $type = [
        'oauth_data' => 'json',
    ];

    const PROVIDER_WECHAT = 'wechat';
    const PROVIDER_QQ = 'qq';
    const PROVIDER_GITHUB = 'github';
    const PROVIDER_WEIBO = 'weibo';

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
