<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板自定义配置模型 - V2.9.12
 *
 * 存储用户对模板的样式自定义配置（颜色/字体/Logo/布局等）
 * 唯一索引：uk_member_slug_key (member_id + theme_slug + config_key)
 */
class TemplateCustomConfig extends Model
{
    protected $name = 'template_custom_config';
    protected $pk = 'id';

    protected $schema = [
        'id'          => 'int',
        'member_id'   => 'int',
        'theme_slug'  => 'string',
        'config_key'  => 'string',
        'config_value'=> 'text',
        'config_type' => 'string',
        'create_time' => 'int',
        'update_time' => 'int',
    ];

    protected $autoWriteTimestamp = true;

    /**
     * 获取某用户某主题的全部配置
     */
    public static function getThemeConfig(int $memberId, string $themeSlug): array
    {
        $rows = self::where('member_id', $memberId)
            ->where('theme_slug', $themeSlug)
            ->column('config_value', 'config_key');

        $config = [];
        foreach ($rows as $key => $value) {
            $decoded = json_decode($value, true);
            $config[$key] = $decoded !== null ? $decoded : $value;
        }
        return $config;
    }

    /**
     * 保存单个配置项
     */
    public static function setConfig(int $memberId, string $themeSlug, string $key, $value, string $type = 'style'): void
    {
        $jsonValue = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        $exists = self::where('member_id', $memberId)
            ->where('theme_slug', $themeSlug)
            ->where('config_key', $key)
            ->find();

        if ($exists) {
            $exists->config_value = $jsonValue;
            $exists->config_type = $type;
            $exists->save();
        } else {
            self::create([
                'member_id'    => $memberId,
                'theme_slug'   => $themeSlug,
                'config_key'   => $key,
                'config_value' => $jsonValue,
                'config_type'  => $type,
            ]);
        }
    }

    /**
     * 批量保存配置
     */
    public static function setConfigs(int $memberId, string $themeSlug, array $configs, string $type = 'style'): void
    {
        foreach ($configs as $key => $value) {
            self::setConfig($memberId, $themeSlug, $key, $value, $type);
        }
    }

    /**
     * 删除某主题的全部配置
     */
    public static function clearThemeConfig(int $memberId, string $themeSlug): int
    {
        return self::where('member_id', $memberId)
            ->where('theme_slug', $themeSlug)
            ->delete();
    }
}
