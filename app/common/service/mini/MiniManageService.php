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

namespace app\common\service\mini;

use app\common\model\MiniConfig;
use think\facade\Cache;
use think\facade\Db;

/**
 * MINI-5 管理后台
 * 配置管理/页面管理/发布信息/统计/错误日志
 */
class MiniManageService
{
    /**
     * 配置列表 (按分组)
     */
    public function getConfigList(): array
    {
        $list = MiniConfig::order('config_group', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $groups = [];
        foreach ($list as $item) {
            $group = $item['config_group'] ?? 'basic';
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            $groups[$group][] = $item;
        }

        return $groups;
    }

    /**
     * 保存配置
     */
    public function saveConfig(array $data): array
    {
        $updated = 0;
        foreach ($data as $key => $value) {
            if (in_array($key, ['appid', 'secret', 'mini_name', 'theme_color',
                'enable_comment', 'enable_favorite', 'enable_like', 'api_rate_limit'])) {
                $item = MiniConfig::where('config_key', $key)->find();
                if ($item) {
                    $item->config_value = (string) $value;
                    $item->save();
                } else {
                    MiniConfig::create([
                        'config_key'   => $key,
                        'config_value' => (string) $value,
                        'config_group' => 'basic',
                    ]);
                }
                $updated++;
            }
        }

        Cache::clear();
        Cache::clear();

        return ['code' => 0, 'msg' => '保存成功', 'data' => ['updated' => $updated]];
    }

    /**
     * 页面列表
     */
    public function getPageList(): array
    {
        $pages = MiniConfig::where('config_group', 'page')->select()->toArray();
        $result = [];
        foreach ($pages as $page) {
            $config = json_decode($page['config_value'], true);
            $result[] = [
                'name'        => str_replace('page_', '', $page['config_key']),
                'title'       => $config['title'] ?? $page['config_key'],
                'components'  => count($config['components'] ?? []),
                'update_time' => $page['update_time'] ?? '',
            ];
        }

        // 补充默认页面
        $defaultPages = ['index', 'list', 'user'];
        foreach ($defaultPages as $dp) {
            $exists = false;
            foreach ($result as $r) {
                if ($r['name'] === $dp) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $result[] = ['name' => $dp, 'title' => $dp, 'components' => 0, 'update_time' => ''];
            }
        }

        return $result;
    }

    /**
     * 保存页面配置
     */
    public function savePage(string $pageName, array $config): array
    {
        $key = 'page_' . $pageName;
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);

        $item = MiniConfig::where('config_key', $key)->find();
        if ($item) {
            $item->config_value = $configJson;
            $item->save();
        } else {
            MiniConfig::create([
                'config_key'         => $key,
                'config_value'       => $configJson,
                'config_group'       => 'page',
                'config_description' => $pageName . '页面配置',
            ]);
        }

        Cache::clear();

        return ['code' => 0, 'msg' => '保存成功', 'data' => ['page' => $pageName]];
    }

    /**
     * 发布信息
     */
    public function getPublishInfo(): array
    {
        return [
            'appid'      => MiniConfig::getValue('appid', ''),
            'mini_name'  => MiniConfig::getValue('mini_name', ''),
            'version'    => MiniConfig::getValue('version', '1.0.0'),
            'last_upload' => MiniConfig::getValue('last_upload_time', ''),
            'status'     => MiniConfig::getValue('publish_status', 'pending'),
        ];
    }

    /**
     * 上传代码到微信 (占位)
     */
    public function uploadCode(): array
    {
        $appid = MiniConfig::getValue('appid', '');
        if (empty($appid)) {
            return ['code' => 1, 'msg' => '请先配置AppID', 'data' => null];
        }

        // 占位: 实际需要调用微信第三方平台API上传代码
        MiniConfig::where('config_key', 'last_upload_time')
            ->update(['config_value' => date('Y-m-d H:i:s')]);

        return ['code' => 0, 'msg' => '代码上传成功(占位)', 'data' => ['time' => date('Y-m-d H:i:s')]];
    }

    /**
     * 统计数据
     */
    public function getStats(int $days = 30): array
    {
        $startTime = time() - $days * 86400;

        // 小程序用户统计
        $totalUsers = Db::name('member')->where('wechat_openid', '<>', '')->count();
        $newUsers = Db::name('member')
            ->where('wechat_openid', '<>', '')
            ->where('create_time', '>=', $startTime)
            ->count();

        // 内容统计
        $totalContent = Db::name('content')->where('status', 1)->count();
        $totalViews = (int) Db::name('content')->where('status', 1)->sum('views');
        $totalLikes = (int) Db::name('content')->where('status', 1)->sum('likes');

        // 互动统计
        $totalFavorites = Db::name('favorite')->count();
        $totalComments = Db::name('comment')->where('status', 1)->count();

        // 最近7天趋势
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', time() - $i * 86400);
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400;
            $trend[] = [
                'date'      => $date,
                'new_users' => Db::name('member')->where('wechat_openid', '<>', '')
                    ->where('create_time', '>=', $dayStart)->where('create_time', '<', $dayEnd)->count(),
                'views'     => 0,
            ];
        }

        return [
            'total_users'     => $totalUsers,
            'new_users'       => $newUsers,
            'total_content'   => $totalContent,
            'total_views'     => $totalViews,
            'total_likes'     => $totalLikes,
            'total_favorites' => $totalFavorites,
            'total_comments'  => $totalComments,
            'trend'           => $trend,
        ];
    }

    /**
     * 错误日志
     */
    public function getErrorLog(int $page = 1): array
    {
        $limit = 20;
        $query = Db::name('mini_error_log')->order('id', 'desc');

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'has_more' => ($page * $limit) < $total,
        ];
    }
}
