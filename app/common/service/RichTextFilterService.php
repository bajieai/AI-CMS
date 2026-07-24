<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;

/**
 * V2.9.35 SEC-1: 富文本XSS过滤服务
 * 使用HTMLPurifier对富文本字段进行深度过滤
 * 如果HTMLPurifier未安装，降级为内置白名单过滤
 */
class RichTextFilterService
{
    /**
     * HTMLPurifier实例（懒加载）
     */
    protected static $purifier = null;

    /**
     * 过滤富文本HTML
     */
    public function filter(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $config = Config::get('security.html_purifier', []);
        if (empty($config['enabled'])) {
            return $html;
        }

        // 尝试使用HTMLPurifier
        if (class_exists(\HTMLPurifier::class)) {
            return $this->filterWithPurifier($html, $config);
        }

        // 降级：内置白名单过滤
        return $this->filterWithFallback($html, $config);
    }

    /**
     * 使用HTMLPurifier过滤
     */
    protected function filterWithPurifier(string $html, array $config): string
    {
        if (self::$purifier === null) {
            $purifierConfig = \HTMLPurifier_Config::createDefault();
            $purifierConfig->set('HTML.Allowed', $config['allowed_tags'] ?? 'p,br,strong,em,a,img,ul,ol,li');
            $purifierConfig->set('Attr.AllowedClasses', '');
            $purifierConfig->set('AutoFormat.RemoveEmpty', true);
            $purifierConfig->set('AutoFormat.RemoveSpansWithoutAttributes', true);
            $purifierConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $purifierConfig->set('HTML.SafeIframe', false);
            $purifierConfig->set('URI.Base', '');
            $purifierConfig->set('Cache.SerializerPath', runtime_path() . 'htmlpurifier_cache');

            // 确保缓存目录存在
            $cacheDir = runtime_path() . 'htmlpurifier_cache';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            self::$purifier = new \HTMLPurifier($purifierConfig);
        }

        return self::$purifier->purify($html);
    }

    /**
     * 降级过滤方案（HTMLPurifier未安装时）
     */
    protected function filterWithFallback(string $html, array $config): string
    {
        $allowedTags = $config['allowed_tags'] ?? 'p,br,strong,em,a,img,ul,ol,li';

        // 1. 移除所有script标签
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);

        // 2. 移除事件属性
        $html = preg_replace('/\son\w+\s*=\s*["\']?[^"\'>\s]*/i', '', $html);

        // 3. 移除JS伪协议
        $html = preg_replace('/(href|src)\s*=\s*["\']?\s*javascript:/i', '$1="#"', $html);

        // 4. 移除style中的expression
        $html = preg_replace('/expression\s*\(/i', '', $html);

        // 5. 移除data:URI（防XSS）
        $html = preg_replace('/(src)\s*=\s*["\']?\s*data:text\/html/i', '$1="#"', $html);

        // 6. 过滤不允许的标签
        $allowedTagList = explode(',', $allowedTags);
        $allowedTagList = array_map('trim', $allowedTagList);

        // 匹配所有HTML标签
        $html = preg_replace_callback('/<\/?(\w+)[^>]*>/i', function ($matches) use ($allowedTagList) {
            $tag = strtolower($matches[1]);
            if (in_array($tag, $allowedTagList, true)) {
                return $matches[0];
            }
            return '';
        }, $html);

        return $html;
    }

    /**
     * 批量过滤
     */
    public function filterBatch(array $items): array
    {
        foreach ($items as $key => $value) {
            if (is_string($value)) {
                $items[$key] = $this->filter($value);
            }
        }
        return $items;
    }
}
