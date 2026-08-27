<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 全局辅助函数
// +----------------------------------------------------------------------

declare(strict_types=1);

/**
 * msubstr 字符串截取函数（ThinkPHP 5.x/6.x 兼容函数）
 * ThinkPHP 8.1 不再内置此函数，模板中大量使用了 {$var|msubstr=0,100} 语法
 *
 * @param string $str 要截取的字符串
 * @param int $start 开始位置
 * @param int $length 截取长度
 * @param string $charset 字符编码
 * @param bool $suffix 是否加省略号
 * @return string
 */
if (!function_exists('msubstr')) {
    function msubstr(?string $str, int $start = 0, int $length = 100, string $charset = 'utf-8', bool $suffix = true): string
    {
        // V2.9.47: 对 null/空值容错，避免模板中 excerpt 等字段为空时 msubstr(null) 抛 TypeError
        if ($str === null || $str === '') {
            return '';
        }
        $str = (string) $str;
        if (function_exists('mb_substr')) {
            $slice = mb_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset] ?? $re['utf-8'], $str, $match);
            $slice = implode('', array_slice($match[0], $start, $length));
        }

        $strLen = function_exists('mb_strlen') ? mb_strlen($str, $charset) : strlen($str);
        if ($suffix && $strLen > $length) {
            return $slice . '...';
        }
        return $slice;
    }
}
