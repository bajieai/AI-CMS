<?php
declare(strict_types=1);

namespace app\common\service\system;

use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

/**
 * 短信服务
 * V2.9.38 SYS-INTEG-3
 * 3个适配器+自动切换+防刷机制
 */
class SmsService
{
    protected array $adapters = [];
    // V2.9.55: smsbao 置顶（唯一真实发短信的适配器，配置了即优先使用；空壳占位适配器排后兜底顺序不变）
    protected array $adapterPriority = ['smsbao', 'aliyun', 'tencent', 'qiniu'];

    public function __construct()
    {
        $config = Config::get('sms', []);

        // V2.9.62: 后台「短信配置」页保存在 i8j_config 的 sms_* 扁平键，经 load_cms_configs()
        // 加载进 Config 的 sms 组；此处优先级高于 config/sms.php(读 .env)，让后台配置即时生效。
        $dbConfig = $this->loadDbSmsConfig();
        foreach (['smsbao', 'aliyun', 'tencent', 'qiniu'] as $adapter) {
            if (isset($dbConfig[$adapter])) {
                $config[$adapter] = array_merge($config[$adapter] ?? [], $dbConfig[$adapter]);
            }
        }
        // 默认渠道（后台可指定优先使用的服务商）
        $defaultChannel = Config::get('sms.sms_default');
        if ($defaultChannel) {
            $config['default'] = $defaultChannel;
        }

        // V2.9.55: 短信宝（真实HTTP调用，低成本起步）
        if (!empty($config['smsbao']['username']) && !empty($config['smsbao']['password'])) $this->adapters['smsbao'] = new SmsbaoSmsAdapter($config['smsbao']);
        if (!empty($config['aliyun']['access_key'])) $this->adapters['aliyun'] = new AliyunSmsAdapter($config['aliyun']);
        if (!empty($config['tencent']['secret_id'])) $this->adapters['tencent'] = new TencentSmsAdapter($config['tencent']);
        if (!empty($config['qiniu']['access_key'])) $this->adapters['qiniu'] = new QiniuSmsAdapter($config['qiniu']);
    }

    /**
     * V2.9.62: 从数据库配置(i8j_config 的 sms_* 扁平键)读取各适配器凭据
     * load_cms_configs() 已将这些键加载到 Config 的 sms 组下（键名即去除 sms_ 前缀，如
     * sms_smsbao_username → Config::get('sms.smsbao_username')），此处映射回嵌套结构。
     */
    protected function loadDbSmsConfig(): array
    {
        $map = [
            'smsbao'  => ['username' => 'smsbao_username', 'password' => 'smsbao_password', 'sign_name' => 'smsbao_sign_name', 'content_template' => 'smsbao_content_template'],
            'aliyun'  => ['access_key' => 'aliyun_access_key', 'access_secret' => 'aliyun_access_secret', 'sign_name' => 'aliyun_sign_name'],
            'tencent' => ['secret_id' => 'tencent_secret_id', 'secret_key' => 'tencent_secret_key', 'sign_name' => 'tencent_sign_name', 'sdk_app_id' => 'tencent_sdk_app_id'],
            'qiniu'   => ['access_key' => 'qiniu_access_key', 'secret_key' => 'qiniu_secret_key', 'sign_name' => 'qiniu_sign_name'],
        ];
        $db = [];
        foreach ($map as $adapter => $fields) {
            foreach ($fields as $cfgKey => $dbKey) {
                $val = Config::get('sms.' . $dbKey);
                if ($val !== null && $val !== '') {
                    $db[$adapter][$cfgKey] = $val;
                }
            }
        }
        return $db;
    }

    /**
     * 发送短信
     */
    public function send(string $mobile, string $templateCode, array $params = [], ?string $channel = null): array
    {
        $adapter = $this->selectAdapter($channel);
        if (!$adapter) throw new \RuntimeException('No SMS adapter available');
        
        try {
            $result = $adapter->send($mobile, $templateCode, $params);
            $this->logSms($mobile, $templateCode, $params, $adapter->getName(), 'success', $result);
            return $result;
        } catch (\Throwable $e) {
            $this->logSms($mobile, $templateCode, $params, $adapter->getName(), 'failed', ['error' => $e->getMessage()]);
            // 自动切换到备用适配器
            $backup = $this->getBackupAdapter($adapter->getName());
            if ($backup) {
                $result = $backup->send($mobile, $templateCode, $params);
                $this->logSms($mobile, $templateCode, $params, $backup->getName(), 'success', $result);
                return $result;
            }
            throw $e;
        }
    }

    /**
     * 发送验证码
     */
    public function sendVerifyCode(string $mobile, string $type = 'register'): array
    {
        // 防刷: 频率限制
        $key = 'sms_freq_' . $mobile;
        $lastSend = Cache::get($key);
        if ($lastSend && (time() - $lastSend) < 60) {
            throw new \RuntimeException('发送过于频繁，请60秒后再试');
        }
        
        // IP频率限制
        $ipKey = 'sms_ip_' . request()->ip();
        $ipCount = Cache::get($ipKey, 0);
        if ($ipCount >= 10) throw new \RuntimeException('该IP今日发送次数已达上限');
        Cache::set($ipKey, $ipCount + 1, 86400);
        
        // 生成验证码
        $code = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::set('sms_code_' . $type . '_' . $mobile, $code, 300); // 5分钟有效

        try {
            $result = $this->send($mobile, 'verify_code', ['code' => $code]);
        } catch (\Throwable $e) {
            // V2.9.54: 发送失败删除已写入的验证码缓存，防止用户拿到未送达的码注册
            Cache::delete('sms_code_' . $type . '_' . $mobile);
            // 保留 60 秒频率限制（防止异常态下快速重试轰炸适配器），重新抛出由调用方给出友好提示
            Cache::set($key, time(), 60);
            throw $e;
        }
        Cache::set($key, time(), 60);

        return $result;
    }

    /**
     * 验证验证码
     */
    public function verifyCode(string $mobile, string $code, string $type = 'register'): bool
    {
        $cachedCode = Cache::get('sms_code_' . $type . '_' . $mobile);
        if (!$cachedCode || $cachedCode !== $code) return false;
        Cache::delete('sms_code_' . $type . '_' . $mobile);
        return true;
    }

    protected function selectAdapter(?string $channel = null)
    {
        if ($channel && isset($this->adapters[$channel])) return $this->adapters[$channel];
        // V2.9.62: 优先使用后台配置的默认渠道
        $default = Config::get('sms.default');
        if ($default && isset($this->adapters[$default])) return $this->adapters[$default];
        foreach ($this->adapterPriority as $name) {
            if (isset($this->adapters[$name])) return $this->adapters[$name];
        }
        return null;
    }

    /**
     * V2.9.62: 返回当前已配置（可用）的短信通道列表，供后台页面展示
     */
    public function getAvailableAdapters(): array
    {
        return array_keys($this->adapters);
    }

    protected function getBackupAdapter(string $exclude)
    {
        foreach ($this->adapterPriority as $name) {
            if ($name !== $exclude && isset($this->adapters[$name])) return $this->adapters[$name];
        }
        return null;
    }

    protected function logSms(string $mobile, string $template, array $params, string $channel, string $status, array $result): void
    {
        Db::name('sms_log')->insert([
            'mobile' => $mobile, 'template_code' => $template,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE), 'channel' => $channel,
            'status' => $status, 'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'ip' => request()->ip(), 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
