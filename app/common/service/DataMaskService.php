<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;

/**
 * V2.9.35 SEC-3: 数据脱敏服务
 * 手机号/邮箱/身份证/IP/银行卡脱敏
 */
class DataMaskService
{
    /**
     * 脱敏手机号: 138****8888
     */
    public function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    /**
     * 脱敏邮箱: z***@example.com
     */
    public function maskEmail(string $email): string
    {
        $atPos = strpos($email, '@');
        if ($atPos === false || $atPos < 1) {
            return $email;
        }
        $name = substr($email, 0, $atPos);
        $domain = substr($email, $atPos);
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, strlen($name) - 1));
        return $maskedName . $domain;
    }

    /**
     * 脱敏身份证: 420***********1234
     */
    public function maskIdCard(string $idCard): string
    {
        if (strlen($idCard) < 10) {
            return $idCard;
        }
        return substr($idCard, 0, 3) . str_repeat('*', strlen($idCard) - 7) . substr($idCard, -4);
    }

    /**
     * 脱敏IP: 192.168.1.***
     */
    public function maskIp(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '***';
            return implode('.', $parts);
        }
        return $ip;
    }

    /**
     * 脱敏银行卡: 6222****1234
     */
    public function maskBankCard(string $card): string
    {
        if (strlen($card) < 8) {
            return $card;
        }
        return substr($card, 0, 4) . str_repeat('*', strlen($card) - 8) . substr($card, -4);
    }

    /**
     * 根据配置自动脱敏
     */
    public function autoMask(string $type, string $value): string
    {
        $config = Config::get('security.data_mask', []);

        if (empty($config[$type])) {
            return $value;
        }

        return match($type) {
            'phone'     => $this->maskPhone($value),
            'email'     => $this->maskEmail($value),
            'id_card'   => $this->maskIdCard($value),
            'ip'        => $this->maskIp($value),
            'bank_card' => $this->maskBankCard($value),
            default     => $value,
        };
    }

    /**
     * 批量脱敏数据
     */
    public function maskBatch(array $data, array $fieldMap): array
    {
        foreach ($fieldMap as $field => $type) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->autoMask($type, $data[$field]);
            }
        }
        return $data;
    }
}
