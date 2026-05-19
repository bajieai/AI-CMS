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
 * 语言模型 - V2.5新增
 */
class Language extends Model
{
    protected $name = 'language';
    protected $autoWriteTimestamp = false;

    protected $type = [
        'is_default' => 'integer',
        'is_enabled' => 'integer',
        'sort' => 'integer',
    ];
}
