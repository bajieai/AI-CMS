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

namespace app\common\service\push;

/**
 * 推送通道接口 - V2.9.18 D-1
 * 
 * 所有推送通道必须实现此接口
 */
interface PushChannelInterface
{
    /**
     * 执行推送
     *
     * @param array $payload 推送数据
     * @param array $config  通道配置
     * @return array ['success' => bool, 'response_code' => int, 'response_body' => string, 'duration_ms' => int, 'error_msg' => string]
     */
    public function push(array $payload, array $config): array;
}
