<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * AI编辑器模板库模型 — V2.9.28 A-5
 */
class AiEditorTemplate extends Model
{
    protected $name = 'ai_editor_template';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'is_system' => 'integer',
        'user_id' => 'integer',
        'use_count' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 增加使用次数
     */
    public static function incrementUseCount(int $id): void
    {
        self::where('id', $id)->inc('use_count')->update();
    }
}
