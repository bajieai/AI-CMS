<?php

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.35 SEC: 安全事件日志模型
 */
class SecurityLog extends Model
{
    protected $name = 'security_log';
    protected $autoWriteTimestamp = false;

    // 事件类型常量
    public const TYPE_XSS = 'xss';
    public const TYPE_CSRF = 'csrf';
    public const TYPE_SQLI = 'sqli';
    public const TYPE_FILE_UPLOAD = 'file_upload';
    public const TYPE_AUTH_DENY = 'auth_deny';
    public const TYPE_LOGIN_FAIL = 'login_fail';
    public const TYPE_LOGIN_SUCCESS = 'login_success';
    public const TYPE_PERMISSION_DENIED = 'permission_denied';
    public const TYPE_SENSITIVE_ACCESS = 'sensitive_access';

    // 严重级别常量
    public const SEVERITY_LOW = 1;
    public const SEVERITY_MEDIUM = 2;
    public const SEVERITY_HIGH = 3;
    public const SEVERITY_CRITICAL = 4;
}
