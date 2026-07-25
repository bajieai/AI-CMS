<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

class AiTranslationCache extends Model
{
    protected $name = 'ai_translation_cache';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    /**
     * 查找翻译记忆
     */
    public static function findTranslation(string $sourceText, string $sourceLang, string $targetLang): ?array
    {
        $hash = hash('sha256', $sourceText);
        $record = self::where('source_text_hash', $hash)
            ->where('source_lang', $sourceLang)
            ->where('target_lang', $targetLang)
            ->find();
        if ($record) {
            $record->hit_count++;
            $record->save();
            return $record->toArray();
        }
        return null;
    }

    /**
     * 保存翻译记忆
     */
    public static function saveTranslation(
        string $sourceText, string $sourceLang, string $targetLang,
        string $translatedText, string $provider, float $qualityScore = 0
    ): void {
        $hash = hash('sha256', $sourceText);
        self::create([
            'source_text_hash' => $hash,
            'source_text'      => $sourceText,
            'source_lang'      => $sourceLang,
            'target_lang'      => $targetLang,
            'translated_text'  => $translatedText,
            'provider'         => $provider,
            'quality_score'    => $qualityScore,
            'hit_count'        => 1,
        ]);
    }
}
