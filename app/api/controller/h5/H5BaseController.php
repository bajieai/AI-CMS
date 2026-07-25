<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use app\api\controller\BaseController;
use think\facade\Cache;
use think\facade\Db;
use think\Request;
use think\response\Json;

/**
 * H5移动端API基类控制器
 * 提供JWT认证、频率限制、请求合并等能力
 */
class H5BaseController extends BaseController
{
    protected int $memberId = 0;
    protected string $lang = 'zh-cn';
    protected int $rateLimitPerMinute = 60;

    public function initialize(): void
    {
        parent::initialize();
        $this->lang = $this->request->header('Accept-Language', 'zh-cn');
        $token = $this->request->header('Authorization', '');
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
            $this->memberId = $this->validateToken($token);
        }
        $this->checkRateLimit();
    }

    /**
     * 验证JWT Token
     */
    protected function validateToken(string $token): int
    {
        $memberId = Cache::get('h5_token_' . $token);
        return $memberId ? (int)$memberId : 0;
    }

    /**
     * 频率限制
     */
    protected function checkRateLimit(): void
    {
        $key = 'h5_rate_' . ($this->memberId ?: $this->request->ip());
        $count = Cache::inc($key);
        if ($count === 1) {
            Cache::expire($key, 60);
        }
        if ($count > $this->rateLimitPerMinute) {
            json(['code' => 429, 'msg' => '请求过于频繁，请稍后再试'])->send();
            exit;
        }
    }

    /**
     * 成功响应
     */
    protected function success($data = null, string $msg = 'success'): Json
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data, 'timestamp' => time()]);
    }

    /**
     * 错误响应
     */
    protected function error(string $msg = 'error', int $code = 1, $data = null): Json
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data, 'timestamp' => time()]);
    }
}
