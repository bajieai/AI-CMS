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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Config as ConfigModel;

/**
 * 通知设置管理控制器 (V2.9.20 C-4)
 * 对应菜单ID 483
 */
class NotificationSettingController extends AdminBaseController
{
    /**
     * 通知设置列表页
     */
    public function index()
    {
        $settings = ConfigModel::where('group', 'notification')->column('value', 'name');

        // 解析默认设置JSON
        $defaultSettings = [];
        if (!empty($settings['notify_default_settings'])) {
            $decoded = json_decode($settings['notify_default_settings'], true);
            if (is_array($decoded)) {
                $defaultSettings = $decoded;
            }
        }

        return $this->view('/notification_setting', [
            'settings' => $defaultSettings,
        ]);
    }

    /**
     * 保存通知设置
     */
    public function save()
    {
        $data = $this->request->post('settings', []);

        if (!is_array($data)) {
            return $this->error('参数错误');
        }

        // 白名单校验
        $allowedKeys = [
            'system', 'review', 'publish', 'comment_reply',
            'content_approve', 'content_reject', 'reward_receive',
            'level_upgrade', 'level_downgrade', 'level_grace_warning',
        ];

        $filtered = [];
        foreach ($allowedKeys as $key) {
            $filtered[$key] = isset($data[$key]) && $data[$key] ? 1 : 0;
        }

        $config = ConfigModel::where('name', 'notify_default_settings')->where('group', 'notification')->find();
        if ($config) {
            $config->value = json_encode($filtered, JSON_UNESCAPED_UNICODE);
            $config->save();
        } else {
            ConfigModel::create([
                'name'  => 'notify_default_settings',
                'value' => json_encode($filtered, JSON_UNESCAPED_UNICODE),
                'group' => 'notification',
            ]);
        }

        // 清除相关缓存
        \think\facade\Cache::clear();

        return $this->success('设置已保存');
    }
}
