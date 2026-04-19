<?php
declare(strict_types=1);

namespace app\exception;

use Exception;

/**
 * 业务异常
 */
class BusinessException extends Exception
{
    /**
     * 错误码
     */
    protected int $errorCode;

    /**
     * 字段级错误
     */
    protected array $errors;

    /**
     * HTTP状态码
     */
    protected int $httpCode;

    /**
     * 构造函数
     */
    public function __construct(
        string $message = '操作失败',
        int $errorCode = 400,
        array $errors = [],
        int $httpCode = 400
    ) {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->errors = $errors;
        $this->httpCode = $httpCode;
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * 获取字段级错误
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取HTTP状态码
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * 判断是否有字段级错误
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        $result = [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];
        
        if ($this->hasErrors()) {
            $result['errors'] = $this->errors;
        }
        
        return $result;
    }
}
