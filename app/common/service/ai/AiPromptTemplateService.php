<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint AI3: AI Prompt模板管理服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;

/**
 * AI Prompt模板管理服务 - V2.9.31 AI3-2
 * 提供可配置的AI提示词模板，支持变量替换
 */
class AiPromptTemplateService
{
    private const string CACHE_TAG = 'ai_prompt';
    private const string CACHE_KEY = 'ai_prompt_templates';

    /**
     * 默认Prompt模板库
     */
    private const DEFAULT_TEMPLATES = [
        'seo_title' => [
            'name' => 'SEO标题生成',
            'description' => '根据内容生成SEO友好的标题',
            'template' => '请为以下内容生成一个SEO友好的标题（30-60字符）：\n\n内容：{content}\n\n要求：包含核心关键词，吸引点击，不超过60字符。',
            'variables' => ['content'],
        ],
        'seo_description' => [
            'name' => 'SEO描述生成',
            'description' => '根据内容生成SEO描述',
            'template' => '请为以下内容生成一段SEO描述（80-160字符）：\n\n内容：{content}\n\n要求：概括核心内容，包含关键词，吸引点击。',
            'variables' => ['content'],
        ],
        'content_summary' => [
            'name' => '内容摘要生成',
            'description' => '生成长内容的精简摘要',
            'template' => '请为以下内容生成一段200字以内的摘要：\n\n{content}\n\n要求：保留核心信息，语言简洁。',
            'variables' => ['content'],
        ],
        'keyword_extract' => [
            'name' => '关键词提取',
            'description' => '从内容中提取核心关键词',
            'template' => '请从以下内容中提取5个核心关键词，用逗号分隔：\n\n{content}',
            'variables' => ['content'],
        ],
        'title_optimization' => [
            'name' => '标题优化',
            'description' => '优化现有标题，提升点击率',
            'template' => '请优化以下标题，使其更吸引点击（30-60字符）：\n\n原标题：{title}\n\n要求：保留核心含义，增加吸引力，可加入数字、疑问等手法。',
            'variables' => ['title'],
        ],
        'content_expand' => [
            'name' => '内容扩写',
            'description' => '对简短内容进行扩写丰富',
            'template' => '请对以下内容进行扩写，使其更加详细和丰富：\n\n{content}\n\n要求：保持原意，增加细节和例子，语言流畅。',
            'variables' => ['content'],
        ],
    ];

    /**
     * 获取所有模板
     */
    public function getAll(): array
    {
        $templates = Cache::get(self::CACHE_KEY);
        if ($templates !== null) {
            return $templates;
        }

        // 从数据库加载（如存在），否则使用默认
        $dbTemplates = $this->loadFromDb();
        $templates = !empty($dbTemplates) ? $dbTemplates : self::DEFAULT_TEMPLATES;

        Cache::set(self::CACHE_KEY, $templates, 3600);
        return $templates;
    }

    /**
     * 获取单个模板
     */
    public function get(string $key): ?array
    {
        $all = $this->getAll();
        return $all[$key] ?? null;
    }

    /**
     * 渲染模板（变量替换）
     */
    public function render(string $key, array $variables = []): string
    {
        $template = $this->get($key);
        if (empty($template)) {
            return '';
        }

        $text = $template['template'];
        foreach ($variables as $var => $value) {
            $text = str_replace('{' . $var . '}', $value, $text);
        }
        return $text;
    }

    /**
     * 保存模板（支持自定义覆盖）
     */
    public function save(string $key, array $data): bool
    {
        $all = $this->getAll();
        $all[$key] = array_merge($all[$key] ?? [], $data);

        // 保存到数据库（如果表存在）
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            Db::table($prefix . 'ai_prompt_template')->replace()->insert([
                'key' => $key,
                'name' => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
                'template' => $data['template'] ?? '',
                'variables' => json_encode($data['variables'] ?? [], JSON_UNESCAPED_UNICODE),
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 表可能不存在，忽略
        }

        Cache::set(self::CACHE_KEY, $all, 3600);
        return true;
    }

    /**
     * 重置为默认模板
     */
    public function reset(): void
    {
        Cache::set(self::CACHE_KEY, self::DEFAULT_TEMPLATES, 3600);
    }

    /**
     * 从数据库加载
     */
    private function loadFromDb(): array
    {
        try {
            $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
            $rows = Db::table($prefix . 'ai_prompt_template')
                ->column('template', 'key', 'name', 'description', 'variables');
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'template' => $row['template'],
                    'variables' => json_decode($row['variables'] ?? '[]', true) ?: [],
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
