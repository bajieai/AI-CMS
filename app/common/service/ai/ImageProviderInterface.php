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
 * AI配图生成接口 - V2.8新增
 * 所有配图Provider必须实现此接口
 */
interface ImageProviderInterface
{
    /**
     * 生成图片
     * @param string $prompt 图片描述
     * @param array $options 可选参数
     *        - style: 风格 (realistic/illustration/watercolor/3d_render/pixel_art)
     *        - size: 尺寸 (1024x1024/1024x1792/1792x1024)
     *        - count: 生成数量 (1-5)
     *        - regenerate: 是否重新生成
     * @return array ['url'=>string, 'width'=>int, 'height'=>int, 'format'=>string, '_provider'=>string, '_request_id'=>string]
     */
    public function generateImage(string $prompt, array $options = []): array;

    /**
     * 获取Provider信息
     * @return array ['provider'=>string, 'model'=>string, 'max_resolution'=>string, 'supported_styles'=>array]
     */
    public function getImageInfo(): array;

    /**
     * 获取支持的风格列表
     * @return array ['realistic', 'illustration', 'watercolor', ...]
     */
    public function getSupportedStyles(): array;

    /**
     * V2.9.15: 查询异步任务状态（通义万相/Flux等异步Provider）
     *
     * @param string $taskId 任务ID
     * @return array ['success'=>bool, 'url'=>string, 'failed'=>bool, 'message'=>string, 'progress'=>int]
     *         success=true & url非空 → 任务完成
     *         failed=true → 任务失败
     *         success=true & url空 → 任务进行中，message可含进度信息
     */
    public function queryTaskStatus(string $taskId): array;
}
