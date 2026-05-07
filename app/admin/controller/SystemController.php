<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Config as ConfigModel;
use app\common\model\CustomVar;
use app\common\model\Module;
use app\common\service\TemplateService;

/**
 * 系统管理控制器
 */
class SystemController extends AdminBaseController
{
    /**
     * 系统配置
     */
    public function config()
    {
        $this->app->view->assign('menuActive', 'system_config');

        if ($this->request->isGet()) {
            $configs = ConfigModel::order('sort', 'asc')->select();
            $groups = [];
            foreach ($configs as $config) {
                $groups[$config->group][] = $config;
            }
            // 移除site分组（主题切换由上方卡片选择器完成，不重复显示）
            unset($groups['site']);

            // 分组显示顺序（按用户要求：基本设置第1，AI设置第2，会员设置第3）
            // 注意：site分组（主题设置）已移除，主题切换由上方卡片选择器完成
            $groupOrder = [
                'basic',        // 1. 基本设置
                'ai',           // 2. AI设置
                'member',       // 3. 会员设置
                'upload',       // 4. 上传设置
                'comment',      // 5. 评论设置
                'ad',           // 6. 广告设置
                'email',        // 7. 邮件设置
                'notification', // 8. 通知设置
                'oauth',        // 9. 第三方登录
                'payment',      // 10. 支付设置
                'points',       // 11. 积分设置
                'security',     // 12. 安全设置
                'seo',          // 13. SEO设置
                'system',       // 14. 系统设置
            ];

            // 分组中文名称与图标映射
            $groupNames = [
                'basic'        => '基本设置',
                'upload'       => '上传设置',
                'ai'           => 'AI设置',
                'member'       => '会员设置',
                'comment'      => '评论设置',
                'ad'           => '广告设置',
                'email'        => '邮件设置',
                'notification' => '通知设置',
                'oauth'        => '第三方登录',
                'payment'      => '支付设置',
                'points'       => '积分设置',
                'security'     => '安全设置',
                'seo'          => 'SEO设置',
                'site'         => '主题设置',
                'system'       => '系统设置',
            ];
            $groupIcons = [
                'basic'        => 'gear',
                'upload'       => 'cloud-upload',
                'ai'           => 'robot',
                'member'       => 'people',
                'comment'      => 'chat-left-text',
                'ad'           => 'badge-ad',
                'email'        => 'envelope',
                'notification' => 'bell',
                'oauth'        => 'box-arrow-in-right',
                'payment'      => 'credit-card',
                'points'       => 'coin',
                'security'     => 'shield-lock',
                'seo'          => 'search',
                'site'         => 'palette2',
                'system'       => 'sliders2',
            ];

            // 按指定顺序重新排列分组
            $sortedGroups = [];
            foreach ($groupOrder as $groupKey) {
                if (isset($groups[$groupKey])) {
                    $sortedGroups[$groupKey] = $groups[$groupKey];
                }
            }
            // 将未定义顺序的分组追加到末尾
            foreach ($groups as $key => $value) {
                if (!isset($sortedGroups[$key])) {
                    $sortedGroups[$key] = $value;
                }
            }

            $this->assign(['groups' => $sortedGroups, 'groupNames' => $groupNames, 'groupIcons' => $groupIcons]);
            return $this->view('/system_config');
        }

        // 保存配置
        $data = $this->request->post();
        foreach ($data as $name => $value) {
            ConfigModel::where('name', $name)->update(['value' => $value]);
        }

        $this->recordLog('保存系统配置', '', $data);
        return $this->success('保存成功');
    }

    /**
     * 自定义变量列表页
     */
    public function customVar()
    {
        if ($this->request->isGet()) {
            $list = CustomVar::order('sort', 'asc')->select();
            $this->assign(['list' => $list]);
            return $this->view('/system_custom_var');
        }

        // POST: 批量保存排序
        $data = $this->request->post('sort/a', []);
        foreach ($data as $id => $sort) {
            CustomVar::where('id', (int) $id)->update(['sort' => (int) $sort]);
        }
        CustomVar::clearCache();
        $this->recordLog('保存自定义变量排序');
        return $this->success('保存成功');
    }

    /**
     * 新增/编辑自定义变量（AJAX）
     */
    public function customVarSave()
    {
        $id = $this->request->post('id', 0);
        $name = trim($this->request->post('name', ''));
        $value = $this->request->post('value', '');
        $remark = trim($this->request->post('remark', ''));
        $sort = (int) $this->request->post('sort', 0);

        if (empty($name)) {
            return $this->error('变量名不能为空');
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            return $this->error('变量名只能包含字母、数字和下划线，且不能以数字开头');
        }

        $data = [
            'name'   => $name,
            'value'  => $value,
            'remark' => $remark,
            'sort'   => $sort,
        ];

        if ($id) {
            $exists = CustomVar::where('name', $name)->where('id', '<>', $id)->find();
            if ($exists) {
                return $this->error('变量名已存在');
            }
            CustomVar::where('id', (int) $id)->update($data);
            $this->recordLog('编辑自定义变量', $name, $data);
        } else {
            $exists = CustomVar::where('name', $name)->find();
            if ($exists) {
                return $this->error('变量名已存在');
            }
            CustomVar::create($data);
            $this->recordLog('新增自定义变量', $name, $data);
        }

        CustomVar::clearCache();
        return $this->success('保存成功');
    }

    /**
     * 删除自定义变量
     */
    public function customVarDelete()
    {
        $id = (int) $this->request->post('id', 0);
        if (!$id) {
            return $this->error('参数错误');
        }

        $var = CustomVar::find($id);
        if (!$var) {
            return $this->error('变量不存在');
        }

        $var->delete();
        CustomVar::clearCache();
        $this->recordLog('删除自定义变量', $var->name);
        return $this->success('删除成功');
    }

    /**
     * 功能开关页面
     */
    public function moduleControl()
    {
        $this->app->view->assign('menuActive', 'system_module');

        if ($this->request->isGet()) {
            // 若模块表为空，自动初始化默认数据
            if (Module::count() == 0) {
                $this->initDefaultModules();
            }

            $modules = Module::order('sort', 'asc')->select();
            $categories = [
                'core'       => '核心模块',
                'operation'  => '内容运营',
                'interaction'=> '互动管理',
                'seo_data'   => 'SEO与数据',
                'extension'  => '高级扩展',
            ];
            $grouped = [];
            foreach ($modules as $module) {
                $cat = $module->category ?: 'other';
                $grouped[$cat][] = $module;
            }

            $this->assign([
                'grouped'    => $grouped,
                'categories' => $categories,
            ]);
            return $this->view('/system_module');
        }

        return $this->error('非法请求');
    }

    /**
     * 初始化默认模块数据
     */
    protected function initDefaultModules(): void
    {
        $defaultModules = [
            ['code' => 'content', 'name' => '内容管理', 'description' => '内容发布、分类、标签、回收站', 'icon' => 'bi-file-text', 'category' => 'core', 'is_system' => 1, 'is_enabled' => 1, 'sort' => 1, 'menu_ids' => '[11,12,13,14,15,16]'],
            ['code' => 'user', 'name' => '用户管理', 'description' => '后台用户管理', 'icon' => 'bi-people', 'category' => 'core', 'is_system' => 1, 'is_enabled' => 1, 'sort' => 2, 'menu_ids' => '[21]'],
            ['code' => 'banner', 'name' => '轮播图', 'description' => '首页轮播图管理', 'icon' => 'bi-images', 'category' => 'operation', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 10, 'menu_ids' => '[33]'],
            ['code' => 'link', 'name' => '友情链接', 'description' => '友链及分组管理', 'icon' => 'bi-link-45deg', 'category' => 'operation', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 11, 'menu_ids' => '[34,35]'],
            ['code' => 'ad', 'name' => '广告系统', 'description' => '广告位与广告管理', 'icon' => 'bi-badge-ad', 'category' => 'operation', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 12, 'menu_ids' => '[36]'],
            ['code' => 'comment', 'name' => '评论系统', 'description' => '前台评论与审核', 'icon' => 'bi-chat-left-text', 'category' => 'interaction', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 20, 'menu_ids' => '[51]'],
            ['code' => 'member', 'name' => '前台会员', 'description' => '会员注册登录与互动', 'icon' => 'bi-person-badge', 'category' => 'interaction', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 21, 'menu_ids' => '[52]'],
            ['code' => 'seo', 'name' => 'SEO管理', 'description' => 'Sitemap、robots.txt、结构化数据', 'icon' => 'bi-search', 'category' => 'seo_data', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 30, 'menu_ids' => '[61]'],
            ['code' => 'export', 'name' => '数据导出', 'description' => 'Excel/CSV导入导出', 'icon' => 'bi-download', 'category' => 'seo_data', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 31, 'menu_ids' => '[62]'],
            ['code' => 'token', 'name' => 'API令牌', 'description' => 'RESTful API Token管理', 'icon' => 'bi-key', 'category' => 'seo_data', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 32, 'menu_ids' => '[63]'],
            ['code' => 'notification', 'name' => '消息通知', 'description' => '站内通知与提醒', 'icon' => 'bi-bell', 'category' => 'extension', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 40, 'menu_ids' => '[44]'],
            ['code' => 'backup', 'name' => '数据库备份', 'description' => '数据库备份与恢复', 'icon' => 'bi-database', 'category' => 'extension', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 41, 'menu_ids' => '[43]'],
            ['code' => 'ai_model', 'name' => 'AI模型管理', 'description' => 'AI大模型配置与管理', 'icon' => 'bi-robot', 'category' => 'extension', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 42, 'menu_ids' => ''],
            ['code' => 'member_level', 'name' => '会员等级', 'description' => '会员等级与权益', 'icon' => 'bi-award', 'category' => 'interaction', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 22, 'menu_ids' => ''],
            ['code' => 'points', 'name' => '积分体系', 'description' => '积分规则与兑换', 'icon' => 'bi-coin', 'category' => 'interaction', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 23, 'menu_ids' => ''],
            ['code' => 'paid_content', 'name' => '付费阅读', 'description' => '内容付费与订单', 'icon' => 'bi-cash-coin', 'category' => 'extension', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 43, 'menu_ids' => ''],
            ['code' => 'form_builder', 'name' => '表单生成器', 'description' => '自定义表单与数据收集', 'icon' => 'bi-ui-radios', 'category' => 'extension', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 44, 'menu_ids' => ''],
            ['code' => 'seo_keyword', 'name' => 'SEO关键词库', 'description' => '关键词挖掘与优化', 'icon' => 'bi-tags', 'category' => 'seo_data', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 33, 'menu_ids' => ''],
            ['code' => 'dashboard', 'name' => '数据看板', 'description' => '访问统计与数据分析', 'icon' => 'bi-graph-up', 'category' => 'core', 'is_system' => 1, 'is_enabled' => 1, 'sort' => 3, 'menu_ids' => ''],
            ['code' => 'oauth_manage', 'name' => 'OAuth管理', 'description' => '第三方登录配置', 'icon' => 'bi-box-arrow-in-right', 'category' => 'extension', 'is_system' => 0, 'is_enabled' => 1, 'sort' => 45, 'menu_ids' => ''],
        ];

        $now = time();
        foreach ($defaultModules as &$item) {
            $item['create_time'] = $now;
            $item['update_time'] = $now;
        }
        Module::insertAll($defaultModules);
        Module::clearCache();
    }

    /**
     * 切换模块启用状态（AJAX）
     */
    public function moduleToggle()
    {
        try {
            $roleId = (int) session('role_id');
            if ($roleId !== 1) {
                return $this->error('仅超级管理员可操作');
            }

            $id = (int) $this->request->post('id', 0);
            $isEnabled = (int) $this->request->post('is_enabled', 0);

            if (!$id) {
                return $this->error('参数错误');
            }

            $module = Module::find($id);
            if (!$module) {
                return $this->error('模块不存在');
            }

            if ($module->is_system) {
                return $this->error('系统模块不可关闭');
            }

            $module->is_enabled = $isEnabled;
            $module->save();
            Module::clearCache();

            $this->recordLog($isEnabled ? '启用模块' : '禁用模块', $module->name);
            return $this->success('操作成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // ═══════════ 前台主题管理 ═══════════

    /**
     * GET /admin/system/templates - 获取可用前台主题列表
     */
    public function templates()
    {
        $themes = TemplateService::scanThemes();
        $activeTheme = TemplateService::getActiveTheme();
        foreach ($themes as &$theme) {
            $theme['is_active'] = ($theme['name'] === $activeTheme);
        }
        return $this->success('获取成功', [
            'themes' => $themes,
            'active'  => $activeTheme,
        ]);
    }

    /**
     * POST /admin/system/setTheme - 切换前台主题
     */
    public function setTheme()
    {
        $theme = $this->request->post('theme', '');
        $themes = TemplateService::scanThemes();
        $validNames = array_column($themes, 'name');
        if (!in_array($theme, $validNames, true)) {
            return $this->error('主题不存在或无效');
        }

        $config = ConfigModel::where('name', 'frontend_theme')->find();
        if (!$config) {
            ConfigModel::create([
                'name'   => 'frontend_theme',
                'value'  => $theme,
                'title'  => '前台主题',
                'group'  => 'site',
                'type'   => 'string',
                'sort'   => 50,
            ]);
        } else {
            $config->value = $theme;
            $config->save();
        }

        TemplateService::clearCache();
        $this->recordLog('switch_theme', "切换前台主题为: {$theme}");
        return $this->success("前台主题已切换为: {$theme}");
    }

    // ═══════════ 后台主题管理 ═══════════

    /**
     * GET /admin/system/adminTemplates - 获取可用后台主题列表
     */
    public function adminTemplates()
    {
        $themes = TemplateService::scanAdminThemes();
        $activeTheme = TemplateService::getAdminTheme();
        foreach ($themes as &$theme) {
            $theme['is_active'] = ($theme['name'] === $activeTheme);
        }
        return $this->success('获取成功', [
            'themes' => $themes,
            'active'  => $activeTheme,
        ]);
    }

    /**
     * POST /admin/system/setAdminTheme - 切换后台主题
     */
    public function setAdminTheme()
    {
        $theme = $this->request->post('admin_theme', '');
        $themes = TemplateService::scanAdminThemes();
        $validNames = array_column($themes, 'name');
        if (!in_array($theme, $validNames, true)) {
            return $this->error('后台主题不存在或无效');
        }

        $config = ConfigModel::where('name', 'admin_theme')->find();
        if (!$config) {
            ConfigModel::create([
                'name'   => 'admin_theme',
                'value'  => $theme,
                'title'  => '后台主题',
                'group'  => 'site',
                'type'   => 'string',
                'sort'   => 51,
            ]);
        } else {
            $config->value = $theme;
            $config->save();
        }

        TemplateService::clearAdminCache();
        $this->recordLog('switch_admin_theme', "切换后台主题为: {$theme}");
        return $this->success("后台主题已切换为: {$theme}，刷新页面后生效");
    }

    /**
     * GET /admin/system/allTemplates - 一次性获取前后台所有模板信息
     */
    public function allTemplates()
    {
        return $this->success('获取成功', [
            'frontend' => [
                'themes' => TemplateService::scanThemes(),
                'active'  => TemplateService::getActiveTheme(),
            ],
            'admin' => [
                'themes' => TemplateService::scanAdminThemes(),
                'active'  => TemplateService::getAdminTheme(),
            ],
        ]);
    }
}
