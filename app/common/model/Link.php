<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 友情链接模型
 */
class Link extends Model
{
    protected $name = 'link';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'sort' => 'integer',
        'status' => 'integer',
        'group_id' => 'integer',
        'is_apply' => 'integer',
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [0 => '禁用', 1 => '启用'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 关联分组
     */
    public function group()
    {
        return $this->belongsTo(LinkGroup::class, 'group_id');
    }
}
