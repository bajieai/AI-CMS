<?php

declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Db;

/**
 * V2.9.35 SEC-3: 密码策略服务
 * 密码强度检测 + 历史限制 + 重试锁定
 */
class PasswordPolicyService
{
    /**
     * 检查密码强度
     */
    public function checkStrength(string $password): array
    {
        $config = Config::get('security.password', []);
        $errors = [];

        $minLength = $config['min_length'] ?? 8;
        if (mb_strlen($password) < $minLength) {
            $errors[] = "密码长度至少{$minLength}位";
        }

        if (!empty($config['require_case'])) {
            if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
                $errors[] = '密码必须包含大小写字母';
            }
        }

        if (!empty($config['require_number'])) {
            if (!preg_match('/\d/', $password)) {
                $errors[] = '密码必须包含数字';
            }
        }

        if (!empty($config['require_special'])) {
            if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors[] = '密码必须包含特殊字符';
            }
        }

        // 强度评分
        $score = 0;
        if (mb_strlen($password) >= 8) $score++;
        if (mb_strlen($password) >= 12) $score++;
        if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/\d/', $password)) $score++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;

        $level = match($score) {
            0, 1 => 'weak',
            2, 3 => 'medium',
            4    => 'strong',
            5    => 'very_strong',
            default => 'weak',
        };

        return [
            'passed' => empty($errors),
            'errors' => $errors,
            'score'  => $score,
            'level'  => $level,
        ];
    }

    /**
     * 检查密码是否在历史记录中
     */
    public function isInHistory(int $userId, string $password): bool
    {
        $config = Config::get('security.password', []);
        $historyCount = $config['history_count'] ?? 5;

        $member = Db::name('member')->where('id', $userId)->find();
        if (!$member || empty($member['password_history'])) {
            return false;
        }

        $history = json_decode($member['password_history'], true);
        if (!is_array($history)) {
            return false;
        }

        // 只检查最近N条
        $recentHistory = array_slice($history, -$historyCount);

        foreach ($recentHistory as $oldHash) {
            if (password_verify($password, $oldHash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 记录密码历史
     */
    public function recordPasswordHistory(int $userId, string $newPasswordHash): void
    {
        $member = Db::name('member')->where('id', $userId)->find();
        if (!$member) {
            return;
        }

        $history = [];
        if (!empty($member['password_history'])) {
            $decoded = json_decode($member['password_history'], true);
            if (is_array($decoded)) {
                $history = $decoded;
            }
        }

        // 记录当前密码哈希到历史
        if (!empty($member['password'])) {
            $history[] = $member['password'];
        }

        // 保留最近20条
        $history = array_slice($history, -20);

        Db::name('member')->where('id', $userId)->update([
            'password_history'     => json_encode($history, JSON_UNESCAPED_UNICODE),
            'password_changed_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 记录登录失败
     */
    public function recordLoginFailure(int $userId): void
    {
        $config = Config::get('security.password', []);
        $maxAttempts = $config['max_login_attempts'] ?? 5;
        $lockMinutes = $config['lock_minutes'] ?? 30;

        $member = Db::name('member')->where('id', $userId)->find();
        if (!$member) {
            return;
        }

        $attempts = ($member['login_attempts'] ?? 0) + 1;
        $updateData = ['login_attempts' => $attempts];

        if ($attempts >= $maxAttempts) {
            $updateData['locked_until'] = date('Y-m-d H:i:s', time() + $lockMinutes * 60);
        }

        Db::name('member')->where('id', $userId)->update($updateData);
    }

    /**
     * 登录成功时重置计数
     */
    public function resetLoginAttempts(int $userId): void
    {
        Db::name('member')->where('id', $userId)->update([
            'login_attempts' => 0,
            'locked_until'   => null,
        ]);
    }

    /**
     * 检查账号是否被锁定
     */
    public function isLocked(int $userId): bool
    {
        $member = Db::name('member')->where('id', $userId)->find();
        if (!$member) {
            return false;
        }

        if (empty($member['locked_until'])) {
            return false;
        }

        if (strtotime($member['locked_until']) < time()) {
            // 锁定已过期，自动解锁
            Db::name('member')->where('id', $userId)->update([
                'login_attempts' => 0,
                'locked_until'   => null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * 检查密码是否已过期
     */
    public function isExpired(int $userId): bool
    {
        $config = Config::get('security.password', []);
        $expireDays = $config['expire_days'] ?? 0;
        if ($expireDays === 0) {
            return false;
        }

        $member = Db::name('member')->where('id', $userId)->find();
        if (!$member || empty($member['password_changed_at'])) {
            return false;
        }

        $changedAt = strtotime($member['password_changed_at']);
        $expireTime = $changedAt + ($expireDays * 86400);

        return time() > $expireTime;
    }
}
