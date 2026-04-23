<?php
declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 安装检查中间件
 * 检查系统是否已完成安装
 */
class InstallCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(root_path() . 'install.lock')) {
            return redirect('/admin.php');
        }
        return $next($request);
    }
}
