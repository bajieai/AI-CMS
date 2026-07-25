<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiContentLog;

/**
 * AI内容编辑增强服务 — V2.9.26 R-1
 *
 * 支持模式：continue(续写) / rewrite(改写) / expand(扩写) / summarize(摘要)
 * 支持风格：formal(正式) / casual(随性) / professional(专业) / academic(学术) / colloquial(口语化)
 */
class AiContentEnhanceService
{
    protected AiProviderFactory $providerFactory;

    public function __construct()
    {
        $this->providerFactory = new AiProviderFactory();
    }

    /**
     * AI续写
     */
    public function continueWriting(string $text, string $style = 'formal', int $userId = 0, int $contentId = 0): array
    {
        return $this->execute('continue', $style, $text, $userId, $contentId);
    }

    /**
     * AI改写
     */
    public function rewrite(string $text, string $style = 'professional', int $userId = 0, int $contentId = 0): array
    {
        return $this->execute('rewrite', $style, $text, $userId, $contentId);
    }

    /**
     * AI扩写
     */
    public function expand(string $text, string $style = 'academic', int $userId = 0, int $contentId = 0): array
    {
        return $this->execute('expand', $style, $text, $userId, $contentId);
    }

    /**
     * AI摘要
     */
    public function summarize(string $text, string $style = 'formal', int $userId = 0, int $contentId = 0): array
    {
        return $this->execute('summarize', $style, $text, $userId, $contentId);
    }

    /**
     * 执行AI编辑
     */
    protected function execute(string $mode, string $style, string $inputText, int $userId, int $contentId): array
    {
        $startTime = microtime(true);
        $prompt = $this->buildPrompt($mode, $style, $inputText);

        try {
            $provider = $this->providerFactory->getDefaultProvider();
            $result = $provider->chat([
                ['role' => 'system', 'content' => $this->getSystemPrompt($mode, $style)],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $outputText = $result['content'] ?? '';
            $elapsed = (int)((microtime(true) - $startTime) * 1000);

            // 记录日志
            AiContentLog::log(
                $userId, $contentId, $mode, $style,
                $inputText, $outputText,
                $result['provider'] ?? 'unknown',
                $result['tokens'] ?? 0, $elapsed
            );

            return ['success' => true, 'text' => $outputText, 'elapsed_ms' => $elapsed];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function getSystemPrompt(string $mode, string $style): string
    {
        $styleMap = [
            'formal'       => '正式严谨',
            'casual'       => '轻松随性',
            'professional' => '专业规范',
            'academic'     => '学术严谨',
            'colloquial'   => '口语化',
        ];
        $styleDesc = $styleMap[$style] ?? '正式';
        $modeDesc = [
            'continue'  => '续写',
            'rewrite'   => '改写',
            'expand'    => '扩写',
            'summarize' => '摘要',
        ];
        return "你是一个专业的内容编辑助手。请以{$styleDesc}的风格进行{$modeDesc[$mode]}。保持内容通顺、逻辑清晰。";
    }

    protected function buildPrompt(string $mode, string $style, string $text): string
    {
        $instructions = [
            'continue'  => '请续写以下内容，保持风格一致：',
            'rewrite'   => '请改写以下内容，使其更加精炼：',
            'expand'    => '请扩写以下内容，增加更多细节和论据：',
            'summarize' => '请提取以下内容的核心要点，生成摘要：',
        ];
        return ($instructions[$mode] ?? '') . "\n\n" . $text;
    }
}
