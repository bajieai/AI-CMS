<?php

declare(strict_types=1);

/**
 * 内容增强插件主类
 * 演示Filter钩子修改内容数据
 */
class ContentEnhancerPlugin
{
    /**
     * 内容保存前：自动提取摘要和关键词
     */
    public function beforeSave(array $data): array
    {
        $config = require __DIR__ . '/../config.php';
        $settings = $config['settings'] ?? [];

        // 自动生成摘要
        if (empty($data['summary']) && !empty($data['content'])) {
            $length = $settings['auto_summary_length'] ?? 200;
            $data['summary'] = mb_substr(strip_tags($data['content']), 0, $length);
        }

        // 自动提取关键词
        if (empty($data['keywords']) && !empty($data['content'])) {
            $count = $settings['auto_keywords_count'] ?? 10;
            $data['keywords'] = $this->extractKeywords($data['content'], $count);
        }

        // 添加阅读时间
        if ($settings['add_reading_time'] ?? true) {
            $wordCount = mb_strlen(strip_tags($data['content'] ?? ''));
            $data['reading_time'] = max(1, (int) ceil($wordCount / 300));
        }

        return $data;
    }

    /**
     * 内容显示后：添加阅读时间显示
     */
    public function afterDisplay(string $html): string
    {
        // 在内容前插入阅读时间提示
        $readingTime = '<div class="reading-time-hint">预计阅读时间：约%d分钟</div>';
        // 实际场景中从上下文获取阅读时间
        return $html;
    }

    /**
     * 简单关键词提取（基于词频）
     */
    protected function extractKeywords(string $content, int $count): string
    {
        $text = strip_tags($content);
        $words = preg_split('/[\s,，。.！!？?；;：:、]+/u', $text);
        $words = array_filter($words, fn($w) => mb_strlen($w) >= 2);

        $freq = array_count_values($words);
        arsort($freq);

        return implode(',', array_slice(array_keys($freq), 0, $count));
    }
}
