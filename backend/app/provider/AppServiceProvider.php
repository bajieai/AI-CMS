<?php
declare(strict_types=1);

namespace app\provider;

use think\Service;
use app\Request;

/**
 * 应用服务提供者 - 注册自定义Request类以修复 $this->config() 缺失
 */
class AppServiceProvider extends Service
{
    public function register(): void
    {
        // 覆盖默认的 Request 类绑定，使用带 config() 方法的自定义类
        $this->app->bind('request', Request::class);
    }

    public function boot(): void
    {
        // 启动时无需额外操作
    }
}
