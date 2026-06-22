<?php
declare(strict_types=1);

namespace app\common\service\ai;

/**
 * Markdown转换服务 — V2.9.28 A-3
 *
 * Markdown ↔ 富文本双向转换
 */
class AiMarkdownConverter
{
    /**
     * Markdown转HTML
     */
    public function markdownToHtml(string $markdown): string
    {
        // 标题
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $markdown);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

        // 加粗/斜体
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<!\*)\*(.+?)\*(?!\*)/s', '<em>$1</em>', $html);

        // 链接
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // 引用
        $html = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $html);

        // 代码块
        $html = preg_replace('/```(\w*)\n(.*?)```/s', '<pre><code class="$1">$2</code></pre>', $html);

        // 行内代码
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // 列表
        $html = preg_replace('/^[\-\*] (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $html);

        // 有序列表
        $html = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $html);

        // 段落
        $html = preg_replace('/\n\n/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';

        // 清理空段落
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);

        return $html;
    }

    /**
     * HTML转Markdown
     */
    public function htmlToMarkdown(string $html): string
    {
        // 标题
        $md = preg_replace('/<h1[^>]*>(.+?)<\/h1>/s', '# $1', $html);
        $md = preg_replace('/<h2[^>]*>(.+?)<\/h2>/s', '## $1', $md);
        $md = preg_replace('/<h3[^>]*>(.+?)<\/h3>/s', '### $1', $md);
        $md = preg_replace('/<h4[^>]*>(.+?)<\/h4>/s', '#### $1', $md);

        // 加粗/斜体
        $md = preg_replace('/<(strong|b)[^>]*>(.+?)<\/\1>/s', '**$2**', $md);
        $md = preg_replace('/<(em|i)[^>]*>(.+?)<\/\1>/s', '*$2*', $md);

        // 链接
        $md = preg_replace('/<a[^>]*href="([^"]*)"[^>]*>(.+?)<\/a>/s', '[$2]($1)', $md);

        // 引用
        $md = preg_replace('/<blockquote[^>]*>(.+?)<\/blockquote>/s', '> $1', $md);

        // 代码块
        $md = preg_replace('/<pre[^>]*><code[^>]*>(.+?)<\/code><\/pre>/s', '```\n$1\n```', $md);
        $md = preg_replace('/<code[^>]*>(.+?)<\/code>/s', '`$1`', $md);

        // 列表
        $md = preg_replace('/<li[^>]*>(.+?)<\/li>/s', '- $1', $md);
        $md = preg_replace('/<\/?[uo]l[^>]*>/', '', $md);

        // 段落
        $md = preg_replace('/<p[^>]*>(.+?)<\/p>/s', '$1\n\n', $md);
        $md = preg_replace('/<br\s*\/?>/', "\n", $md);

        // 清理剩余HTML标签
        $md = strip_tags($md);

        // 清理多余空行
        $md = preg_replace("/\n{3,}/", "\n\n", $md);

        return trim($md);
    }
}
