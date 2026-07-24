<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Db;

/**
 * 通知订阅服务
 */
class NotifySubscriptionService
{
    public function getUserPreferences(int $userId): array
    {
        $config = Db::name('system_config')->where('config_key', 'notify_pref_' . $userId)->value('config_value');
        return $config ? json_decode($config, true) : ['sms' => true, 'email' => true, 'in_app' => true, 'wechat' => false, 'do_not_disturb' => false];
    }

    public function updatePreferences(int $userId, array $preferences): bool
    {
        $key = 'notify_pref_' . $userId;
        $exists = Db::name('system_config')->where('config_key', $key)->find();
        if ($exists) {
            Db::name('system_config')->where('config_key', $key)->update(['config_value' => json_encode($preferences, JSON_UNESCAPED_UNICODE)]);
        } else {
            Db::name('system_config')->insert(['config_key' => $key, 'config_value' => json_encode($preferences, JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s')]);
        }
        return true;
    }

    public function setDoNotDisturb(int $userId, bool $enabled): bool
    {
        $pref = $this->getUserPreferences($userId);
        $pref['do_not_disturb'] = $enabled;
        return $this->updatePreferences($userId, $pref);
    }
}
