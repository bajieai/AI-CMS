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

namespace app\common\service\theme;

/**
 * V2.9.9 F-1/I-4: 模板主题 Schema 校验服务
 * 兼容 V2(colors+options) 和 V3(layouts+assets+pages) 两种格式
 * I-4: 规则从 config/theme_schema.php 读取，常量作为 fallback
 */
class ThemeSchemaService
{
    // 保留常量作为 fallback（兼容已有调用）
    public const V2_REQUIRED = ['name', 'version', 'description', 'author'];
    public const V2_RECOMMENDED = ['colors', 'options', 'supports', 'default_device'];
    public const V3_REQUIRED = ['name', 'version', 'description', 'author', 'type'];
    public const V3_RECOMMENDED = ['color', 'layouts', 'assets', 'pages'];
    public const MARKET_STANDARD = [
        'name', 'version', 'description', 'author', 'category', 'tags',
        'preview', 'type', 'supports', 'colors', 'layouts', 'assets',
    ];

    /**
     * 从配置读取规则（I-4配置化）
     */
    public static function getConfig(): array
    {
        return config('theme_schema', []);
    }

    /**
     * 获取行业列表（单源模式）
     */
    public static function getIndustries(): array
    {
        $cfg = self::getConfig();
        return $cfg['industries'] ?? config('ai.theme_industry_categories', []);
    }

    /**
     * 校验单个 theme.json
     */
    public static function validate(string $jsonPath): array
    {
        if (!file_exists($jsonPath)) {
            return self::result('error', '文件不存在: ' . $jsonPath);
        }

        $content = file_get_contents($jsonPath);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return self::result('error', 'JSON解析失败: ' . json_last_error_msg());
        }

        $keys = array_keys($data);
        $errors = [];
        $warnings = [];

        // 识别格式版本
        $isV2 = in_array('colors', $keys) || in_array('options', $keys);
        $isV3 = in_array('layouts', $keys) || in_array('assets', $keys) || in_array('pages', $keys);

        if ($isV2 && !$isV3) {
            // V2 校验
            $missingReq = array_diff(self::V2_REQUIRED, $keys);
            foreach ($missingReq as $f) {
                $errors[] = "V2 缺少核心字段: $f";
            }
            $missingRec = array_diff(self::V2_RECOMMENDED, $keys);
            foreach ($missingRec as $f) {
                $warnings[] = "V2 缺少建议字段(引导升级): $f";
            }
        } elseif ($isV3 && !$isV2) {
            // V3 校验
            $missingReq = array_diff(self::V3_REQUIRED, $keys);
            foreach ($missingReq as $f) {
                $errors[] = "V3 缺少核心字段: $f";
            }
            $missingRec = array_diff(self::V3_RECOMMENDED, $keys);
            foreach ($missingRec as $f) {
                $warnings[] = "V3 缺少建议字段: $f";
            }
        } elseif ($isV2 && $isV3) {
            // 混合格式：既含 V2 又含 V3 字段
            $warnings[] = '混合格式(V2+V3)，建议统一为市场标准格式';
            $missingReq = array_diff(self::V2_REQUIRED, $keys);
            foreach ($missingReq as $f) {
                $errors[] = "缺少核心字段: $f";
            }
        } else {
            // 未知格式，按最小集校验
            $missingReq = array_diff(['name', 'version'], $keys);
            foreach ($missingReq as $f) {
                $errors[] = "缺少必要字段: $f";
            }
        }

        // 市场标准字段缺失（统一 warning，引导而非惩罚）
        $missingMarket = array_diff(self::MARKET_STANDARD, $keys);
        if ($missingMarket) {
            $warnings[] = '市场标准缺失: ' . implode(', ', $missingMarket);
        }

        // 字段类型校验
        if (isset($data['version']) && !is_string($data['version'])) {
            $errors[] = 'version 必须为字符串';
        }
        if (isset($data['name']) && mb_strlen($data['name']) > 50) {
            $warnings[] = 'name 超过50字符，建议精简';
        }

        if ($errors) {
            return self::result('error', '校验失败', $errors, $warnings);
        }
        if ($warnings) {
            return self::result('warning', '校验通过（有警告）', [], $warnings);
        }
        return self::result('ok', '校验通过');
    }

    /**
     * 批量校验所有主题
     */
    public static function validateAll(string $themesDir): array
    {
        $results = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themesDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getFilename() !== 'theme.json') continue;
            $path = $file->getPathname();
            $rel = str_replace(rtrim($themesDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, '', $path);
            $results[$rel] = self::validate($path);
        }

        return $results;
    }

    private static function result(string $status, string $message, array $errors = [], array $warnings = []): array
    {
        return compact('status', 'message', 'errors', 'warnings');
    }
}
