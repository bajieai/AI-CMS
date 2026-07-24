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
 * 内容版本历史模型
 */
class ContentVersion extends Model
{
    protected $name = 'content_version';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'cate_id' => 'integer',
        'status' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * 获取操作人名称
     */
    public function getUserNameAttr($value, $data): string
    {
        if (empty($data['user_id'])) {
            return '系统';
        }
        $user = User::find($data['user_id']);
        return $user ? ($user->nickname ?: $user->username) : '未知';
    }
}
