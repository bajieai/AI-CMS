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

namespace app\common\hook;

/**
 * 系统级 Hook 事件常量定义 — V2.9.25 K-3/M-1
 *
 * 命名规范：
 * - 常量名：{MODULE}_{ACTION}，全大写下划线分割
 * - 事件标识：{module}.{action}，全小写点号分割
 *
 * 事件分类（共 29 个）：
 * - 内容模块（9个）
 * - 用户模块（3个）
 * - 模板模块（5个）
 * - 缓存模块（3个）
 * - 插件模块（7个）
 * - 系统配置模块（2个）
 */
class HookEvents
{
    // ─── 内容模块（9个） ───
    const CONTENT_BEFORE_SAVE      = 'content.before_save';
    const CONTENT_AFTER_SAVE       = 'content.after_save';
    const CONTENT_BEFORE_DELETE    = 'content.before_delete';
    const CONTENT_AFTER_DELETE     = 'content.after_delete';
    const CONTENT_AFTER_VIEW       = 'content.after_view';
    const CONTENT_BEFORE_PUBLISH   = 'content.before_publish';
    const CONTENT_AFTER_PUBLISH    = 'content.after_publish';
    const CONTENT_BEFORE_UNPUBLISH = 'content.before_unpublish';
    const CONTENT_AFTER_UNPUBLISH  = 'content.after_unpublish';

    // ─── 用户模块（3个） ───
    const USER_AFTER_LOGIN    = 'user.after_login';
    const USER_AFTER_REGISTER = 'user.after_register';
    const USER_BEFORE_LOGOUT  = 'user.before_logout';

    // ─── 模板模块（5个） ───
    const TEMPLATE_BEFORE_RENDER    = 'template.before_render';
    const TEMPLATE_AFTER_RENDER     = 'template.after_render';
    const TEMPLATE_BEFORE_INSTALL   = 'template.before_install';
    const TEMPLATE_AFTER_INSTALL    = 'template.after_install';
    const TEMPLATE_AFTER_UNINSTALL  = 'template.after_uninstall';

    // ─── 缓存模块（3个） ───
    const CACHE_AFTER_CLEAR = 'cache.after_clear';
    const CACHE_AFTER_HIT   = 'cache.after_hit';
    const CACHE_AFTER_MISS  = 'cache.after_miss';

    // ─── 插件模块（7个） ───
    const PLUGIN_BEFORE_ENABLE    = 'plugin.before_enable';
    const PLUGIN_AFTER_ENABLE     = 'plugin.after_enable';
    const PLUGIN_BEFORE_DISABLE   = 'plugin.before_disable';
    const PLUGIN_AFTER_DISABLE    = 'plugin.after_disable';
    const PLUGIN_BEFORE_UNINSTALL = 'plugin.before_uninstall';
    const PLUGIN_AFTER_UNINSTALL  = 'plugin.after_uninstall';
    const PLUGIN_AFTER_DOWNLOAD   = 'plugin.after_download';

    // ─── 系统配置模块（2个） ───
    const SYSTEM_CONFIG_BEFORE_UPDATE = 'system.config.before_update';
    const SYSTEM_CONFIG_AFTER_UPDATE  = 'system.config.after_update';

    /**
     * 获取所有事件常量
     * @return array<string, string> [常量名 => 事件标识]
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }

    /**
     * 按模块分组获取事件列表
     * @return array<string, array<string>> [模块 => [事件标识, ...]]
     */
    public static function getByModule(string $module = ''): array
    {
        $groups = [
            'content' => [
                self::CONTENT_BEFORE_SAVE,
                self::CONTENT_AFTER_SAVE,
                self::CONTENT_BEFORE_DELETE,
                self::CONTENT_AFTER_DELETE,
                self::CONTENT_AFTER_VIEW,
                self::CONTENT_BEFORE_PUBLISH,
                self::CONTENT_AFTER_PUBLISH,
                self::CONTENT_BEFORE_UNPUBLISH,
                self::CONTENT_AFTER_UNPUBLISH,
            ],
            'user' => [
                self::USER_AFTER_LOGIN,
                self::USER_AFTER_REGISTER,
                self::USER_BEFORE_LOGOUT,
            ],
            'template' => [
                self::TEMPLATE_BEFORE_RENDER,
                self::TEMPLATE_AFTER_RENDER,
                self::TEMPLATE_BEFORE_INSTALL,
                self::TEMPLATE_AFTER_INSTALL,
                self::TEMPLATE_AFTER_UNINSTALL,
            ],
            'cache' => [
                self::CACHE_AFTER_CLEAR,
                self::CACHE_AFTER_HIT,
                self::CACHE_AFTER_MISS,
            ],
            'plugin' => [
                self::PLUGIN_BEFORE_ENABLE,
                self::PLUGIN_AFTER_ENABLE,
                self::PLUGIN_BEFORE_DISABLE,
                self::PLUGIN_AFTER_DISABLE,
                self::PLUGIN_BEFORE_UNINSTALL,
                self::PLUGIN_AFTER_UNINSTALL,
                self::PLUGIN_AFTER_DOWNLOAD,
            ],
            'system' => [
                self::SYSTEM_CONFIG_BEFORE_UPDATE,
                self::SYSTEM_CONFIG_AFTER_UPDATE,
            ],
        ];

        if ($module !== '') {
            return $groups[$module] ?? [];
        }
        return $groups;
    }

    /**
     * 获取事件元数据（参数 Schema、描述、引入版本）
     * @return array<string, array>
     */
    public static function getMeta(): array
    {
        return [
            // ─── 内容模块 ───
            self::CONTENT_BEFORE_SAVE => [
                'description' => '内容创建/更新前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'content_data' => ['type' => 'array', 'required' => true, 'description' => '内容数据'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_AFTER_SAVE => [
                'description' => '内容创建/更新后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'content_data' => ['type' => 'array', 'required' => true, 'description' => '内容数据'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_BEFORE_DELETE => [
                'description' => '内容删除前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_AFTER_DELETE => [
                'description' => '内容删除后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_AFTER_VIEW => [
                'description' => '内容被查看后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'viewer_ip' => ['type' => 'string', 'required' => false, 'description' => '查看者IP'],
                ],
            ],
            self::CONTENT_BEFORE_PUBLISH => [
                'description' => '内容发布前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_AFTER_PUBLISH => [
                'description' => '内容发布后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_BEFORE_UNPUBLISH => [
                'description' => '内容下架前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::CONTENT_AFTER_UNPUBLISH => [
                'description' => '内容下架后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'content_id' => ['type' => 'int', 'required' => true, 'description' => '内容ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],

            // ─── 用户模块 ───
            self::USER_AFTER_LOGIN => [
                'description' => '用户登录成功后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '用户ID'],
                    'login_time' => ['type' => 'string', 'required' => true, 'description' => '登录时间'],
                    'ip' => ['type' => 'string', 'required' => false, 'description' => '登录IP'],
                ],
            ],
            self::USER_AFTER_REGISTER => [
                'description' => '用户注册成功后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'user_data' => ['type' => 'array', 'required' => true, 'description' => '用户数据'],
                ],
            ],
            self::USER_BEFORE_LOGOUT => [
                'description' => '用户登出前触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '用户ID'],
                ],
            ],

            // ─── 模板模块 ───
            self::TEMPLATE_BEFORE_RENDER => [
                'description' => '模板渲染前触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'template_name' => ['type' => 'string', 'required' => true, 'description' => '模板名称'],
                    'data' => ['type' => 'array', 'required' => true, 'description' => '模板数据'],
                ],
            ],
            self::TEMPLATE_AFTER_RENDER => [
                'description' => '模板渲染后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'template_name' => ['type' => 'string', 'required' => true, 'description' => '模板名称'],
                    'html_content' => ['type' => 'string', 'required' => true, 'description' => '渲染后的HTML'],
                ],
            ],
            self::TEMPLATE_BEFORE_INSTALL => [
                'description' => '模板安装前触发（可阻止安装）',
                'since' => '2.9.26',
                'supports_block' => true,
                'parameters' => [
                    'template_id' => ['type' => 'int', 'required' => true, 'description' => '模板ID'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '模板版本'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::TEMPLATE_AFTER_INSTALL => [
                'description' => '模板安装完成后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'template_id' => ['type' => 'int', 'required' => true, 'description' => '模板ID'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '模板版本'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],
            self::TEMPLATE_AFTER_UNINSTALL => [
                'description' => '模板卸载完成后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'template_id' => ['type' => 'int', 'required' => true, 'description' => '模板ID'],
                    'user_id' => ['type' => 'int', 'required' => true, 'description' => '操作人ID'],
                ],
            ],

            // ─── 缓存模块 ───
            self::CACHE_AFTER_CLEAR => [
                'description' => '缓存清理完成后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'cache_type' => ['type' => 'string', 'required' => true, 'description' => '缓存类型'],
                    'operator' => ['type' => 'string', 'required' => false, 'description' => '操作人'],
                ],
            ],
            self::CACHE_AFTER_HIT => [
                'description' => '缓存命中时触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'key' => ['type' => 'string', 'required' => true, 'description' => '缓存键'],
                    'tag' => ['type' => 'string', 'required' => false, 'description' => '缓存标签'],
                ],
            ],
            self::CACHE_AFTER_MISS => [
                'description' => '缓存未命中时触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'key' => ['type' => 'string', 'required' => true, 'description' => '缓存键'],
                    'tag' => ['type' => 'string', 'required' => false, 'description' => '缓存标签'],
                ],
            ],

            // ─── 插件模块 ───
            self::PLUGIN_BEFORE_ENABLE => [
                'description' => '插件启用前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                ],
            ],
            self::PLUGIN_AFTER_ENABLE => [
                'description' => '插件启用后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                ],
            ],
            self::PLUGIN_BEFORE_DISABLE => [
                'description' => '插件禁用前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                ],
            ],
            self::PLUGIN_AFTER_DISABLE => [
                'description' => '插件禁用后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                ],
            ],
            self::PLUGIN_BEFORE_UNINSTALL => [
                'description' => '插件卸载前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                ],
            ],
            self::PLUGIN_AFTER_UNINSTALL => [
                'description' => '插件卸载后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                ],
            ],
            self::PLUGIN_AFTER_DOWNLOAD => [
                'description' => '插件下载完成后触发',
                'since' => '2.9.26',
                'supports_block' => false,
                'parameters' => [
                    'plugin_name' => ['type' => 'string', 'required' => true, 'description' => '插件标识'],
                    'version' => ['type' => 'string', 'required' => false, 'description' => '插件版本'],
                    'user_id' => ['type' => 'int', 'required' => false, 'description' => '下载用户ID'],
                ],
            ],

            // ─── 系统配置模块 ───
            self::SYSTEM_CONFIG_BEFORE_UPDATE => [
                'description' => '系统配置更新前触发',
                'since' => '2.9.25',
                'supports_block' => true,
                'parameters' => [
                    'config_key' => ['type' => 'string', 'required' => true, 'description' => '配置键'],
                    'old_value' => ['type' => 'mixed', 'required' => false, 'description' => '旧值'],
                    'new_value' => ['type' => 'mixed', 'required' => true, 'description' => '新值'],
                ],
            ],
            self::SYSTEM_CONFIG_AFTER_UPDATE => [
                'description' => '系统配置更新后触发',
                'since' => '2.9.25',
                'supports_block' => false,
                'parameters' => [
                    'config_key' => ['type' => 'string', 'required' => true, 'description' => '配置键'],
                    'old_value' => ['type' => 'mixed', 'required' => false, 'description' => '旧值'],
                    'new_value' => ['type' => 'mixed', 'required' => true, 'description' => '新值'],
                ],
            ],
        ];
    }

    /**
     * 获取事件总数
     */
    public static function count(): int
    {
        return count(self::all());
    }
}
