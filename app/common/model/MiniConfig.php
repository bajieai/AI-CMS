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
 * 小程序配置模型 (i8j_mini_config)
 */
class MiniConfig extends Model
{
    protected $name = 'mini_config';

    protected $autoWriteTimestamp = false;

    /**
     * 获取配置值
     */
    public static function getValue(string $key, mixed $default = ''): mixed
    {
        $item = self::where('config_key', $key)->find();
        return $item ? $item->config_value : $default;
    }

    /**
     * 获取分组配置
     */
    public static function getGroup(string $group): array
    {
        return self::where('config_group', $group)->select()->toArray();
    }

    /**
     * 获取全部配置(key=>value)
     */
    public static function getAll(): array
    {
        $list = self::select()->toArray();
        $result = [];
        foreach ($list as $item) {
            $result[$item['config_key']] = $item['config_value'];
        }
        return $result;
    }
}
