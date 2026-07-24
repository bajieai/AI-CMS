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

namespace app\admin\model;

use think\Model;

/**
 * 数据大屏配置模型 - V2.9.39 DATA-DEEP-1
 * 对应表: i8j_data_dashboard
 */
class DataDashboard extends Model
{
    protected $name = 'data_dashboard';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'id'               => 'integer',
        'refresh_interval' => 'integer',
        'is_active'        => 'integer',
        'is_public'        => 'integer',
        'layout'           => 'json',
    ];

    public const LAYOUT_DEFAULT   = 'default';
    public const LAYOUT_MONITOR   = 'monitor';
    public const LAYOUT_EXECUTIVE = 'executive';

    protected static array $layoutMap = [
        self::LAYOUT_DEFAULT   => '默认布局',
        self::LAYOUT_MONITOR   => '监控大屏',
        self::LAYOUT_EXECUTIVE => '高管看板',
    ];

    /**
     * 布局文本
     */
    public function getLayoutTextAttr($value, array $data): string
    {
        return self::$layoutMap[$data['layout_template'] ?? ''] ?? '自定义';
    }

    /**
     * 获取激活的大屏
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', 1)->order('id', 'asc')->find();
    }

    /**
     * 通过分享Token获取
     */
    public static function getByShareToken(string $token): ?self
    {
        if (empty($token)) {
            return null;
        }
        return self::where('share_token', $token)
            ->where('is_public', 1)
            ->find();
    }

    /**
     * 生成分享Token
     */
    public static function generateShareToken(): string
    {
        return bin2hex(random_bytes(16)) . dechex(time());
    }
}
