<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 广告位模型
 */
class AdPosition extends Model
{
    protected $name = 'ad_position';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'width' => 'integer',
        'height' => 'integer',
        'status' => 'integer',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status'] ? '启用' : '禁用';
    }

    public function ads()
    {
        return $this->hasMany(Ad::class, 'position_id');
    }
}