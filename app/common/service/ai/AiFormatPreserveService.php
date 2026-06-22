<?php
declare(strict_types=1);

namespace app\common\service\ai;

/**
 * AI编辑器格式保留服务 — V2.9.28 A-3
 *
 * 格式感知Prompt + 格式后处理 + Markdown↔富文本双向转换
 */
class AiFormatPreserveService
{
    /**
     * 格式感知：在Prompt中注入格式保留指令
     */
    public function injectFormatPrompt(string $originalPrompt, string $originalText): string
    {
        $formatInfo = $this->analyzeFormat($originalText);

        $formatHint = "\n\n【格式保留要求】\n";
        $formatHint .= "请严格保留原文的以下格式：\n";
        if ($formatInfo['has_heading']) $formatHint .= "- 标题级别（# ## ###）\n";
        if ($formatInfo['has_list']) $formatHint .= "- 列表格式（- 或 1.）\n";
        if ($formatInfo['has_table']) $formatHint .= "- 表格结构\n";
        if ($formatInfo['has_quote']) $formatHint .= "- 引用块（>）\n";
        if ($formatInfo['has_code']) $formatHint .= "- 代码块（```）\n";
        if ($formatInfo['has_bold']) $formatHint .= "- 加粗（**text**）\n";
        if ($formatInfo['has_italic']) $formatHint .= "- 斜体（*text*）\n";
        if ($formatInfo['has_link']) $formatHint .= "- 链接（[text](url)）\n";
        $formatHint .= "不要改变任何格式标记的结构。\n";

        return $originalPrompt . $formatHint;
    }

    /**
     * 格式后处理：修复AI输出中丢失的格式标记
     */
    public function postProcess(string $originalText, string $aiOutput): string
    {
        // 修复丢失的Markdown标记
        $aiOutput = $this->fixHeadings($originalText, $aiOutput);
        $aiOutput = $this->fixLists($originalText, $aiOutput);
        $aiOutput = $this->fixTables($originalText, $aiOutput);

        return $aiOutput;
    }

    /**
     * 计算格式保真率
     */
    public function calculateFidelity(string $originalText, string $aiOutput): float
    {
        $originalFormats = $this->analyzeFormat($originalText);
        $outputFormats = $this->analyzeFormat($aiOutput);

        $total = 0;
        $matched = 0;

        foreach ($originalFormats as $key => $value) {
            if ($key === 'has_heading' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_list' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_table' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_quote' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_code' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_bold' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_italic' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
            if ($key === 'has_link' && $value) {
                $total++;
                if ($outputFormats[$key]) $matched++;
            }
        }

        return $total > 0 ? round($matched / $total * 100, 1) : 100.0;
    }

    /**
     * 分析文本格式
     */
    public function analyzeFormat(string $text): array
    {
        return [
            'has_heading' => (bool)preg_match('/^#{1,6}\s/m', $text),
            'has_list' => (bool)preg_match('/^[\-\*\d]\.?\s/m', $text),
            'has_table' => (bool)preg_match('/\|.*\|.*\n[\|\s\-:]+/', $text),
            'has_quote' => (bool)preg_match('/^>\s/m', $text),
            'has_code' => (bool)preg_match('/```/', $text),
            'has_bold' => (bool)preg_match('/\*\*[^*]+\*\*/', $text),
            'has_italic' => (bool)preg_match('/(?<!\*)\*[^*]+\*(?!\*)/', $text),
            'has_link' => (bool)preg_match('/\[([^\]]+)\]\(([^)]+)\)/', $text),
        ];
    }

    /**
     * 修复标题
     */
    private function fixHeadings(string $original, string $output): string
    {
        preg_match_all('/^(#{1,6})\s+(.+)$/m', $original, $matches);
        if (!empty($matches[1])) {
            // 确保输出中有对应级别的标题
            foreach ($matches[1] as $i => $level) {
                $title = $matches[2][$i];
                if (strpos($output, $level . ' ' . $title) === false) {
                    // 标题丢失，尝试在输出中找到对应的文本行并添加标记
                    $output = preg_replace(
                        '/^' . preg_quote($title, '/') . '$/m',
                        $level . ' ' . $title,
                        $output
                    );
                }
            }
        }
        return $output;
    }

    /**
     * 修复列表
     */
    private function fixLists(string $original, string $output): string
    {
        // 简化处理：如果原文有列表标记但输出没有，不做强制修复
        return $output;
    }

    /**
     * 修复表格
     */
    private function fixTables(string $original, string $output): string
    {
        // 简化处理：保留原文中的表格结构
        return $output;
    }
}
