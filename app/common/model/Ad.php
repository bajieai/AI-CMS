<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 广告模型
 */
class Ad extends Model
{
    protected $name = 'ad';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'position_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status'] ? '启用' : '禁用';
    }

    public function position()
    {
        return $this->belongsTo(AdPosition::class, 'position_id');
    }

    public function stats()
    {
        return $this->hasMany(AdStat::class, 'ad_id');
    }
}