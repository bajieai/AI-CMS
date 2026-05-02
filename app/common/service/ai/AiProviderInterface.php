<?php
declare(strict_types=1);

namespace app\common\service\ai;

/**
 * AI Provider统一接口
 * 所有AI模型适配器必须实现此接口
 */
interface AiProviderInterface
{
    /**
     * AI写作/内容生成
     * @param string $prompt 用户提示词
     * @param array $options 可选参数（template/system_prompt/max_tokens/temperature等）
     * @return string AI生成的内容
     */
    public function write(string $prompt, array $options = []): string;

    /**
     * SEO优化
     * @param string $content 待优化内容
     * @param array $keywords 目标关键词
     * @return array ['optimized_content'=>'', 'seo_title'=>'', 'seo_keywords'=>'', 'seo_description'=>'']
     */
    public function seoOptimize(string $content, array $keywords = []): array;

    /**
     * 翻译
     * @param string $text 待翻译文本
     * @param string $from 源语言
     * @param string $to 目标语言
     * @return string 翻译结果
     */
    public function translate(string $text, string $from = 'zh', string $to = 'en'): string;

    /**
     * 摘要生成
     * @param string $text 待总结文本
     * @param int $maxLength 最大长度
     * @return string 摘要
     */
    public function summarize(string $text, int $maxLength = 200): string;

    /**
     * 获取当前模型信息
     * @return array ['provider'=>'', 'model_id'=>'', 'capabilities'=>[]]
     */
    public function getModelInfo(): array;
}
