<?php
declare(strict_types=1);

namespace app\common\service\data;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 数据大屏交互服务 - V2.9.40 DATA-DEEP2-1
 *
 * 支持大屏布局拖拽排序、分享链接(密码保护+有效期)、模板管理
 */
class DashboardInteractionService
{
    private const CACHE_TAG = 'dashboard_interaction';
    private const CACHE_TTL = 300;

    /**
     * 保存布局配置（拖拽排序后的模块位置）
     */
    public function saveLayout(int $dashboardId, array $layout): bool
    {
        $result = Db::name('data_dashboard')->where('id', $dashboardId)->update([
            'layout_config' => json_encode($layout),
            'updated_at'    => time(),
        ]);
        Cache::clear();
        return $result > 0;
    }

    /**
     * 获取布局配置
     */
    public function getLayout(int $dashboardId): array
    {
        return Cache::remember('layout_' . $dashboardId, function () use ($dashboardId) {
            $dashboard = Db::name('data_dashboard')->find($dashboardId);
            if (!$dashboard) return [];
            return json_decode($dashboard['layout_config'] ?? '{}', true) ?: [];
        }, self::CACHE_TTL);
    }

    /**
     * 创建分享链接
     */
    public function createShareLink(int $dashboardId, array $options = []): array
    {
        $token = md5($dashboardId . time() . mt_rand(1000, 9999));
        $password = $options['password'] ?? '';
        $expireAt = isset($options['expire_hours']) && $options['expire_hours'] > 0
            ? time() + $options['expire_hours'] * 3600
            : 0; // 0=永不过期

        Db::name('data_dashboard_share')->insert([
            'dashboard_id' => $dashboardId,
            'token'        => $token,
            'password'     => $password,
            'expire_at'    => $expireAt,
            'view_count'   => 0,
            'created_at'   => time(),
            'updated_at'   => time(),
        ]);

        return [
            'token'    => $token,
            'url'      => '/dashboard/share/' . $token,
            'expire_at' => $expireAt,
            'has_password' => !empty($password),
        ];
    }

    /**
     * 验证分享链接
     */
    public function verifyShareLink(string $token, string $password = ''): array
    {
        $share = Db::name('data_dashboard_share')->where('token', $token)->find();
        if (!$share) {
            return ['valid' => false, 'msg' => '分享链接不存在'];
        }
        if ($share['expire_at'] > 0 && $share['expire_at'] < time()) {
            return ['valid' => false, 'msg' => '分享链接已过期'];
        }
        if (!empty($share['password']) && $share['password'] !== $password) {
            return ['valid' => false, 'msg' => '密码错误'];
        }

        // 更新浏览次数
        Db::name('data_dashboard_share')->where('token', $token)->inc('view_count')->update();

        return ['valid' => true, 'dashboard_id' => $share['dashboard_id']];
    }

    /**
     * 获取分享链接列表
     */
    public function getShareLinks(int $dashboardId): array
    {
        return Db::name('data_dashboard_share')
            ->where('dashboard_id', $dashboardId)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 删除分享链接
     */
    public function deleteShareLink(string $token): bool
    {
        Db::name('data_dashboard_share')->where('token', $token)->delete();
        return true;
    }

    /**
     * 保存模板（从当前大屏导出为模板）
     */
    public function saveTemplate(int $dashboardId, string $name, string $description = ''): int
    {
        $dashboard = Db::name('data_dashboard')->find($dashboardId);
        if (!$dashboard) return 0;

        $id = Db::name('data_dashboard_template')->insertGetId([
            'name'           => $name,
            'description'    => $description,
            'layout_config'  => $dashboard['layout_config'] ?? '{}',
            'module_config'  => $dashboard['module_config'] ?? '{}',
            'is_public'      => 1,
            'use_count'      => 0,
            'created_at'     => time(),
            'updated_at'     => time(),
        ]);

        return (int) $id;
    }

    /**
     * 从模板创建大屏
     */
    public function createFromTemplate(int $templateId, string $name): int
    {
        $template = Db::name('data_dashboard_template')->find($templateId);
        if (!$template) return 0;

        $id = Db::name('data_dashboard')->insertGetId([
            'name'           => $name,
            'layout_config'  => $template['layout_config'],
            'module_config'  => $template['module_config'],
            'status'         => 1,
            'created_at'     => time(),
            'updated_at'     => time(),
        ]);

        Db::name('data_dashboard_template')->where('id', $templateId)->inc('use_count')->update();
        Cache::clear();

        return (int) $id;
    }

    /**
     * 获取模板列表
     */
    public function getTemplateList(int $page = 1, int $limit = 20): array
    {
        return Db::name('data_dashboard_template')
            ->where('is_public', 1)
            ->order('use_count', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 删除模板
     */
    public function deleteTemplate(int $templateId): bool
    {
        Db::name('data_dashboard_template')->where('id', $templateId)->delete();
        return true;
    }
}
