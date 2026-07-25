<?php
declare(strict_types=1);

namespace plugin\helloworld\src;

/**
 * HelloWorld示例插件主类 - V2.5
 */
class HelloWorld
{
    /**
     * 获取问候语
     */
    public static function greet(string $name = 'World'): string
    {
        return "Hello, {$name}!";
    }
}
