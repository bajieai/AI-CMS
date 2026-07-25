<?php
declare(strict_types=1);

namespace app\home\middleware;

use app\common\service\ml\LangSwitchService;
use think\Request;
use think\Response;

/**
 * 前台多语言检测中间件
 * V2.9.37 I18N-1
 * 
 * 优先级: 用户选择 > 浏览器语言 > 系统默认
 * 存储: Cookie(aicms_lang, 30天) + LocalStorage(前端JS)
 */
class I18nDetectMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $service = new LangSwitchService();
        $langCode = $service->detectLanguage($request);
        // 注入到请求中
        $request->withInput(['lang' => $langCode]);
        // 设置到全局变量供模板使用
        $GLOBALS['current_lang'] = $langCode;
        // 设置语言包
        if (function_exists('app')) {
            app()->setLang($langCode);
        }
        return $next($request);
    }
}
