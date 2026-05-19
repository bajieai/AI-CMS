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

namespace app\common\service;

use app\common\model\AiBatchTask;
use app\common\model\Content;
use app\common\model\AiTemplate;
use app\common\service\ai\AiProviderFactory;
use app\common\service\ai\ImageProviderFactory;
use app\common\service\AiTemplateService;
use think\facade\Db;
use think\facade\Log;

/**
 * AI写作增强服务 - V2.5新增
 * 长文分段生成 + 写作风格 + 批量生成
 */
class AiWritingService
{
    /**
     * 6种写作风格System Prompt（V2.9.4增强：新增relaxed/warm风格）
     */
    protected static array $stylePrompts = [
        'default'    => '你是一位专业的内容创作者，请根据用户要求撰写高质量文章。',
        'formal'     => '你是一位正式的学术/商业写作专家。请使用正式、严谨的语言风格，避免口语化表达，注重逻辑性和专业性。语言规范，用词准确，句式完整，客观中性。',
        'relaxed'    => '你是一位轻松有趣的博客作者。请使用通俗易懂、生动活泼的语言，口语化表达，短句为主，适当使用语气词，有亲和力。让读者感到轻松愉快。',
        'professional' => '你是一位技术/专业领域的资深作者。术语准确，逻辑严密，数据支撑，论证充分。注重内容的专业性和深度，适合产品介绍、技术文档等。',
        'warm'       => '你是一位温暖的写作伙伴。请使用第二人称称呼读者，关怀语气，温暖正面。注重共情和引导，就像朋友之间交流一样亲切自然，适合使用指南、客服文章等。',
        'marketing'  => '你是一位资深的营销文案策划师。请使用吸引眼球、富有感染力的语言，突出产品/服务的核心价值，引导读者行动。',
        'technical'  => '你是一位技术文档工程师。请使用精确、简洁的技术语言，注重结构清晰、步骤明确，必要时提供代码示例。',
    ];

    /**
     * 获取写作风格列表（V2.9.4增强：6种风格）
     */
    public static function getStyles(): array
    {
        return [
            ['key' => 'default', 'name' => '默认', 'desc' => '专业内容创作', 'preview' => '标准的文章写作风格，适用于大部分场景'],
            ['key' => 'formal', 'name' => '正式', 'desc' => '新闻/公告严谨风格', 'preview' => '语言规范、客观中性，适合新闻动态、公告通知'],
            ['key' => 'relaxed', 'name' => '轻松', 'desc' => '轻松活泼博客风格', 'preview' => '口语化、短句为主，适合博客、个人日记'],
            ['key' => 'professional', 'name' => '专业', 'desc' => '技术/产品专业风格', 'preview' => '术语准确、逻辑严密，适合产品介绍、技术文档'],
            ['key' => 'warm', 'name' => '亲切', 'desc' => '温暖关怀指导风格', 'preview' => '第二人称、关怀语气，适合使用指南、客服文章'],
            ['key' => 'marketing', 'name' => '营销', 'desc' => '吸引眼球文案风格', 'preview' => '富有感染力、引导行动，适合营销推广'],
        ];
    }

    /**
     * V2.9.4: 获取栏目的默认写作风格
     */
    public static function getCategoryStyle(int $cateId): string
    {
        if ($cateId <= 0) return 'default';
        try {
            $style = \think\facade\Db::name('category')->where('id', $cateId)->value('default_style');
            return $style ?: 'default';
        } catch (\Throwable) {
            return 'default';
        }
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
     * V2.9 集成字段映射引擎 + 表单联动 + 质量检测
     */
    public static function executeBatchTask(int $taskId): bool
    {
        $task = AiBatchTask::find($taskId);
        if (!$task || $task->status === 2) return false;

        $task->status = 1;
        $task->save();

        $keywords = array_filter(array_map('trim', explode("\n", $task->keywords)));

        // V2.6 模板模式：检测 template_id > 0 时走模板Prompt引擎
        $template = null;
        if ($task->template_id > 0) {
            $template = AiTemplate::find($task->template_id);
            if (!$template || $template->status !== 1) {
                Log::warning("批量生成任务 #{$taskId}: 关联模板不存在或已禁用，降级为普通模式");
                $template = null;
                $task->template_id = 0;
                $task->save();
            }
        }

        $contentService = new ContentService();

        try {
            foreach ($keywords as $keyword) {
                $article = null;
                $retryCount = 0;
                $maxRetry = 2;
                $qualityPassed = false;

                do {
                    if ($template && $template->generate_mode === 'example') {
                        // 参考示例模式
                        $prompt = AiTemplateService::buildExamplePrompt($template, $keyword);
                        $systemPrompt = self::$stylePrompts[$template->style] ?? self::$stylePrompts['default'];
                        $article = self::executeWithPrompt($prompt, $systemPrompt, $keyword, $template);
                    } elseif ($template) {
                        // V2.9: NLP模板模式 — 使用结构化Prompt（字段映射+表单联动）
                        $prompt = AiTemplateService::buildStructuredPrompt($template, ['keyword' => $keyword]);
                        $systemPrompt = self::$stylePrompts[$template->style] ?? self::$stylePrompts['default'];
                        $rawOutput = self::executeWithPromptRaw($prompt, $systemPrompt, $template);

                        // V2.9: 应用字段映射引擎
                        $cmsData = AiTemplateService::applyFieldMapping($template, $rawOutput);

                        // V2.9 M2: 解析表单数据并填充扩展字段
                        if (!empty($cmsData['form_data']) && is_array($cmsData['form_data'])) {
                            $fieldsConfig = $template->fields_array;
                            $parsedForm = $contentService->parseFormData($cmsData['form_data'], $fieldsConfig);
                            $cmsData = array_merge($cmsData, $parsedForm);
                            unset($cmsData['form_data']);
                        }

                        $article = [
                            'title'   => $cmsData['title']   ?? '未命名文章',
                            'content' => $cmsData['content'] ?? $rawOutput,
                        ];

                        // 将映射字段合并到article供后续使用
                        foreach (['seo_title','seo_keywords','seo_description','summary','tags','source','author'] as $f) {
                            if (!empty($cmsData[$f])) {
                                $article[$f] = $cmsData[$f];
                            }
                        }
                        if (!empty($cmsData['ext_data'])) {
                            $article['ext_data'] = $cmsData['ext_data'];
                        }

                        // V2.9: 质量检测决策
                        $qualityResult = AiTemplateService::applyQualityConfig($template, $article);
                        if ($qualityResult['passed']) {
                            $qualityPassed = true;
                            Log::info("批量生成任务 #{$taskId} 质量检测通过: 评分 {$qualityResult['score']}");
                        } elseif ($qualityResult['retry_suggested'] && $retryCount < $maxRetry) {
                            $retryCount++;
                            Log::warning("批量生成任务 #{$taskId} 质量不达标(评分 {$qualityResult['score']})，第{$retryCount}次重试");
                            continue;
                        } elseif ($qualityResult['action'] === 'reject') {
                            Log::error("批量生成任务 #{$taskId} 质量过低已拒绝: 评分 {$qualityResult['score']}");
                            $task->completed++;
                            $task->save();
                            break 2; // 跳过该关键词，继续下一个
                        } else {
                            // notify: 记录但继续
                            $qualityPassed = true;
                            Log::warning("批量生成任务 #{$taskId} 质量不达标但已记录: 评分 {$qualityResult['score']}");
                        }
                    } else {
                        // 原有逻辑不变（向后兼容无模板的任务）
                        $article = self::generateArticle($keyword, $task->style, ['max_tokens' => 2000]);
                        $qualityPassed = true;
                    }
                } while (!$qualityPassed && $retryCount <= $maxRetry);

                if (!$qualityPassed) {
                    continue;
                }

                // 构建Content创建数据
                $contentData = [
                    'title'   => $article['title'],
                    'content' => $article['content'],
                    'cate_id' => $task->cate_id,
                    'status'  => 0,
                    'source'  => 'ai_batch',
                    'create_time' => time(),
                    'update_time' => time(),
                ];

                // 映射SEO字段
                if (!empty($article['seo_title'])) {
                    $contentData['seo_title'] = $article['seo_title'];
                }
                if (!empty($article['seo_keywords'])) {
                    $contentData['seo_keywords'] = $article['seo_keywords'];
                }
                if (!empty($article['seo_description'])) {
                    $contentData['seo_description'] = $article['seo_description'];
                }
                if (!empty($article['summary'])) {
                    $contentData['excerpt'] = $article['summary'];
                }
                if (!empty($article['tags'])) {
                    $contentData['tags'] = $article['tags'];
                }
                if (!empty($article['source'])) {
                    $contentData['source'] = $article['source'];
                }
                if (!empty($article['author'])) {
                    $contentData['author'] = $article['author'];
                }
                // 质量评分
                if (!empty($qualityResult['score'])) {
                    $contentData['quality_score'] = $qualityResult['score'];
                }

                // 处理扩展字段
                if (!empty($article['ext_data'])) {
                    $contentData['ext'] = $article['ext_data'];
                }

                $contentRecord = Content::create($contentData);

                // V2.9 M12: AI配图生成（根据模板image_config配置）
                if ($template && $contentRecord) {
                    $imgCfg = $template->image_config_array;
                    if (($imgCfg['images'] ?? '0') === '1' && ($imgCfg['source'] ?? '0') === '1') {
                        $count = min((int) ($imgCfg['count'] ?? 1), 3);
                        $imgPrompt = $article['title'] ?? $keyword;
                        try {
                            $imgProvider = ImageProviderFactory::getDefault();
                            $imgResult = $imgProvider->generateImage($imgPrompt, ['count' => $count]);
                            if (!empty($imgResult['url'])) {
                                $contentRecord->cover = $imgResult['url'];
                                $contentRecord->save();
                            }
                        } catch (\Exception $imgEx) {
                            Log::warning("AI配图生成失败 #{$taskId}: " . $imgEx->getMessage());
                        }
                    }
                }

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
     * 使用预构建的Prompt执行文章生成（V2.6 模板模式专用）
     */
    protected static function executeWithPrompt(string $prompt, string $systemPrompt, string $defaultTitle, ?AiTemplate $template): array
    {
        $rawOutput = self::executeWithPromptRaw($prompt, $systemPrompt, $template);
        return self::parseArticle($rawOutput, $defaultTitle);
    }

    /**
     * V2.9 新增：执行Prompt并返回原始AI输出字符串（供字段映射引擎使用）
     */
    protected static function executeWithPromptRaw(string $prompt, string $systemPrompt, ?AiTemplate $template): string
    {
        // 使用任务指定的模型ID或模板的模型ID
        $modelId = $template?->model_id ?: 0;

        if ($modelId > 0) {
            try {
                $provider = AiProviderFactory::getById($modelId);
            } catch (\Exception $e) {
                Log::warning("模板指定模型(#{$modelId})不可用，回退到默认模型");
                $provider = AiProviderFactory::getDefault();
            }
        } else {
            $provider = AiProviderFactory::getDefault();
        }

        return $provider->write($prompt, [
            'system_prompt' => $systemPrompt,
            'max_tokens'     => 3000,
        ]);
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
