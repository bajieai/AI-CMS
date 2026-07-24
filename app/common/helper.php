<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
// AI-CMS V2.0 公共辅助函数

use think\facade\Cache;
use think\facade\Config;

if (!function_exists('load_cms_configs')) {
    /**
     * 从数据库加载系统配置到 ThinkPHP Config
     * 支持 comment_auto_approve -> config('comment.comment_auto_approve')
     */
    function load_cms_configs(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $configs = Cache::remember('site_configs_all', function () {
            return \app\common\model\Config::column('value', 'name');
        }, 3600);

        foreach ($configs as $name => $value) {
            $pos = strpos($name, '_');
            if ($pos !== false) {
                $group = substr($name, 0, $pos);
                $key = substr($name, $pos + 1);
                Config::set([$key => $value], $group);
            }
        }
    }
}

if (!function_exists('get_status_text')) {
    /**
     * 获取状态文本
     */
    function get_status_text(int $status): string
    {
        $map = [
            0 => '草稿',
            1 => '待审',
            2 => '已发布',
            -1 => '已删除',
        ];
        return $map[$status] ?? '未知';
    }
}

if (!function_exists('get_type_text')) {
    /**
     * 获取信息类型文本
     */
    function get_type_text(int $type): string
    {
        $map = [
            1 => '产品',
            2 => '案例',
            3 => '新闻',
            4 => '下载',
            5 => '招聘',
            6 => '单页',
        ];
        return $map[$type] ?? '未知';
    }
}

if (!function_exists('get_type_slug')) {
    /**
     * 获取信息类型URL标识
     */
    function get_type_slug(int $type): string
    {
        $map = [
            1 => 'product',
            2 => 'case',
            3 => 'news',
            4 => 'download',
            5 => 'job',
            6 => 'page',
        ];
        return $map[$type] ?? 'info';
    }
}

if (!function_exists('get_role_text')) {
    /**
     * 获取角色文本
     */
    function get_role_text(int $roleId): string
    {
        $map = [
            1 => '超级管理员',
            2 => '管理员',
            3 => '编辑',
        ];
        return $map[$roleId] ?? '未知';
    }
}

if (!function_exists('format_bytes')) {
    /**
     * 格式化字节大小
     */
    function format_bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }
        return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
    }
}

if (!function_exists('i8j_cache')) {
    /**
     * AI-CMS缓存快捷方法
     * @param string $key 缓存键
     * @param mixed $value 缓存值（null表示获取，false表示删除）
     * @param int|null $expire 有效期（秒）
     * @param string|null $tag 缓存标签
     */
    function i8j_cache(string $key, mixed $value = null, ?int $expire = null, ?string $tag = null): mixed
    {
        if ($value === null) {
            return Cache::get($key);
        }
        
        if ($value === false) {
            if ($tag) {
                Cache::clear();
            }
            return Cache::delete($key);
        }
        
        if ($tag) {
            return Cache::set($key, $value, $expire);
        }
        
        return Cache::set($key, $value, $expire);
    }
}

// V2.5 多语言辅助函数
if (!function_exists('__')) {
    /**
     * 多语言翻译函数
     * @param string $key 翻译键 (group.key 格式，如 common.hello)
     * @param array $replace 替换变量 ['name' => 'value']
     * @param string|null $lang 指定语言（null=当前语言）
     * @return string
     */
    function __(string $key, array $replace = [], ?string $lang = null): string
    {
        // 解析 group.key 格式
        $group = 'common';
        if (str_contains($key, '.')) {
            [$group, $key] = explode('.', $key, 2);
        }

        try {
            $text = \app\common\service\LanguageService::translate($key, $group, [], $lang);
        } catch (\Throwable) {
            $text = $key;
        }

        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }

        return $text;
    }
}
