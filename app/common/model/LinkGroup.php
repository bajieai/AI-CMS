<?php
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