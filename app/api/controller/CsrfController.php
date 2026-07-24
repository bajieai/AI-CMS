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

/**
 * CSRF Token控制器
 * @api_group 安全
 * @api_desc CSRF Token获取，供前端AJAX自动恢复
 */
class CsrfController
{
    /**
     * 获取CSRF Token
     * @api 获取CSRF Token
     * @api_desc 获取当前会话的CSRF Token，如已过期则自动生成新的
     * @return json 返回csrf_token
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
