<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\provider\AiProviderFactory;

/**
 * AI错别字/语病检测服务 — V2.9.28 A-1
 */
class AiProofreadService
{
    private AiProviderFactory $factory;

    public function __construct()
    {
        $this->factory = new AiProviderFactory();
    }

    /**
     * 错别字修正
     */
    public function proofread(string $text): array
    {
        $systemPrompt = "你是一个专业的中文文字校对专家。请检测并修正以下文本中的错别字和语病。"
            . "只返回修正后的文本，不要添加任何解释。保持原文的格式和标点。";

        $result = $this->callAi($systemPrompt, $text);
        return ['text' => $result, 'elapsed_ms' => 0];
    }

    /**
     * 句式优化
     */
    public function optimizeSentence(string $text): array
    {
        $systemPrompt = "你是一个专业的中文写作编辑。请优化以下文本的句式："
            . "1.长句拆分为短句 2.被动语态转主动 3.删除冗余表达 4.保持原意不变。"
            . "只返回优化后的文本，保持原文的格式标记。";

        $result = $this->callAi($systemPrompt, $text);
        return ['text' => $result, 'elapsed_ms' => 0];
    }

    /**
     * 语气切换
     */
    public function switchTone(string $text, string $tone): array
    {
        $toneMap = [
            'formal' => '正式',
            'casual' => '轻松',
            'professional' => '专业',
            'friendly' => '友好',
        ];
        $toneLabel = $toneMap[$tone] ?? '正式';

        $systemPrompt = "请将以下文本的语气调整为{$toneLabel}风格。"
            . "保持内容信息不变，只调整表达语气和措辞。只返回调整后的文本。";

        $result = $this->callAi($systemPrompt, $text);
        return ['text' => $result, 'elapsed_ms' => 0, 'tone' => $tone];
    }

    /**
     * 段落级综合优化
     */
    public function optimizeParagraph(string $text, string $mode = 'all'): array
    {
        $prompts = [
            'proofread' => "请检测并修正以下文本中的错别字和语病，只返回修正后的文本。",
            'optimize' => "请优化以下文本的句式，使表达更流畅简洁，保持原意不变，只返回优化后的文本。",
            'all' => "请对以下文本进行综合优化：修正错别字、优化句式、提升表达质量。保持原意和格式不变，只返回优化后的文本。",
        ];

        $systemPrompt = $prompts[$mode] ?? $prompts['all'];
        $result = $this->callAi($systemPrompt, $text);
        return ['text' => $result, 'elapsed_ms' => 0, 'mode' => $mode];
    }

    /**
     * 调用AI
     */
    private function callAi(string $systemPrompt, string $userText): string
    {
        try {
            $provider = $this->factory->getDefault();
            $response = $provider->chat([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userText],
            ], [
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);
            return $response['content'] ?? $userText;
        } catch (\Throwable $e) {
            return $userText;
        }
    }
}
