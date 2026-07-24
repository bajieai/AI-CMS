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

namespace app\home\service;

use think\facade\Cache;

/**
 * 移动端骨架屏服务 (V2.9.29 F-5)
 */
class MobileSkeletonService
{
    /**
     * 获取骨架屏HTML
     */
    public function renderListSkeleton(int $count = 5): string
    {
        $html = '<div class="skeleton-list">';
        for ($i = 0; $i < $count; $i++) {
            $html .= '<div class="skeleton-item">
                <div class="skeleton-img skeleton-pulse"></div>
                <div class="skeleton-lines">
                    <div class="skeleton-line skeleton-pulse" style="width:80%"></div>
                    <div class="skeleton-line skeleton-pulse" style="width:60%"></div>
                    <div class="skeleton-line skeleton-pulse" style="width:40%"></div>
                </div>
            </div>';
        }
        $html .= '</div>';
        return $html;
    }
}
