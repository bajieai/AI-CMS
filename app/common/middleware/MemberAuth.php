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

namespace app\common\middleware;

use app\common\model\Member as MemberModel;
use app\common\service\CacheService;
use think\facade\Cache;
use think\facade\Cookie;

/**
 * 前台会员认证中间件
 */
class MemberAuth
{
    public function handle($request, \Closure $next)
    {
        $token = Cookie::get('member_token');
        $memberInfo = null;

        if (!empty($token)) {
            $hash = sha1($token);
            $cacheKey = 'i8j_member_token_' . $hash;
            $memberData = Cache::get($cacheKey);

            if (!empty($memberData) && is_array($memberData)) {
                $memberInfo = $memberData;
            } else {
                $memberId = Cache::get($cacheKey . '_id');
                if (!empty($memberId)) {
                    $member = MemberModel::find($memberId);
                    if ($member && $member->status == 1) {
                        $memberInfo = [
                            'id'       => $member->id,
                            'username' => $member->username,
                            'nickname' => $member->nickname,
                            'avatar'   => $member->avatar,
                        ];
                        Cache::tag(CacheService::TAG_MEMBER)->set($cacheKey, $memberInfo, 7200);
                    }
                }
            }
        }

        $request->memberInfo = $memberInfo;
        $request->isMemberLogin = !empty($memberInfo);

        return $next($request);
    }
}