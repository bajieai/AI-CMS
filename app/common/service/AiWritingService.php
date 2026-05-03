<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AiBatchTask;
use app\common\model\Content;
use app\common\service\ai\AiProviderFactory;
use think\facade\Db;
use think\facade\Log;

/**
 * AI写作增强服务 - V2.5新增
 * 长文分段生成 + 写作风格 + 批量生成
 */
class AiWritingService
{
    /**
     * 5种写作风格System Prompt
     */
    protected static array $stylePrompts = [
        'default'    => '你是一位专业的内容创作者，请根据用户要求撰写高质量文章。',
        'formal'     => '你是一位正式的学术/商业写作专家。请使用正式、严谨的语言风格，避免口语化表达，注重逻辑性和专业性。',
        'casual'     => '你是一位轻松有趣的博客作者。请使用通俗易懂、生动活泼的语言，适当加入比喻和例子，让读者感到亲切。',
        'marketing'  => '你是一位资深的营销文案策划师。请使用吸引眼球、富有感染力的语言，突出产品/服务的核心价值，引导读者行动。',
        'technical'  => '你是一位技术文档工程师。请使用精确、简洁的技术语言，注重结构清晰、步骤明确，必要时提供代码示例。',
    ];

    /**
     * 获取写作风格列表
     */
    public static function getStyles(): array
    {
        return [
            ['key' => 'default', 'name' => '默认', 'desc' => '专业内容创作'],
            ['key' => 'formal', 'name' => '正式', 'desc' => '学术/商业严谨风格'],
            ['key' => 'casual', 'name' => '通俗', 'desc' => '轻松活泼博客风格'],
            ['key' => 'marketing', 'name' => '营销', 'desc' => '吸引眼球文案风格'],
            ['key' => 'technical', 'name' => '技术', 'desc' => '技术文档精确风格'],
        ];
    }

    /**
     * 生成单篇文章
     */
    public static function generateArticle(string $topic, string $style = 'default', array $options = []): array
    {
        $systemPrompt = self::$stylePrompts[$style] ?? self::$stylePrompts['default'];
        $maxTokens = (int) ($options['max_tokens'] ?? 2000);
        $longThreshold = (int) ConfigService::get('ai_long_article_threshold', 2000);

        if ($maxTokens >= $longThreshold) {
            return self::generateLongArticle($topic, $systemPrompt, $options);
        }

        $provider = AiProviderFactory::getDefault();
        $prompt = "请撰写一篇关于「{$topic}」的文章。\n\n要求：\n- 标题吸引人\n- 结构完整（开头、正文、结尾）\n- 字数800-1500字";

        if (!empty($options['keywords'])) {
            $prompt .= "\n- 包含关键词：" . implode('、', (array) $options['keywords']);
        }

        $content = $provider->write($prompt, [
            'system_prompt' => $systemPrompt,
            'max_tokens' => $maxTokens,
        ]);

        return self::parseArticle($content, $topic);
    }

    /**
     * 长文分段生成（3000+字）
     */
    protected static function generateLongArticle(string $topic, string $systemPrompt, array $options = []): array
    {
        $provider = AiProviderFactory::getDefault();

        // 第1步：生成大纲
        $outlinePrompt = "请为以下主题生成详细的文章大纲：{$topic}\n\n要求：\n- 包含吸引人的标题\n- 5-8个主要段落\n- 每个段落用1-2句话描述要点\n\n格式：\n## 标题\n\n1. [段落1标题] - [要点描述]\n2. [段落2标题] - [要点描述]\n...";

        $outline = $provider->write($outlinePrompt, [
            'system_prompt' => $systemPrompt,
            'max_tokens' => 1000,
        ]);

        // 第2步：逐段展开
        $sections = preg_split('/\n(?=\d+\.)/', $outline);
        $fullContent = '';
        $prevSummary = '';

        foreach ($sections as $section) {
            if (empty(trim($section))) continue;

            $sectionPrompt = "请根据以下大纲要点，展开写一个完整的段落（约400-600字）：\n\n{$section}";
            if (!empty($prevSummary)) {
                $sectionPrompt .= "\n\n前文概要：{$prevSummary}";
            }

            $sectionContent = $provider->write($sectionPrompt, [
                'system_prompt' => $systemPrompt,
                'max_tokens' => 1000,
            ]);

            $fullContent .= $sectionContent . "\n\n";
            $prevSummary = mb_substr($sectionContent, 0, 100);
        }

        return self::parseArticle($fullContent, $topic);
    }

    /**
     * AI优化已有文章
     */
    public static function optimizeArticle(string $content, string $type = 'seo', array $options = []): string
    {
        $provider = AiProviderFactory::getDefault();

        $prompts = [
            'seo'      => "请对以下文章进行SEO优化，提升搜索引擎友好度，保持原文核心意思不变：\n\n{$content}",
            'polish'   => "请对以下文章进行润色优化，提升文笔和可读性，保持原文核心意思不变：\n\n{$content}",
            'expand'   => "请对以下文章进行扩写，丰富细节和例子，字数扩展50%以上：\n\n{$content}",
            'abstract' => "请为以下文章生成一段150字以内的摘要：\n\n{$content}",
        ];

        $prompt = $prompts[$type] ?? $prompts['polish'];
        return $provider->write($prompt, [
            'system_prompt' => '你是一位资深编辑，擅长优化和改写文章内容。',
            'max_tokens' => (int) ($options['max_tokens'] ?? 3000),
        ]);
    }

    /**
     * 创建批量生成任务
     */
    public static function createBatchTask(string $title, string $keywords, string $style = 'default', int $cateId = 0, int $modelId = 0): AiBatchTask
    {
        $keywordList = array_filter(array_map('trim', explode("\n", $keywords)));
        $maxCount = (int) ConfigService::get('ai_batch_max_count', 10);

        if (count($keywordList) > $maxCount) {
            throw new \Exception("批量生成最多{$maxCount}篇");
        }

        return AiBatchTask::create([
            'title' => $title,
            'keywords' => $keywords,
            'style' => $style,
            'cate_id' => $cateId,
            'model_id' => $modelId,
            'total' => count($keywordList),
            'completed' => 0,
            'status' => 0,
        ]);
    }

    /**
     * 执行批量生成（CLI调用）
     */
    public static function executeBatchTask(int $taskId): bool
    {
        $task = AiBatchTask::find($taskId);
        if (!$task || $task->status === 2) return false;

        $task->status = 1;
        $task->save();

        $keywords = array_filter(array_map('trim', explode("\n", $task->keywords)));

        try {
            foreach ($keywords as $keyword) {
                $article = self::generateArticle($keyword, $task->style, ['max_tokens' => 2000]);

                Content::create([
                    'title' => $article['title'],
                    'content' => $article['content'],
                    'cate_id' => $task->cate_id,
                    'status' => 0,
                    'source' => 'ai_batch',
                    'create_time' => time(),
                    'update_time' => time(),
                ]);

                $task->completed++;
                $task->save();
            }

            $task->status = 2;
            $task->save();
            return true;
        } catch (\Exception $e) {
            $task->status = 3;
            $task->save();
            Log::error("批量生成任务失败 #{$taskId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 解析AI生成的文章
     */
    protected static function parseArticle(string $content, string $defaultTitle = ''): array
    {
        $title = $defaultTitle;
        if (preg_match('/^#\s+(.+)/m', $content, $matches)) {
            $title = trim($matches[1]);
            $content = preg_replace('/^#\s+.+/m', '', $content);
        } elseif (preg_match('/^(.+)\n{2,}/', $content, $matches)) {
            $firstLine = trim($matches[1]);
            if (mb_strlen($firstLine) <= 100 && !str_contains($firstLine, '。')) {
                $title = $firstLine;
                $content = preg_replace('/^.+\n{2,}/', '', $content);
            }
        }

        return ['title' => $title ?: '未命名文章', 'content' => trim($content)];
    }
}
