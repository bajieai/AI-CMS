<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 广告统计模型
 */
class AdStat extends Model
{
    protected $name = 'ad_stat';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'ad_id' => 'integer',
        'views' => 'integer',
        'clicks' => 'integer',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}