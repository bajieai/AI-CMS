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

namespace app\api\controller;

use think\BaseController as ThinkController;

/**
 * API基础控制器 - V2.9统一认证层
 */
class BaseController extends ThinkController
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
     * V2.9.5: 兼容 think\BaseController 签名，同时保留 API 层的 data-first 调用习惯
     */
    protected function success(string|array $msg = '', mixed $data = [], int $code = 0): \think\Response
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

        // 兼容旧调用：success($dataArray) 或 success($dataArray, $msg)
        if (is_array($msg)) {
            $data = $msg;
            $msg = func_num_args() > 1 && is_string(func_get_arg(1)) ? func_get_arg(1) : 'success';
        }

        return json(['code' => $code, 'msg' => $msg, 'data' => $data], 200, [], $flags);
    }

    /**
     * 统一失败响应
     */
    protected function error(string $msg = 'error', int $code = 1, mixed $data = []): \think\Response
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
        return json(['code' => $code, 'msg' => $msg, 'data' => $data], 200, [], $flags);
    }
}
