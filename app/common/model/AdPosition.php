<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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