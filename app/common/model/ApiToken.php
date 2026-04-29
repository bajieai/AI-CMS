<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * API令牌模型
 */
class ApiToken extends Model
{
    protected $name = 'api_token';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'rate_limit' => 'integer',
        'last_used_time' => 'integer',
        'expire_time' => 'integer',
        'status' => 'integer',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status'] ? '启用' : '禁用';
    }

    public function getAuthTypeTextAttr($value, $data): string
    {
        return $data['auth_type'] === 'hmac' ? 'HMAC签名' : 'Bearer令牌';
    }

    public function isExpired(): bool
    {
        return $this->expire_time > 0 && $this->expire_time < time();
    }

    /**
     * 检查是否拥有指定权限范围
     */
    public function hasScope(string $required): bool
    {
        $scopes = explode(',', $this->scopes);
        if (in_array('*', $scopes)) {
            return true;
        }
        return in_array($required, $scopes);
    }
}