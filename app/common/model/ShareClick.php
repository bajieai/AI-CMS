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
 * 分享点击追踪模型 - V2.9.19 R-1
 *
 * 映射 i8j_share_click 表，实时记录分享点击数据
 */
class ShareClick extends Model
{
    protected $name = 'share_click';
    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'content_id' => 'integer',
    ];

    /**
     * 按内容ID统计各来源点击数
     */
    public static function statByContent(int $contentId): array
    {
        return self::where('content_id', $contentId)
            ->group('source')
            ->column('count(*) as count', 'source');
    }

    /**
     * 按内容ID统计总点击数
     */
    public static function totalByContent(int $contentId): int
    {
        return (int) self::where('content_id', $contentId)->count();
    }
}
