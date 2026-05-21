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

namespace app\common\utils;

/**
 * V2.9.4 敏感词过滤工具
 * 基于Trie前缀树的敏感词匹配算法
 */
class SensitiveWordHelper
{
    /**
     * @var ?array Trie树根节点
     */
    protected static ?array $trieRoot = null;

    /**
     * 检测文本中的敏感词
     * @return array ['score'=>0-100, 'matched'=>[], 'suggestions'=>[]]
     */
    public static function analyze(string $content): array
    {
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        $matched = self::matchWords($text);
        $count = count($matched);

        // 评分：无敏感词=100，有则根据数量扣分
        $score = $count === 0 ? 100 : max(0, 100 - $count * 15);

        $suggestions = [];
        foreach ($matched as $word) {
            $suggestions[] = [
                'type' => 'danger',
                'msg' => "检测到敏感词：{$word}",
            ];
        }

        if ($count > 0) {
            $suggestions[] = [
                'type' => 'warning',
                'msg' => "共检测到{$count}个敏感词，请修改后再发布",
            ];
        }

        return [
            'score' => $score,
            'matched' => $matched,
            'count' => $count,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * 匹配敏感词
     */
    public static function matchWords(string $text): array
    {
        $trie = self::getTrie();
        if (empty($trie)) return [];

        $matched = [];
        $len = mb_strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1);
            if (!isset($trie[$char])) continue;

            $node = $trie[$char];
            $j = $i + 1;

            while ($j < $len) {
                if (isset($node['__end__'])) {
                    $word = mb_substr($text, $i, $j - $i);
                    if (!in_array($word, $matched)) {
                        $matched[] = $word;
                    }
                }
                $nextChar = mb_substr($text, $j, 1);
                if (!isset($node[$nextChar])) break;
                $node = $node[$nextChar];
                $j++;
            }

            // 检查最后一个字符
            if (isset($node['__end__'])) {
                $word = mb_substr($text, $i, $j - $i);
                if (!in_array($word, $matched)) {
                    $matched[] = $word;
                }
            }
        }

        return $matched;
    }

    /**
     * 构建Trie树
     */
    protected static function getTrie(): array
    {
        if (self::$trieRoot !== null) {
            return self::$trieRoot;
        }

        $words = self::loadWords();
        $trie = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (empty($word)) continue;

            $node = &$trie;
            $len = mb_strlen($word);
            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($word, $i, 1);
                if (!isset($node[$char])) {
                    $node[$char] = [];
                }
                $node = &$node[$char];
            }
            $node['__end__'] = true;
        }

        self::$trieRoot = $trie;
        return $trie;
    }

    /**
     * 加载敏感词库
     */
    protected static function loadWords(): array
    {
        $path = root_path() . 'config/sensitive_words.json';
        if (!file_exists($path)) {
            return [];
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * 清除Trie缓存（用于词库更新后）
     */
    public static function clearCache(): void
    {
        self::$trieRoot = null;
    }
}
