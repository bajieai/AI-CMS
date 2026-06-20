<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

class AiTranslationGlossary extends Model
{
    protected $name = 'ai_translation_glossary';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public const CACHE_TAG = 'ai_glossary';

    /**
     * 获取术语对（用于翻译预处理）
     */
    public static function getGlossary(string $sourceLang, string $targetLang): array
    {
        $cacheKey = 'glossary_' . $sourceLang . '_' . $targetLang;
        return Cache::tag(self::CACHE_TAG)->remember($cacheKey, function () use ($sourceLang, $targetLang) {
            return self::where('source_lang', $sourceLang)
                ->where('target_lang', $targetLang)
                ->where('status', 1)
                ->select()
                ->toArray();
        }, 3600);
    }

    /**
     * 预处理：将原文中的术语替换为占位符
     */
    public static function preprocessText(string $text, string $sourceLang, string $targetLang): array
    {
        $glossary = self::getGlossary($sourceLang, $targetLang);
        $placeholders = [];
        $processed = $text;
        foreach ($glossary as $i => $item) {
            $placeholder = "[G{$i}]";
            if (mb_strpos($processed, $item['source_term']) !== false) {
                $processed = str_replace($item['source_term'], $placeholder, $processed);
                $placeholders[$placeholder] = $item['target_term'];
            }
        }
        return ['processed_text' => $processed, 'placeholders' => $placeholders];
    }

    /**
     * 后处理：将占位符替换回目标术语
     */
    public static function postprocessText(string $text, array $placeholders): string
    {
        foreach ($placeholders as $placeholder => $target) {
            $text = str_replace($placeholder, $target, $text);
        }
        return $text;
    }
}
