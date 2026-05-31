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

namespace app\common\listener;

use app\common\event\AiThemeGenerated;
use app\common\service\theme\ThemeRepairPipeline;
use think\facade\Log;

/**
 * 主题质量校验监听器 - V2.9.12新增
 *
 * 监听 AiThemeGenerated 事件，自动触发质量校验管线
 */
class ThemeQualityCheckListener
{
    /**
     * 事件处理
     */
    public function handle(AiThemeGenerated $event): void
    {
        $store = $event->store;

        try {
            $pipeline = new ThemeRepairPipeline();
            $result = $pipeline->validate($store);

            Log::info('[ThemeQualityCheck] 自动校验完成: store_id=' . $store->id . ', score=' . $result['quality_score'] . ', pass=' . ($result['pass'] ? 'Y' : 'N'));
        } catch (\Throwable $e) {
            Log::error('[ThemeQualityCheck] 自动校验异常: ' . $e->getMessage());
        }
    }
}
