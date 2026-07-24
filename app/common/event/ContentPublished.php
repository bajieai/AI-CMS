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

/**
 * 内容发布事件 - V2.9.18 D-1
 * 
 * 触发条件：ContentController 发布/审核通过内容后
 */
class ContentPublished
{
    /** 内容ID */
    public int $contentId;
    /** 是否手动触发推送 */
    public bool $isManual;

    public function __construct(int $contentId, bool $isManual = false)
    {
        $this->contentId = $contentId;
        $this->isManual  = $isManual;
    }
}
