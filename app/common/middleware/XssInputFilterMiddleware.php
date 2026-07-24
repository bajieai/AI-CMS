<?php

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;

/**
 * V2.9.35 SEC-1: XSS输入过滤中间件
 * 在请求进入控制器前过滤GET/POST参数中的恶意XSS代码
 * 与现有XssEscapeMiddleware(V2.9.5输出过滤)配合，形成输入+输出双重防护
 */
class XssInputFilterMiddleware
{
    /**
     * 富文本字段白名单（由RichTextFilterService处理）
     */
    protected array $richTextFields = [];

    /**
     * 需要移除的危险标签
     */
    protected array $strictTags = ['script', 'iframe', 'object', 'embed', 'base', 'form'];

    public function handle(Request $request, Closure $next): Response
    {
        $config = Config::get('security', []);
        if (empty($config['xss_input']['enabled'])) {
            return $next($request);
        }

        $this->richTextFields = $config['xss_input']['rich_text_fields'] ?? ['content', 'description', 'body', 'text'];
        $this->strictTags = $config['xss_input']['strict_tags'] ?? $this->strictTags;
        $level = $config['level'] ?? 'standard';

        // 过滤GET参数
        $this->filterArray($request->get(), $request, 'get', $level);

        // 过滤POST参数
        $this->filterArray($request->post(), $request, 'post', $level);

        return $next($request);
    }

    /**
     * 递归过滤数组参数
     */
    protected function filterArray(array $data, Request $request, string $source, string $level): void
    {
        $filtered = [];
        $modified = false;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = $this->filterValueRecursive($value, $level);
                if ($result !== $value) {
                    $filtered[$key] = $result;
                    $modified = true;
                } else {
                    $filtered[$key] = $value;
                }
            } elseif (is_string($value)) {
                $filteredValue = $this->filterString($value, $key, $level);
                if ($filteredValue !== $value) {
                    $filtered[$key] = $filteredValue;
                    $modified = true;
                } else {
                    $filtered[$key] = $value;
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        if ($modified) {
            if ($source === 'get') {
                $request->withGet($filtered);
            } else {
                $request->withPost($filtered);
            }
        }
    }

    /**
     * 递归过滤值
     */
    protected function filterValueRecursive(array $data, string $level): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterValueRecursive($value, $level);
            } elseif (is_string($value)) {
                $data[$key] = $this->filterString($value, (string) $key, $level);
            }
        }
        return $data;
    }

    /**
     * 过滤字符串中的XSS代码
     */
    protected function filterString(string $value, string $key, string $level): string
    {
        // 富文本字段跳过输入过滤（由HTMLPurifier处理）
        if (in_array(strtolower($key), array_map('strtolower', $this->richTextFields), true)) {
            return $value;
        }

        $config = Config::get('security.xss_input', []);

        // 1. 移除事件属性 on*=
        if (!empty($config['event_attrs'])) {
            $value = preg_replace($config['event_attrs'], '', $value);
        }

        // 2. 移除JS伪协议 javascript:
        if (!empty($config['js_protocol'])) {
            $value = preg_replace($config['js_protocol'], '', $value);
        }

        // 3. 移除CSS注入 expression()
        if (!empty($config['css_injection'])) {
            $value = preg_replace($config['css_injection'], '', $value);
        }

        // 4. 根据级别移除危险标签
        if ($level === 'strict') {
            // 严格模式：strip_tags
            $value = strip_tags($value);
        } elseif ($level === 'standard') {
            // 标准模式：移除script/iframe/object/embed/base/form
            foreach ($this->strictTags as $tag) {
                $value = preg_replace('/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is', '', $value);
                $value = preg_replace('/<' . $tag . '[^>]*>/is', '', $value);
                $value = preg_replace('/<\/' . $tag . '>/is', '', $value);
            }
        } else {
            // 宽松模式：仅移除script
            $value = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $value);
            $value = preg_replace('/<script[^>]*>/is', '', $value);
            $value = preg_replace('/<\/script>/is', '', $value);
        }

        return $value;
    }
}
