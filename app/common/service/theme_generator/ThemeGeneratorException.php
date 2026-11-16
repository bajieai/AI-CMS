<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme_generator;

/**
 * AI-THEME 适配器异常
 *
 * 所有可预期的失败都带稳定的错误码（E-TG-xxx），控制器据此回执，
 * 不用异常消息做判断。
 */
class ThemeGeneratorException extends \RuntimeException
{
    /** @var string 稳定错误码，形如 E-TG-005 */
    private string $errorCode;

    public function __construct(string $errorCode, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
    }

    /**
     * 错误码（E-TG-xxx）
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
