<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-3: 数据脱敏引擎
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Db;
use think\facade\Cache;

/**
 * 数据脱敏引擎 - V2.9.39 COMPLIANCE-3
 * 手机号/邮箱/身份证/银行卡/姓名/地址/IP/自定义脱敏 + 按场景脱敏
 */
class DataMaskEngineService
{
    protected const CACHE_TAG = 'data_mask_engine';
    protected const CACHE_TTL = 3600;

    public const TYPE_PHONE     = 'phone';
    public const TYPE_EMAIL     = 'email';
    public const TYPE_ID_CARD   = 'id_card';
    public const TYPE_BANK_CARD = 'bank_card';
    public const TYPE_NAME      = 'name';
    public const TYPE_ADDRESS   = 'address';
    public const TYPE_IP        = 'ip';
    public const TYPE_CUSTOM    = 'custom';

    public const SCENE_LIST   = 'list';
    public const SCENE_DETAIL = 'detail';
    public const SCENE_EXPORT = 'export';
    public const SCENE_LOG    = 'log';
    public const SCENE_API    = 'api';

    /**
     * 脱敏主入口
     */
    public function mask(string $type, string $value, string $scene = self::SCENE_LIST, ?array $config = null): string
    {
        if ($value === '') {
            return $value;
        }

        $rule = $this->getSceneRule($type, $scene);

        return match ($type) {
            self::TYPE_PHONE     => $this->maskPhone($value, $rule),
            self::TYPE_EMAIL     => $this->maskEmail($value, $rule),
            self::TYPE_ID_CARD   => $this->maskIdCard($value, $rule),
            self::TYPE_BANK_CARD => $this->maskBankCard($value, $rule),
            self::TYPE_NAME      => $this->maskName($value, $rule),
            self::TYPE_ADDRESS   => $this->maskAddress($value, $rule),
            self::TYPE_IP        => $this->maskIp($value, $rule),
            self::TYPE_CUSTOM    => $this->maskCustom($value, $config ?? $rule),
            default              => $value,
        };
    }

    /**
     * 批量脱敏
     */
    public function maskBatch(array $data, array $fieldMappings, string $scene = self::SCENE_LIST): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskBatch($value, $fieldMappings, $scene);
            } elseif (is_string($value) && isset($fieldMappings[$key])) {
                $config = $fieldMappings[$key];
                $type = is_array($config) ? ($config['type'] ?? self::TYPE_CUSTOM) : $config;
                $ruleConfig = is_array($config) ? $config : null;
                $data[$key] = $this->mask($type, (string) $value, $scene, $ruleConfig);
            }
        }
        return $data;
    }

    public function maskPhone(string $phone, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_PHONE);
        if (strlen($phone) < 7) {
            return $phone;
        }
        $keepStart = $rule['keep_start'] ?? 3;
        $keepEnd = $rule['keep_end'] ?? 4;
        $maskChar = $rule['mask_char'] ?? '*';
        $maskLen = strlen($phone) - $keepStart - $keepEnd;
        return substr($phone, 0, $keepStart) . str_repeat($maskChar, max(1, $maskLen)) . substr($phone, -$keepEnd);
    }

    public function maskEmail(string $email, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_EMAIL);
        $atPos = strpos($email, '@');
        if ($atPos === false || $atPos < 1) {
            return $email;
        }
        $name = substr($email, 0, $atPos);
        $domain = substr($email, $atPos);
        $keepStart = $rule['keep_start'] ?? 1;
        $maskChar = $rule['mask_char'] ?? '*';
        $maskLen = strlen($name) - $keepStart;
        return substr($name, 0, $keepStart) . str_repeat($maskChar, max(1, $maskLen)) . $domain;
    }

    public function maskIdCard(string $idCard, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_ID_CARD);
        if (strlen($idCard) < 10) {
            return $idCard;
        }
        $keepStart = $rule['keep_start'] ?? 3;
        $keepEnd = $rule['keep_end'] ?? 4;
        $maskChar = $rule['mask_char'] ?? '*';
        $maskLen = strlen($idCard) - $keepStart - $keepEnd;
        return substr($idCard, 0, $keepStart) . str_repeat($maskChar, max(1, $maskLen)) . substr($idCard, -$keepEnd);
    }

    public function maskBankCard(string $card, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_BANK_CARD);
        $card = preg_replace('/\s+/', '', $card);
        if (strlen($card) < 8) {
            return $card;
        }
        $keepStart = $rule['keep_start'] ?? 4;
        $keepEnd = $rule['keep_end'] ?? 4;
        $maskChar = $rule['mask_char'] ?? '*';
        $maskLen = strlen($card) - $keepStart - $keepEnd;
        return substr($card, 0, $keepStart) . str_repeat($maskChar, max(1, $maskLen)) . substr($card, -$keepEnd);
    }

    public function maskName(string $name, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_NAME);
        $len = mb_strlen($name, 'UTF-8');
        if ($len <= 1) {
            return $name;
        }
        $maskChar = $rule['mask_char'] ?? '*';
        if ($len === 2) {
            return mb_substr($name, 0, 1, 'UTF-8') . $maskChar;
        }
        $first = mb_substr($name, 0, 1, 'UTF-8');
        $last = mb_substr($name, -1, 1, 'UTF-8');
        return $first . str_repeat($maskChar, $len - 2) . $last;
    }

    public function maskAddress(string $address, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_ADDRESS);
        $len = mb_strlen($address, 'UTF-8');
        if ($len < 6) {
            return $address;
        }
        $keepStart = $rule['keep_start'] ?? 6;
        $maskChar = $rule['mask_char'] ?? '*';
        $maskLen = $len - $keepStart;
        return mb_substr($address, 0, $keepStart, 'UTF-8') . str_repeat($maskChar, max(1, $maskLen));
    }

    public function maskIp(string $ip, ?array $rule = null): string
    {
        $rule = $rule ?? $this->getDefaultRule(self::TYPE_IP);
        $maskChar = $rule['mask_char'] ?? '*';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[2] = $maskChar;
            $parts[3] = $maskChar;
            return implode('.', $parts);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            for ($i = 2; $i < count($parts); $i++) {
                $parts[$i] = $maskChar;
            }
            return implode(':', $parts);
        }
        return $ip;
    }

    public function maskCustom(string $value, ?array $rule = null): string
    {
        if (empty($rule)) {
            return $value;
        }
        $keepStart = $rule['keep_start'] ?? 0;
        $keepEnd = $rule['keep_end'] ?? 0;
        $maskChar = $rule['mask_char'] ?? '*';
        $fixedLength = $rule['fixed_length'] ?? 0;
        $len = strlen($value);
        $maskLen = $len - $keepStart - $keepEnd;
        if ($maskLen <= 0) {
            return str_repeat($maskChar, min($len, $fixedLength ?: $len));
        }
        $start = $keepStart > 0 ? substr($value, 0, $keepStart) : '';
        $end = $keepEnd > 0 ? substr($value, -$keepEnd) : '';
        return $start . str_repeat($maskChar, $fixedLength ?: $maskLen) . $end;
    }

    /**
     * 获取场景脱敏规则
     */
    protected function getSceneRule(string $type, string $scene): ?array
    {
        $cacheKey = 'mask_rule_' . $type . '_' . $scene;
        $rule = Cache::remember($cacheKey, function () use ($type, $scene) {
            try {
                $config = Db::name('data_mask_rule')
                    ->where('field_type', $type)
                    ->where('scene', $scene)
                    ->where('status', 1)
                    ->find();
                if ($config) {
                    return [
                        'keep_start'   => (int) $config['keep_start'],
                        'keep_end'     => (int) $config['keep_end'],
                        'mask_char'    => $config['mask_char'] ?? '*',
                        'fixed_length' => (int) ($config['fixed_length'] ?? 0),
                    ];
                }
            } catch (\Throwable) {
            }
            return null;
        }, self::CACHE_TTL);

        return $rule ?? $this->getDefaultRule($type);
    }

    /**
     * 获取默认脱敏规则
     */
    protected function getDefaultRule(string $type): array
    {
        return match ($type) {
            self::TYPE_PHONE     => ['keep_start' => 3, 'keep_end' => 4, 'mask_char' => '*'],
            self::TYPE_EMAIL     => ['keep_start' => 1, 'keep_end' => 0, 'mask_char' => '*'],
            self::TYPE_ID_CARD   => ['keep_start' => 3, 'keep_end' => 4, 'mask_char' => '*'],
            self::TYPE_BANK_CARD => ['keep_start' => 4, 'keep_end' => 4, 'mask_char' => '*'],
            self::TYPE_NAME      => ['keep_start' => 1, 'keep_end' => 1, 'mask_char' => '*'],
            self::TYPE_ADDRESS   => ['keep_start' => 6, 'keep_end' => 0, 'mask_char' => '*'],
            self::TYPE_IP        => ['keep_start' => 2, 'keep_end' => 0, 'mask_char' => '*'],
            default              => ['keep_start' => 0, 'keep_end' => 0, 'mask_char' => '*'],
        };
    }

    /**
     * 更新脱敏规则
     */
    public function updateRule(int $id, array $data): bool
    {
        $update = [];
        foreach (['field_type', 'scene', 'keep_start', 'keep_end', 'mask_char', 'fixed_length', 'status'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        if (empty($update)) {
            return false;
        }
        $result = Db::name('data_mask_rule')->where('id', $id)->update($update);
        Cache::clear();
        return $result > 0;
    }

    /**
     * 添加脱敏规则
     */
    public function addRule(array $data): int
    {
        $id = Db::name('data_mask_rule')->insertGetId([
            'field_type'   => $data['field_type'] ?? self::TYPE_PHONE,
            'scene'        => $data['scene'] ?? self::SCENE_LIST,
            'keep_start'   => (int) ($data['keep_start'] ?? 3),
            'keep_end'     => (int) ($data['keep_end'] ?? 4),
            'mask_char'    => $data['mask_char'] ?? '*',
            'fixed_length' => (int) ($data['fixed_length'] ?? 0),
            'status'       => 1,
            'create_time'  => time(),
        ]);
        Cache::clear();
        return $id;
    }

    /**
     * 获取所有脱敏规则
     */
    public function getRules(int $page = 1, int $limit = 20): array
    {
        $query = Db::name('data_mask_rule')->order('id', 'desc');
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['list' => $list, 'total' => $total, 'page' => $page];
    }

    /**
     * 自动检测并脱敏数据
     * 根据字段名自动推断类型
     */
    public function autoMask(array $data, string $scene = self::SCENE_LIST): array
    {
        $fieldTypeMap = [
            'phone'    => self::TYPE_PHONE,
            'mobile'   => self::TYPE_PHONE,
            'tel'      => self::TYPE_PHONE,
            'email'    => self::TYPE_EMAIL,
            'id_card'  => self::TYPE_ID_CARD,
            'idcard'   => self::TYPE_ID_CARD,
            'bank_card'=> self::TYPE_BANK_CARD,
            'card_no'  => self::TYPE_BANK_CARD,
            'name'     => self::TYPE_NAME,
            'realname' => self::TYPE_NAME,
            'username' => self::TYPE_NAME,
            'address'  => self::TYPE_ADDRESS,
            'addr'     => self::TYPE_ADDRESS,
            'ip'       => self::TYPE_IP,
            'ip_address' => self::TYPE_IP,
        ];

        $mappings = [];
        foreach (array_keys($data) as $key) {
            $lowerKey = strtolower((string) $key);
            if (isset($fieldTypeMap[$lowerKey])) {
                $mappings[$key] = $fieldTypeMap[$lowerKey];
            }
        }

        return $this->maskBatch($data, $mappings, $scene);
    }
}
