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
    protected array $adapterPriority = ['aliyun', 'tencent', 'qiniu'];

    public function __construct()
    {
        $config = Config::get('sms', []);
        if (!empty($config['aliyun']['access_key'])) $this->adapters['aliyun'] = new AliyunSmsAdapter($config['aliyun']);
        if (!empty($config['tencent']['secret_id'])) $this->adapters['tencent'] = new TencentSmsAdapter($config['tencent']);
        if (!empty($config['qiniu']['access_key'])) $this->adapters['qiniu'] = new QiniuSmsAdapter($config['qiniu']);
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
        
        $result = $this->send($mobile, 'verify_code', ['code' => $code]);
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
        foreach ($this->adapterPriority as $name) {
            if (isset($this->adapters[$name])) return $this->adapters[$name];
        }
        return null;
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
