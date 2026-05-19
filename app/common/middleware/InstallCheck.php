<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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
