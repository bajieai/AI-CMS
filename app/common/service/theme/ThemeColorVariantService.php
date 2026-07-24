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

namespace app\common\service\theme;

use app\common\model\TemplateColorVariant;
use app\common\model\TemplateStore;
use app\common\service\ai\AiProviderFactory;
use think\facade\Log;

/**
 * 模板配色变体服务 - V2.9.12新增
 */
class ThemeColorVariantService
{
    /**
     * 基于主模板生成5种配色方案
     */
    public function generateVariants(int $storeId): array
    {
        $store = TemplateStore::with('category')->find($storeId);
        if (empty($store)) {
            throw new \RuntimeException('模板不存在');
        }

        $industry = $store->category->slug ?? 'corporate';
        $style = $store->name;

        // 构建AI Prompt
        $prompt = $this->buildColorPrompt($style, $industry);

        try {
            $provider = AiProviderFactory::getDefault();
            $response = $provider->write($prompt, [
                'system_prompt' => '你是一个专业的前端配色设计师，精通色彩理论和品牌视觉设计。请严格按JSON格式返回配色方案。',
                'max_tokens' => 4096,
                'temperature' => 0.7,
            ]);

            $variants = $this->parseColorResponse($response);

            // 保存到数据库
            $saved = [];
            foreach ($variants as $index => $variant) {
                $model = new TemplateColorVariant();
                $model->store_id = $storeId;
                $model->name = $variant['name'];
                $model->description = $variant['description'] ?? '';
                $model->colors = json_encode($variant['colors']);
                $model->css_variables = $this->colorsToCssVars($variant['colors']);
                $model->is_default = ($index === 0) ? 1 : 0;
                $model->sort = $index;
                $model->save();

                $saved[] = $model->id;
            }

            Log::info("[ThemeColorVariant] 生成配色方案: store_id={$storeId}, count=" . count($saved));
            return ['success' => true, 'variant_ids' => $saved];
        } catch (\Throwable $e) {
            Log::error("[ThemeColorVariant] 生成失败: " . $e->getMessage());
            throw new \RuntimeException('配色方案生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 应用配色变体到模板CSS
     */
    public function applyVariant(int $variantId, string $themePath): array
    {
        $variant = TemplateColorVariant::find($variantId);
        if (empty($variant)) {
            throw new \RuntimeException('配色方案不存在');
        }

        $cssVars = $variant->css_variables;

        // 查找模板CSS文件并替换:root中的变量
        $cssFiles = $this->collectCssFiles($themePath);
        $replaced = [];

        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            // 替换 :root { ... } 块中的内容
            $newContent = preg_replace('/:root\s*\{[^}]*\}/s', ":root {\n" . trim($cssVars) . "\n}", $content);
            if ($newContent !== $content) {
                file_put_contents($file, $newContent, LOCK_EX);
                $replaced[] = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file);
            }
        }

        return ['success' => true, 'replaced_files' => $replaced];
    }

    /**
     * 构建AI配色Prompt
     */
    protected function buildColorPrompt(string $style, string $industry): string
    {
        return <<<PROMPT
请为"{$style}"风格的{$industry}行业网站模板设计5种不同的配色方案。

要求：
1. 每种方案包含10个核心CSS变量色值
2. 变量名统一使用：primary, primaryLight, primaryDark, secondary, accent, bg, bgSecondary, bgSection, text, textSecondary, border
3. 方案之间要有明显视觉差异（商务蓝、活力橙、自然绿、优雅紫、极简灰等方向）
4. 所有色值使用6位HEX格式（如 #2563EB）
5. 返回严格的JSON数组格式

输出格式示例：
[
  {
    "name": "商务蓝",
    "description": "专业、可信、现代简约",
    "colors": {
      "primary": "#2563EB",
      "primaryLight": "#DBEAFE",
      "primaryDark": "#1E40AF",
      "secondary": "#64748B",
      "accent": "#F59E0B",
      "bg": "#FFFFFF",
      "bgSecondary": "#F8FAFC",
      "bgSection": "#F1F5F9",
      "text": "#1E293B",
      "textSecondary": "#64748B",
      "border": "#E2E8F0"
    }
  }
]
PROMPT;
    }

    /**
     * 解析AI响应为配色数组
     */
    protected function parseColorResponse(string $response): array
    {
        // 尝试提取JSON块
        if (preg_match('/```json\s*(.*?)```/s', $response, $m)) {
            $json = trim($m[1]);
        } elseif (preg_match('/```\s*(.*?)```/s', $response, $m)) {
            $json = trim($m[1]);
        } else {
            $json = trim($response);
        }

        $data = json_decode($json, true);
        if (!is_array($data) || count($data) < 1) {
            throw new \RuntimeException('AI响应格式异常，无法解析配色方案');
        }

        return $data;
    }

    /**
     * 色值对象转CSS变量文本
     */
    protected function colorsToCssVars(array $colors): string
    {
        $lines = [];
        foreach ($colors as $key => $value) {
            $varName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $key));
            $lines[] = "    --{$varName}: {$value};";
        }
        return implode("\n", $lines);
    }

    /**
     * 收集CSS文件
     */
    protected function collectCssFiles(string $themePath): array
    {
        $files = [];
        if (!is_dir($themePath)) {
            return $files;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'css') {
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }
}
