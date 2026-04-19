<?php
declare(strict_types=1);

namespace app\exception;

use think\App;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 全局异常处理
 */
class Handler extends Handle
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 错误码映射
     */
    protected array $errorCodes = [
        400 => '请求参数错误',
        401 => '未授权，请登录',
        403 => '无权限访问',
        404 => '资源不存在',
        405 => '请求方法不允许',
        422 => '数据验证失败',
        429 => '请求过于频繁',
        500 => '服务器内部错误',
        503 => '服务暂不可用',
    ];

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct($app);
    }

    /**
     * 渲染异常
     */
    public function render($request, Throwable $e): Response
    {
        // 处理业务异常
        if ($e instanceof BusinessException) {
            return $this->renderBusinessException($e);
        }
        
        // 处理验证异常
        if ($e instanceof ValidateException) {
            return $this->renderValidateException($e);
        }
        
        // 处理HTTP异常
        if ($e instanceof HttpException) {
            return $this->renderHttpException($e);
        }
        
        // 其他异常
        return $this->renderSystemException($e);
    }

    /**
     * 渲染业务异常
     */
    protected function renderBusinessException(BusinessException $e): Response
    {
        $data = [
            'code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
        ];
        
        if ($e->hasErrors()) {
            $data['errors'] = $e->getErrors();
        }
        
        return json($data, $e->getHttpCode());
    }

    /**
     * 渲染验证异常
     */
    protected function renderValidateException(ValidateException $e): Response
    {
        $errors = [];
        foreach ($e->getError() as $field => $error) {
            $errors[$field] = is_array($error) ? implode(',', $error) : $error;
        }
        
        return json([
            'code' => 422,
            'message' => $e->getMessage(),
            'errors' => $errors,
        ], 422);
    }

    /**
     * 渲染HTTP异常
     */
    protected function renderHttpException(HttpException $e): Response
    {
        $code = $e->getStatusCode();
        $message = $this->errorCodes[$code] ?? $e->getMessage();
        
        return json([
            'code' => $code,
            'message' => $message,
        ], $code);
    }

    /**
     * 渲染系统异常
     */
    protected function renderSystemException(Throwable $e): Response
    {
        // 调试模式下显示详细信息
        if ($this->app->isDebug()) {
            $data = [
                'code' => 500,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ];
        } else {
            $data = [
                'code' => 500,
                'message' => '服务器内部错误，请稍后重试',
            ];
        }
        
        // 记录日志
        $this->logException($e);
        
        return json($data, 500);
    }

    /**
     * 记录异常日志
     */
    protected function logException(Throwable $e): void
    {
        $log = [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $this->app->request->url(true),
            'method' => $this->app->request->method(),
            'ip' => $this->app->request->ip(),
            'user_agent' => $this->app->request->header('user-agent', ''),
        ];
        
        trace($log, 'error');
    }

    /**
     * 判断是否JSON请求
     */
    protected function isJsonRequest(): bool
    {
        $accept = $this->app->request->header('accept', '');
        $contentType = $this->app->request->header('content-type', '');
        
        return stripos($accept, 'application/json') !== false 
            || stripos($contentType, 'application/json') !== false
            || $this->app->request->isAjax();
    }
}
