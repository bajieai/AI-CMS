<?php
declare(strict_types=1);

namespace think;

use think\App;
use think\exception\HttpException;

/**
 * ThinkPHP 8 基础控制器
 * 替代旧版 BaseController，提供请求和响应的基础功能
 */
abstract class BaseController
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 请求实例
     */
    protected Request $request;

    /**
     * 模板变量
     */
    protected array $assignData = [];

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;

        $this->initialize();
    }

    /**
     * 初始化方法（子类可覆盖）
     */
    protected function initialize(): void
    {
    }

    /**
     * 模板变量赋值
     */
    protected function assign(string|array $name, mixed $value = null): static
    {
        if (is_array($name)) {
            $this->assignData = array_merge($this->assignData, $name);
        } else {
            $this->assignData[$name] = $value;
        }
        return $this;
    }

    /**
     * 渲染模板输出（自动包含assign的变量）
     */
    protected function view(string $template = '', array $vars = [], int $code = 200, callable $filter = null): Response
    {
        return Response::create($template, 'view', $code)
            ->assign(array_merge($this->assignData, $vars))
            ->filter($filter);
    }

    /**
     * 成功响应
     */
    protected function success(string $msg = '操作成功', mixed $data = [], int $code = 0): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }

    /**
     * 失败响应
     */
    protected function error(string $msg = '操作失败', int $code = 1, mixed $data = []): Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }
}
