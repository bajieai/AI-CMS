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
 * 模板配色变体模型 - V2.9.12新增
 */
class TemplateColorVariant extends Model
{
    protected $name = 'template_color_variant';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'store_id' => 'integer',
        'is_default' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 关联商店模板
     */
    public function store()
    {
        return $this->belongsTo(TemplateStore::class, 'store_id');
    }
}
