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
     * 生成验证码（根据驱动自动选择）
     * @return array [key, image_base64]
     */
    public static function generate(): array
    {
        $driver = ConfigService::get('captcha_driver', 'local');
        if ($driver === 'tencent') {
            // 腾讯验证码模式：返回AppID，前端自行渲染
            return [
                'key'   => '',
                'image' => '',
                'mode'  => 'tencent',
                'appid' => ConfigService::get('captcha_tencent_appid', ''),
            ];
        }
        // 默认本地GD增强验证码
        return self::generateImage();
    }

    /**
     * 生成GD增强图像验证码（干扰线/扭曲文字/噪点）
     * V2.7 P0-7: 替代简单SVG，提高安全性
     * @return array [key, image_base64]
     */
    public static function generateImage(): array
    {
        $a = random_int(1, 50);
        $b = random_int(1, 50);
        $answer = $a + $b;
        $question = "{$a} + {$b}";

        $key = 'captcha_' . md5(uniqid((string) mt_rand(), true));
        Cache::set($key, (string) $answer, 300); // 5分钟有效

        // GD生成图片
        $width  = 200;
        $height = 60;
        $image = imagecreatetruecolor($width, $height);

        // 背景色
        $bgColor = imagecolorallocate($image, 245, 247, 250);
        imagefill($image, 0, 0, $bgColor);

        // 随机噪点
        for ($i = 0; $i < 200; $i++) {
            $color = imagecolorallocate($image, random_int(180, 220), random_int(180, 220), random_int(180, 220));
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $color);
        }

        // 干扰线
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($image, random_int(100, 180), random_int(100, 180), random_int(100, 180));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $color);
        }

        // 绘制文字（确保所有字符都在图片边界内）
        $chars = str_split($question);
        $charCount = count($chars);
        // 根据字符数动态计算起始位置和步进，确保不溢出
        $padding = 20;                       // 左右边距
        $usableWidth = $width - $padding * 2; // 可用宽度 160px
        $charWidth = (int) ($usableWidth / max($charCount, 1)); // 每字符平均宽度
        $charWidth = max(18, min(28, $charWidth)); // 限制在 18-28px 之间
        $totalWidth = $charWidth * $charCount;
        $x = (int) ($padding + ($usableWidth - $totalWidth) / 2); // 水平居中起始

        foreach ($chars as $char) {
            $size = random_int(18, 22);
            $color = imagecolorallocate($image, random_int(30, 100), random_int(30, 100), random_int(30, 100));
            $angle = random_int(-10, 10);
            $y = random_int(38, 48); // y 在图片中部偏下，不超出底部
            imagettftext($image, $size, $angle, $x, $y, $color, self::getFontPath(), $char);
            $x += $charWidth;
        }

        // 输出为base64
        ob_start();
        imagepng($image);
        $data = ob_get_clean();
        imagedestroy($image);

        return [
            'key'   => $key,
            'image' => 'data:image/png;base64,' . base64_encode($data),
            'mode'  => 'image',
        ];
    }

    /**
     * 获取系统可用字体路径
     */
    protected static function getFontPath(): string
    {
        // 优先使用系统字体，降级使用内置路径
        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
            '/System/Library/Fonts/Helvetica.ttc',
            'C:\\Windows\\Fonts\\arial.ttf',
            root_path() . 'public/assets/fonts/DejaVuSans.ttf',
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        // 无字体时返回空字符串（GD会退化为默认字体）
        return '';
    }

    /**
     * 生成算术验证码（保留向后兼容）
     * @deprecated 使用 generateImage() 或 generate()
     */
    public static function generateMath(): array
    {
        return self::generateImage();
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
