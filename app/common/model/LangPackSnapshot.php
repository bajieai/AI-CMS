<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 语言包版本快照模型
 * V2.9.37 I18N-2 (P0-2修复)
 */
class LangPackSnapshot extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $updateTime = false;

    protected $json = ['snapshot_data'];
    protected $jsonAssoc = true;

    protected $type = [
        'version'          => 'integer',
        'entry_count'      => 'integer',
        'translated_count' => 'integer',
        'completion_rate'  => 'float',
        'created_by'       => 'integer',
    ];
}
