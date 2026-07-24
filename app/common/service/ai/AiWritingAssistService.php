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

namespace app\common\service\ai;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI辅助写作增强服务 — V2.9.39 AI-DEEP-2
 *
 * 9种写作辅助操作：
 *   1. continueWriting — 续写
 *   2. rewrite — 改写
 *   3. expand — 扩写
 *   4. summarize — 摘要
 *   5. polish — 润色
 *   6. proofread — 校对
 *   7. styleConvert — 风格转换
 *   8. formatConvert — 格式转换
 *   9. emotionAdjust — 情感调整
 *
 * 复用 AiProviderFactory 调用AI模型
 */
class AiWritingAssistService
{
    private const CACHE_TAG = 'ai_writing_assist';
    private const CACHE_TTL = 300; // 5分钟

    /** 最大输入文本长度 */
    private const MAX_INPUT_LENGTH = 10000;

    /**
     * 1. 续写
     * @param string $text 原文
     * @param int $length 续写长度
     * @param array $options 额外选项
     * @return array ['success' => bool, 'result' => string]
     */
    public function continueWriting(string $text, int $length = 500, array $options = []): array
    {
        $text = $this->truncateText($text);
        $lengthLabel = $this->lengthToLabel($length);

        $prompt = "请续写以下内容，续写约{$lengthLabel}字。保持语气、风格和主题一致，自然衔接原文：\n\n---\n原文：\n{$text}\n---\n\n请直接输出续写内容，不要重复原文。";

        return $this->executeAi($prompt, $options, 'continue_writing');
    }

    /**
     * 2. 改写
     * @param string $text 原文
     * @param string $style 改写风格
     * @param array $options 额外选项
     * @return array
     */
    public function rewrite(string $text, string $style = 'professional', array $options = []): array
    {
        $text = $this->truncateText($text);
        $styleDesc = $this->getStyleDescription($style);

        $prompt = "请用{$styleDesc}改写以下内容，保持原意不变但表达方式完全不同：\n\n---\n原文：\n{$text}\n---\n\n请直接输出改写后的内容。";

        return $this->executeAi($prompt, $options, 'rewrite');
    }

    /**
     * 3. 扩写
     * @param string $text 原文
     * @param int $targetLength 目标长度
     * @param array $options 额外选项
     * @return array
     */
    public function expand(string $text, int $targetLength = 1000, array $options = []): array
    {
        $text = $this->truncateText($text);

        $prompt = "请将以下内容扩写至约{$targetLength}字。增加细节、论据、例证，使内容更丰富充实，但不要偏离原意：\n\n---\n原文：\n{$text}\n---\n\n请直接输出扩写后的内容。";

        return $this->executeAi($prompt, $options, 'expand');
    }

    /**
     * 4. 摘要
     * @param string $text 原文
     * @param int $maxLength 最大长度
     * @param array $options 额外选项
     * @return array
     */
    public function summarize(string $text, int $maxLength = 200, array $options = []): array
    {
        $text = $this->truncateText($text);

        $prompt = "请将以下内容总结为{$maxLength}字以内的摘要，保留核心观点和关键信息：\n\n---\n原文：\n{$text}\n---\n\n请直接输出摘要内容。";

        return $this->executeAi($prompt, $options, 'summarize');
    }

    /**
     * 5. 润色
     * @param string $text 原文
     * @param array $options 额外选项
     * @return array
     */
    public function polish(string $text, array $options = []): array
    {
        $text = $this->truncateText($text);

        $prompt = "请润色以下内容，改善语言表达，使其更加通顺、优美、专业。保持原意不变，修正语病和不通顺的表达：\n\n---\n原文：\n{$text}\n---\n\n请直接输出润色后的内容。";

        return $this->executeAi($prompt, $options, 'polish');
    }

    /**
     * 6. 校对
     * @param string $text 原文
     * @param array $options 额外选项
     * @return array ['success' => bool, 'result' => string, 'errors' => array]
     */
    public function proofread(string $text, array $options = []): array
    {
        $text = $this->truncateText($text);

        $prompt = "请校对以下内容，找出错别字、语法错误、标点错误和用词不当的地方。\n请先列出所有错误（格式：序号. 原文→修正 | 错误类型），然后给出修正后的完整内容。\n\n---\n原文：\n{$text}\n---\n\n请按以下格式输出：\n## 错误列表\n1. 原文→修正 | 错误类型\n...\n\n## 修正后内容\n（修正后的完整文本）";

        $result = $this->executeAi($prompt, $options, 'proofread');

        // 解析错误列表
        $errors = [];
        if ($result['success'] && preg_match_all('/(\d+)\.\s*(.+?)→(.+?)\s*\|\s*(.+)/', $result['result'], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $errors[] = [
                    'original'  => trim($match[2]),
                    'corrected' => trim($match[3]),
                    'type'      => trim($match[4]),
                ];
            }
        }

        $result['errors'] = $errors;
        return $result;
    }

    /**
     * 7. 风格转换
     * @param string $text 原文
     * @param string $targetStyle 目标风格
     * @param array $options 额外选项
     * @return array
     */
    public function styleConvert(string $text, string $targetStyle = 'formal', array $options = []): array
    {
        $text = $this->truncateText($text);
        $styleDesc = $this->getStyleDescription($targetStyle);

        $prompt = "请将以下内容转换为{$styleDesc}，保持核心信息不变但调整语言风格：\n\n---\n原文：\n{$text}\n---\n\n请直接输出转换后的内容。";

        return $this->executeAi($prompt, $options, 'style_convert');
    }

    /**
     * 8. 格式转换
     * @param string $text 原文
     * @param string $targetFormat 目标格式（markdown/html/plain/text/list）
     * @param array $options 额外选项
     * @return array
     */
    public function formatConvert(string $text, string $targetFormat = 'markdown', array $options = []): array
    {
        $text = $this->truncateText($text);

        $formatDesc = match($targetFormat) {
            'markdown' => 'Markdown格式（使用#标题、**加粗**、-列表等Markdown语法）',
            'html'     => 'HTML格式（使用<h1><p><ul><li>等HTML标签）',
            'plain'    => '纯文本格式（去除所有格式标记，保留段落结构）',
            'text'     => '纯文本格式（去除所有格式标记，连续文本）',
            'list'     => '要点列表格式（提取关键信息为带编号的要点列表）',
            default    => $targetFormat . '格式',
        };

        $prompt = "请将以下内容转换为{$formatDesc}，保持内容完整性：\n\n---\n原文：\n{$text}\n---\n\n请直接输出转换后的内容。";

        return $this->executeAi($prompt, $options, 'format_convert');
    }

    /**
     * 9. 情感调整
     * @param string $text 原文
     * @param string $emotion 目标情感（positive/negative/neutral/enthusiastic/calm/professional）
     * @param array $options 额外选项
     * @return array
     */
    public function emotionAdjust(string $text, string $emotion = 'neutral', array $options = []): array
    {
        $text = $this->truncateText($text);

        $emotionDesc = match($emotion) {
            'positive'      => '积极正面（使用积极向上的语言，传递正能量）',
            'negative'      => '严肃审慎（使用更谨慎严肃的语气）',
            'neutral'       => '中性客观（去除主观色彩，保持客观中立）',
            'enthusiastic'  => '热情洋溢（使用富有感染力的热情表达）',
            'calm'          => '平和冷静（使用平静沉稳的语言风格）',
            'professional'  => '专业严谨（使用专业、权威的表达方式）',
            'warm'          => '温暖亲切（使用关怀、温暖的语言）',
            default         => $emotion . '风格',
        };

        $prompt = "请调整以下内容的情感倾向为{$emotionDesc}，保持核心信息不变：\n\n---\n原文：\n{$text}\n---\n\n请直接输出调整后的内容。";

        return $this->executeAi($prompt, $options, 'emotion_adjust');
    }

    /**
     * 获取支持的写作操作列表
     * @return array
     */
    public function getSupportedOperations(): array
    {
        return [
            ['key' => 'continueWriting', 'name' => '续写', 'desc' => '根据已有内容自动续写'],
            ['key' => 'rewrite', 'name' => '改写', 'desc' => '保持原意重新表达'],
            ['key' => 'expand', 'name' => '扩写', 'desc' => '增加细节丰富内容'],
            ['key' => 'summarize', 'name' => '摘要', 'desc' => '提取核心要点'],
            ['key' => 'polish', 'name' => '润色', 'desc' => '改善语言表达'],
            ['key' => 'proofread', 'name' => '校对', 'desc' => '检查错误并修正'],
            ['key' => 'styleConvert', 'name' => '风格转换', 'desc' => '转换写作风格'],
            ['key' => 'formatConvert', 'name' => '格式转换', 'desc' => '转换文本格式'],
            ['key' => 'emotionAdjust', 'name' => '情感调整', 'desc' => '调整情感倾向'],
        ];
    }

    /**
     * 获取支持的风格列表
     * @return array
     */
    public function getSupportedStyles(): array
    {
        return [
            ['key' => 'professional', 'name' => '专业风格'],
            ['key' => 'formal', 'name' => '正式风格'],
            ['key' => 'casual', 'name' => '休闲风格'],
            ['key' => 'academic', 'name' => '学术风格'],
            ['key' => 'journalistic', 'name' => '新闻风格'],
            ['key' => 'literary', 'name' => '文学风格'],
            ['key' => 'technical', 'name' => '技术风格'],
            ['key' => 'marketing', 'name' => '营销风格'],
        ];
    }

    /**
     * 获取支持的情感类型
     * @return array
     */
    public function getSupportedEmotions(): array
    {
        return [
            ['key' => 'positive', 'name' => '积极正面'],
            ['key' => 'negative', 'name' => '严肃审慎'],
            ['key' => 'neutral', 'name' => '中性客观'],
            ['key' => 'enthusiastic', 'name' => '热情洋溢'],
            ['key' => 'calm', 'name' => '平和冷静'],
            ['key' => 'professional', 'name' => '专业严谨'],
            ['key' => 'warm', 'name' => '温暖亲切'],
        ];
    }

    /**
     * 批量执行写作辅助操作
     * @param string $operation 操作类型
     * @param string $text 原文
     * @param array $params 参数
     * @return array
     */
    public function execute(string $operation, string $text, array $params = []): array
    {
        return match($operation) {
            'continueWriting' => $this->continueWriting($text, (int)($params['length'] ?? 500), $params),
            'rewrite'         => $this->rewrite($text, $params['style'] ?? 'professional', $params),
            'expand'          => $this->expand($text, (int)($params['targetLength'] ?? 1000), $params),
            'summarize'       => $this->summarize($text, (int)($params['maxLength'] ?? 200), $params),
            'polish'          => $this->polish($text, $params),
            'proofread'       => $this->proofread($text, $params),
            'styleConvert'    => $this->styleConvert($text, $params['targetStyle'] ?? 'formal', $params),
            'formatConvert'   => $this->formatConvert($text, $params['targetFormat'] ?? 'markdown', $params),
            'emotionAdjust'   => $this->emotionAdjust($text, $params['emotion'] ?? 'neutral', $params),
            default           => ['success' => false, 'message' => '未知操作: ' . $operation],
        };
    }

    /**
     * 执行AI调用（内部方法）
     * @param string $prompt 提示词
     * @param array $options 选项
     * @param string $operation 操作类型
     * @return array ['success' => bool, 'result' => string]
     */
    private function executeAi(string $prompt, array $options, string $operation): array
    {
        try {
            $provider = AiProviderFactory::getDefault();

            $result = $provider->write($prompt, [
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens'  => $options['max_tokens'] ?? 2000,
            ]);

            // 记录调用日志
            $this->logOperation($operation, $prompt, $result, $options);

            return [
                'success'   => true,
                'result'    => trim($result),
                'operation' => $operation,
            ];
        } catch (\Throwable $e) {
            Log::error('AiWritingAssistService executeAi failed: ' . $e->getMessage());
            return [
                'success'   => false,
                'message'   => 'AI服务异常: ' . $e->getMessage(),
                'operation' => $operation,
            ];
        }
    }

    /**
     * 记录操作日志
     * @param string $operation 操作类型
     * @param string $prompt 提示词
     * @param string $result 结果
     * @param array $options 选项
     */
    private function logOperation(string $operation, string $prompt, string $result, array $options): void
    {
        try {
            Db::name('ai_content_log')->insert([
                'operation'   => $operation,
                'prompt'      => mb_substr($prompt, 0, 500),
                'result'      => mb_substr($result, 0, 500),
                'tokens_used' => $this->estimateTokens($prompt . $result),
                'options'     => json_encode($options, JSON_UNESCAPED_UNICODE),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // 日志记录失败不影响主流程
        }
    }

    /**
     * 截断文本到最大长度
     * @param string $text 原文
     * @return string
     */
    private function truncateText(string $text): string
    {
        if (mb_strlen($text) > self::MAX_INPUT_LENGTH) {
            return mb_substr($text, 0, self::MAX_INPUT_LENGTH) . '...';
        }
        return $text;
    }

    /**
     * 长度转中文描述
     * @param int $length 长度
     * @return string
     */
    private function lengthToLabel(int $length): string
    {
        if ($length <= 200) return $length . '字';
        if ($length <= 500) return $length . '字';
        if ($length <= 1000) return $length . '字';
        return $length . '字';
    }

    /**
     * 获取风格描述
     * @param string $style 风格
     * @return string
     */
    private function getStyleDescription(string $style): string
    {
        return match($style) {
            'professional'   => '专业风格（术语准确，逻辑严密）',
            'formal'         => '正式风格（严谨规范，避免口语化）',
            'casual'         => '休闲风格（通俗易懂，轻松活泼）',
            'academic'       => '学术风格（引用规范，论证充分）',
            'journalistic'   => '新闻风格（客观简洁，5W1H）',
            'literary'       => '文学风格（优美典雅，富有文采）',
            'technical'      => '技术风格（精确简洁，结构清晰）',
            'marketing'      => '营销风格（吸引眼球，富有感染力）',
            default          => $style . '风格',
        };
    }

    /**
     * 估算Token数量
     * @param string $text 文本
     * @return int
     */
    private function estimateTokens(string $text): int
    {
        $chineseCount = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);
        $otherCount = strlen($text) - $chineseCount * 3;
        return (int) ceil($chineseCount / 2 + $otherCount / 4);
    }
}
