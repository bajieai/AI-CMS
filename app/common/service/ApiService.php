<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\ApiToken as ApiTokenModel;

/**
 * API服务
 */
class ApiService
{
    /**
     * 生成Token
     */
    public function generateToken(string $name, string $authType, string $scopes, int $rateLimit = 60, int $expireDays = 0): array
    {
        $token = bin2hex(random_bytes(32));
        $secretKey = $authType === 'hmac' ? bin2hex(random_bytes(32)) : '';

        $model = new ApiTokenModel;
        $model->save([
            'name'       => $name,
            'auth_type'  => $authType,
            'token_hash' => hash('sha256', $token),
            'secret_key' => $secretKey,
            'scopes'     => $scopes,
            'rate_limit' => $rateLimit,
            'expire_time'=> $expireDays > 0 ? time() + ($expireDays * 86400) : 0,
            'status'     => 1,
        ]);

        return [
            'success' => true,
            'token'   => $token,
            'secret'  => $secretKey,
            'data'    => $model->toArray(),
        ];
    }

    /**
     * 吊销Token
     */
    public function revokeToken(int $id): bool
    {
        $model = ApiTokenModel::find($id);
        if (!$model) {
            return false;
        }
        $model->status = 0;
        return $model->save();
    }

    /**
     * 检查权限范围
     */
    public function hasScope(array $scopes, string $required): bool
    {
        if (in_array('*', $scopes)) {
            return true;
        }
        return in_array($required, $scopes);
    }

    /**
     * 验证Token（供ApiAuth中间件调用）
     */
    public function validateToken(string $token): ?ApiTokenModel
    {
        $hash = hash('sha256', $token);
        $model = ApiTokenModel::where('token_hash', $hash)->where('status', 1)->find();
        if (!$model) {
            return null;
        }
        if ($model->isExpired()) {
            return null;
        }
        return $model;
    }
}