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

namespace app\common\event;

use app\common\model\TemplateStore;

/**
 * AI主题生成完成事件 - V2.9.12新增
 */
class AiThemeGenerated
{
    public TemplateStore $store;

    public function __construct(TemplateStore $store)
    {
        $this->store = $store;
    }
}
