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

namespace app\common\service\ai;

/**
 * capabilities 归一化 Trait
 * 兼容数据库 JSON 数组（已自动解码为 PHP array）和字符串格式
 */
trait CapabilityTrait
{
    /**
     * 将 capabilities 归一化为字符串数组
     * @param array $defaults 默认值
     * @return array
     */
    protected function normalizeCapabilities(array $defaults = ['write', 'seo']): array
    {
        $cap = $this->model->capabilities ?? null;

        if (is_array($cap)) {
            return $cap;
        }

        if (is_string($cap) && !empty(trim($cap))) {
            return array_map('trim', explode(',', $cap));
        }

        return $defaults;
    }
}
