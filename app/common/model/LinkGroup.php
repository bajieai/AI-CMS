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
 * 友情链接分组模型
 */
class LinkGroup extends Model
{
    protected $name = 'link_group';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
    ];

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status'] ? '启用' : '禁用';
    }

    public function links()
    {
        return $this->hasMany(Link::class, 'group_id');
    }
}