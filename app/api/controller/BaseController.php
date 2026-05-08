<?php
declare(strict_types=1);

namespace app\api\controller;

use think\Controller;

/**
 * API基础控制器 - V2.9统一认证层
 */
class BaseController extends Controller
{
    /**
     * 从Token中获取会员ID
     */
    protected function getMemberId(): int
    {
        $token = $this->request->header('Authorization', '');
        if (preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $token = $matches[1];
        }
        if (empty($token)) {
            return 0;
        }
        $cacheKey = 'api_token:' . $token;
        $cached   = cache($cacheKey);
        return (int) ($cached['member_id'] ?? 0);
    }

    /**
     * 获取当前会员信息
     */
    protected function getMember(): ?\app\common\model\Member
    {
        $memberId = $this->getMemberId();
        if ($memberId <= 0) {
            return null;
        }
        return \app\common\model\Member::find($memberId);
    }

    /**
     * 统一成功响应
     */
    protected function success($data = [], string $msg = 'success'): \think\Response
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 统一失败响应
     */
    protected function error(string $msg = 'error', int $code = 1): \think\Response
    {
        return json(['code' => $code, 'msg' => $msg]);
    }
}
