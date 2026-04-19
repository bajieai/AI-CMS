<?php
declare(strict_types=1);

namespace app;

use think\Request as ThinkRequest;
use think\facade\Config;

/**
 * 自定义请求类
 * 
 * 覆盖 path() 方法，修复 UrlHandler trait 中 $this->config() 不存在的问题。
 * ThinkPHP 8.x 的 UrlHandler trait 在 path() 方法中调用了 $this->config('url_html_suffix')，
 * 但 Request 基类并未实现 config() 方法，导致运行时致命错误。
 */
class Request extends ThinkRequest
{
    /**
     * 重写 path() 方法，避免调用不存在的 $this->config()
     * @return string 当前请求URL的pathinfo信息(不含URL后缀)
     */
    public function path(): string
    {
        $pathinfo = $this->pathinfo();
        $suffix = Config::get('app.url_html_suffix', false);

        if (false === $suffix) {
            return $pathinfo;
        }

        return preg_replace('/\.(' . $suffix . ')$/i', '', $pathinfo);
    }
}
