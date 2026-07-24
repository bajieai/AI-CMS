<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 语言包条目模型
 * V2.9.37 I18N-2
 */
class LangPack extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    protected $type = [
        'is_translated' => 'integer',
        'is_using_ai'   => 'integer',
        'is_system'     => 'integer',
        'sort_order'    => 'integer',
        'version'       => 'integer',
    ];

    public function scopeLang($query, string $langCode)
    {
        return $query->where('lang_code', $langCode);
    }

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group_name', $group);
    }

    public function scopeUntranslated($query)
    {
        return $query->where('is_translated', 0);
    }
}
