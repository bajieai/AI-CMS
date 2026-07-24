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

namespace app\common\hook;

/**
 * Hook 事件上下文 — V2.9.25 K-3/M-3
 *
 * 封装事件触发时的参数和执行上下文，提供统一的参数访问接口。
 * 支持自动脱敏（移除/遮蔽敏感字段）。
 */
class HookContext
{
    /** @var string 事件名称 */
    public string $event;

    /** @var int 触发时间戳（微秒级，V2.9.26升级） */
    public int $timestamp;

    /** @var array 事件特定数据 */
    public array $data;

    /** @var array 执行上下文（user_id, ip, module 等） */
    public array $context;

    /** @var string 链路追踪ID */
    public string $traceId;

    /**
     * 敏感字段列表（自动脱敏）
     */
    protected static array $sensitiveFields = [
        'password', 'pwd', 'token', 'access_token', 'secret', 'api_key',
    ];

    /**
     * 部分遮蔽字段（保留部分内容）
     */
    protected static array $maskFields = [
        'mobile' => 'maskPhone',
        'phone' => 'maskPhone',
        'email' => 'maskEmail',
        'idcard' => 'maskIdCard',
        'id_card' => 'maskIdCard',
    ];

    public function __construct(string $event, array $data, array $context = [])
    {
        $this->event = $event;
        $this->timestamp = (int)(microtime(true) * 1000000);
        $this->data = $data;
        $this->context = array_merge([
            'user_id' => 0,
            'ip' => '0.0.0.0',
            'module' => 'unknown',
        ], $context);
        $this->traceId = uniqid('hook_', true);
    }

    /**
     * 获取参数值（支持默认值）
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        if (array_key_exists($key, $this->context)) {
            return $this->context[$key];
        }
        return $default;
    }

    /**
     * 设置参数值
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * 获取上下文值
     */
    public function getContext(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'timestamp' => $this->timestamp,
            'data' => $this->data,
            'context' => $this->context,
            'trace_id' => $this->traceId,
        ];
    }

    /**
     * 脱敏处理（移除/遮蔽敏感字段）
     */
    public function sanitize(): array
    {
        $data = $this->data;
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);

            // 完全移除的字段
            if (in_array($lowerKey, self::$sensitiveFields, true)) {
                unset($data[$key]);
                continue;
            }

            // 部分遮蔽的字段
            if (isset(self::$maskFields[$lowerKey]) && is_string($value)) {
                $method = self::$maskFields[$lowerKey];
                $data[$key] = self::$method($value);
            }
        }
        return $data;
    }

    /**
     * 手机号脱敏：138****1234
     */
    protected static function maskPhone(string $value): string
    {
        if (strlen($value) >= 7) {
            return substr($value, 0, 3) . '****' . substr($value, -4);
        }
        return '***';
    }

    /**
     * 邮箱脱敏：a***@example.com
     */
    protected static function maskEmail(string $value): string
    {
        $atPos = strpos($value, '@');
        if ($atPos === false) {
            return '***';
        }
        $name = substr($value, 0, $atPos);
        $domain = substr($value, $atPos);
        if (strlen($name) <= 1) {
            return $name . '***' . $domain;
        }
        return $name[0] . '***' . $domain;
    }

    /**
     * 身份证脱敏：42************1234
     */
    protected static function maskIdCard(string $value): string
    {
        if (strlen($value) >= 6) {
            return substr($value, 0, 2) . str_repeat('*', strlen($value) - 6) . substr($value, -4);
        }
        return '***';
    }
}
