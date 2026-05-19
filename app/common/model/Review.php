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
 * 审核记录模型
 */
class Review extends Model
{
    protected $name = 'review';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 获取操作文本
     */
    public function getActionTextAttr($value, $data): string
    {
        $map = ['approve' => '通过', 'reject' => '驳回'];
        return $map[$data['action']] ?? '未知';
    }

    /**
     * 关联内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * 关联审核人
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
