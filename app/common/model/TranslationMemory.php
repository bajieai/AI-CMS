<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 翻译记忆模型
 * V2.9.37 I18N-5
 */
class TranslationMemory extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    protected $type = [
        'quality_score' => 'float',
        'use_count'     => 'integer',
        'is_confirmed'  => 'integer',
    ];

    public function scopeLangPair($query, string $srcLang, string $tgtLang)
    {
        return $query->where('source_lang', $srcLang)->where('target_lang', $tgtLang);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', 1);
    }
}
