<?php
declare(strict_types=1);

namespace app\api\middleware;

use app\common\model\Member as MemberModel;
use think\facade\Cache;
use think\facade\Cookie;
use think\facade\Log;

/**
 * API会员身份解析中间件
 * 从Cookie Token中解析前台会员身份，注入到请求对象
 * 
 * 注意：此中间件不强制登录，仅做身份解析。需要登录的接口自行判断 apiMemberId。
 */
class ApiMemberAuth
{
    public function handle($request, \Closure $next)
    {
        $memberId = $this->resolveMemberId($request);
        
        if ($memberId > 0) {
            $request->apiMemberId = $memberId;
        } else {
            $request->apiMemberId = null;
        }

        return $next($request);
    }

    /**
     * 解析会员ID
     * 优先从Cookie Token解析，支持X-Member-Token头（用于跨域/APP场景）
     */
    protected function resolveMemberId($request): int
    {
        $token = $request->header('X-Member-Token', '');
        if (empty($token)) {
            $token = Cookie::get('member_token');
        }

        if (empty($token)) {
            return 0;
        }

        $hash = sha1($token);
        $cacheKey = 'i8j_member_token_' . $hash;
        $memberData = Cache::get($cacheKey);

        if (!empty($memberData) && is_array($memberData) && !empty($memberData['id'])) {
            return (int) $memberData['id'];
        }

        // Cache未命中，查库
        $memberId = Cache::get($cacheKey . '_id');
        if (empty($memberId)) {
            return 0;
        }

        $member = MemberModel::find($memberId);
        if ($member && $member->status == 1) {
            return (int) $member->id;
        }

        return 0;
    }

    /**
     * 获取API请求中的会员ID（带GET参数过渡兼容）
     * @deprecated GET参数member_id将在V2.8中移除，请使用apiMemberId
     */
    public static function getApiMemberId($request): ?int
    {
        // 优先从中间件注入的认证信息获取
        if (isset($request->apiMemberId) && $request->apiMemberId > 0) {
            return (int) $request->apiMemberId;
        }

        // 过渡兼容：从GET参数获取（记录弃用日志）
        $getMemberId = (int) $request->get('member_id', 0);
        if ($getMemberId > 0) {
            Log::warning('[DEPRECATION] API请求使用GET参数member_id，将在V2.8移除。请确保客户端传递Cookie或X-Member-Token。', [
                'url'       => $request->url(true),
                'member_id' => $getMemberId,
                'ip'        => $request->ip(),
            ]);
            return $getMemberId;
        }

        return null;
    }
}
