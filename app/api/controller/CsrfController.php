<?php
declare(strict_types=1);

namespace app\api\controller;

/**
 * CSRF Token控制器
 * 提供GET接口供前端AJAX自动恢复CSRF Token
 */
class CsrfController
{
    /**
     * 获取当前会话的CSRF Token
     * GET /api/csrf/token
     * 如果session中已有token则复用，否则生成新的
     */
    public function token()
    {
        $tokenName = '__token__';
        $csrfToken = session($tokenName);

        if (empty($csrfToken)) {
            // Session中无token（会话过期或首次），生成新的
            $csrfToken = md5(uniqid((string) mt_rand(), true));
            session($tokenName, $csrfToken);
        }

        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => ['token' => $csrfToken],
        ]);
    }
}
