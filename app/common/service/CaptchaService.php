<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Cache;

/**
 * 验证码服务 - V2.5新增
 * 支持算术验证码（默认）+ 腾讯云验证码（预留）
 */
class CaptchaService
{
    /**
     * 生成算术验证码
     * @return array [key, image_base64]
     */
    public static function generateMath(): array
    {
        $a = random_int(1, 50);
        $b = random_int(1, 50);
        $answer = $a + $b;
        $question = "{$a} + {$b} = ?";

        $key = 'captcha_' . md5(uniqid((string) mt_rand(), true));
        Cache::set($key, (string) $answer, 300); // 5分钟有效

        // 生成简单SVG验证码图片
        $svg = self::buildSvgCaptcha($question);

        return [
            'key' => $key,
            'image' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ];
    }

    /**
     * 验证验证码
     */
    public static function verify(string $key, string $answer): bool
    {
        $correct = Cache::get($key);
        if (empty($correct)) return false;

        Cache::delete($key); // 一次性使用

        return trim($answer) === (string) $correct;
    }

    /**
     * 检查表单是否需要验证码
     */
    public static function isFormCaptchaRequired(string $formCode): bool
    {
        $enabledForms = ConfigService::get('captcha_enabled_forms', '');
        if (empty($enabledForms)) return false;

        $forms = array_map('trim', explode(',', $enabledForms));
        return in_array($formCode, $forms);
    }

    /**
     * 生成SVG验证码图片
     */
    protected static function buildSvgCaptcha(string $text): string
    {
        $width = 150;
        $height = 50;
        $fontSize = 24;

        // 干扰线
        $lines = '';
        for ($i = 0; $i < 5; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $color = sprintf('#%06x', random_int(0x666666, 0x999999));
            $lines .= "<line x1='{$x1}' y1='{$y1}' x2='{$x2}' y2='{$y2}' stroke='{$color}' stroke-width='1'/>";
        }

        // 干扰点
        $dots = '';
        for ($i = 0; $i < 30; $i++) {
            $x = random_int(0, $width);
            $y = random_int(0, $height);
            $color = sprintf('#%06x', random_int(0x666666, 0x999999));
            $dots .= "<circle cx='{$x}' cy='{$y}' r='1' fill='{$color}'/>";
        }

        // 文字（带随机偏移和旋转）
        $textX = 15;
        $textY = 35;
        $rotate = random_int(-5, 5);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}">
  <rect width="100%" height="100%" fill="#f0f0f0"/>
  {$lines}
  {$dots}
  <text x="{$textX}" y="{$textY}" font-family="Arial" font-size="{$fontSize}" fill="#333" transform="rotate({$rotate}, {$textX}, {$textY})">{$text}</text>
</svg>
SVG;
    }
}
