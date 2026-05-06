<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 会员等级模型
 */
class MemberLevel extends Model
{
    protected $name = 'member_level';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'min_points'  => 'integer',
        'discount'    => 'float',
        'price'       => 'float',
        'points_rate' => 'float',
        'daily_ai_quota' => 'integer',
        'allow_download' => 'integer',
        'allow_comment_no_review' => 'integer',
        'is_default'  => 'integer',
        'sort'        => 'integer',
    ];
}
