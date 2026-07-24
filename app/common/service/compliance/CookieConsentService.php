<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.39 COMPLIANCE-1: Cookie 同意管理服务
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\compliance;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * Cookie 同意管理服务 - V2.9.39 COMPLIANCE-1
 * Cookie横幅管理 + 同意记录 + Cookie分类
 */
class CookieConsentService
{
    protected const CACHE_TAG = 'cookie_consent';
    protected const CACHE_TTL = 86400;

    /**
     * Cookie分类
     */
    public const CATEGORY_NECESSARY   = 'necessary';    // 必需Cookie
    public const CATEGORY_PREFERENCES = 'preferences';  // 偏好Cookie
    public const CATEGORY_STATISTICS  = 'statistics';   // 统计Cookie
    public const CATEGORY_MARKETING   = 'marketing';    // 营销Cookie

    /**
     * 获取Cookie分类列表
     */
    public function getCategories(): array
    {
        return [
            [
                'key'         => self::CATEGORY_NECESSARY,
                'name'        => '必需Cookie',
                'description' => '网站正常运行所必需的Cookie，无法禁用',
                'required'    => true,
                'cookies'     => $this->getCookiesByCategory(self::CATEGORY_NECESSARY),
            ],
            [
                'key'         => self::CATEGORY_PREFERENCES,
                'name'        => '偏好Cookie',
                'description' => '记住您的偏好设置，如语言、主题等',
                'required'    => false,
                'cookies'     => $this->getCookiesByCategory(self::CATEGORY_PREFERENCES),
            ],
            [
                'key'         => self::CATEGORY_STATISTICS,
                'name'        => '统计Cookie',
                'description' => '匿名收集访问统计信息，帮助我们改进网站',
                'required'    => false,
                'cookies'     => $this->getCookiesByCategory(self::CATEGORY_STATISTICS),
            ],
            [
                'key'         => self::CATEGORY_MARKETING,
                'name'        => '营销Cookie',
                'description' => '用于跟踪您的浏览行为以投放相关广告',
                'required'    => false,
                'cookies'     => $this->getCookiesByCategory(self::CATEGORY_MARKETING),
            ],
        ];
    }

    /**
     * 获取分类下的Cookie列表
     */
    public function getCookiesByCategory(string $category): array
    {
        return Cache::remember('cookies_' . $category, function () use ($category) {
            try {
                return Db::name('cookie_definition')
                    ->where('category', $category)
                    ->where('status', 1)
                    ->select()
                    ->toArray();
            } catch (\Throwable) {
                // 表可能尚未创建
                return [];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 记录用户Cookie同意
     */
    public function recordConsent(int $userId, array $consents, string $ipAddress = '', string $userAgent = ''): array
    {
        $now = time();
        $rows = [];

        foreach ($consents as $category => $granted) {
            $rows[] = [
                'user_id'     => $userId,
                'category'    => $category,
                'consent_given' => $granted ? 1 : 0,
                'ip_address'  => $ipAddress ?: request()->ip(),
                'user_agent'  => substr($userAgent ?: request()->header('user-agent', ''), 0, 255),
                'create_time' => $now,
            ];
        }

        if (!empty($rows)) {
            try {
                Db::name('cookie_consent_log')->insertAll($rows);
            } catch (\Throwable $e) {
                Log::error('[CookieConsent] 记录失败', ['error' => $e->getMessage()]);
            }
        }

        // 设置Cookie记住用户选择（有效期1年）
        $cookieData = json_encode($consents, JSON_UNESCAPED_UNICODE);
        cookie('cookie_consent', base64_encode($cookieData), 86400 * 365);

        return ['success' => true, 'consents' => $consents];
    }

    /**
     * 获取用户Cookie同意记录
     */
    public function getUserConsent(int $userId): array
    {
        $cacheKey = 'cookie_consent_user_' . $userId;

        return Cache::remember($cacheKey, function () use ($userId) {
            try {
                $records = Db::name('cookie_consent_log')
                    ->where('user_id', $userId)
                    ->order('create_time', 'desc')
                    ->limit(count($this->getCategories()))
                    ->select()
                    ->toArray();

                $consents = [];
                foreach ($records as $record) {
                    if (!isset($consents[$record['category']])) {
                        $consents[$record['category']] = (int) $record['consent_given'];
                    }
                }

                return $consents;
            } catch (\Throwable) {
                return [];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 检查某个分类是否已获同意
     */
    public function hasConsent(int $userId, string $category): bool
    {
        if ($category === self::CATEGORY_NECESSARY) {
            return true; // 必需Cookie默认同意
        }

        $consents = $this->getUserConsent($userId);
        return ($consents[$category] ?? 0) === 1;
    }

    /**
     * 从Cookie中获取同意状态（未登录用户）
     */
    public function getConsentFromCookie(): array
    {
        $cookieValue = cookie('cookie_consent');
        if (empty($cookieValue)) {
            return [];
        }

        $decoded = base64_decode($cookieValue, true);
        if ($decoded === false) {
            return [];
        }

        $consents = json_decode($decoded, true);
        return is_array($consents) ? $consents : [];
    }

    /**
     * 检查当前请求是否已同意（综合Cookie和数据库）
     */
    public function checkConsent(?int $userId = null, string $category = self::CATEGORY_STATISTICS): bool
    {
        if ($category === self::CATEGORY_NECESSARY) {
            return true;
        }

        // 先检查Cookie
        $cookieConsents = $this->getConsentFromCookie();
        if (isset($cookieConsents[$category])) {
            return (bool) $cookieConsents[$category];
        }

        // 再检查数据库
        if ($userId && $userId > 0) {
            return $this->hasConsent($userId, $category);
        }

        return false;
    }

    /**
     * 获取Cookie同意统计
     */
    public function getConsentStats(): array
    {
        return Cache::remember('cookie_consent_stats', function () {
            try {
                $total = Db::name('cookie_consent_log')
                    ->field('COUNT(DISTINCT user_id) as total_users')
                    ->find();

                $byCategory = Db::name('cookie_consent_log')
                    ->field('category, SUM(consent_given) as granted, COUNT(*) as total')
                    ->group('category')
                    ->select()
                    ->toArray();

                // 最近30天趋势
                $recent = Db::name('cookie_consent_log')
                    ->where('create_time', '>=', time() - 86400 * 30)
                    ->field('from_unixtime(create_time, "%Y-%m-%d") as date, COUNT(DISTINCT user_id) as users')
                    ->group('date')
                    ->order('date', 'asc')
                    ->select()
                    ->toArray();

                return [
                    'total_users' => $total['total_users'] ?? 0,
                    'by_category' => $byCategory,
                    'recent_trend' => $recent,
                ];
            } catch (\Throwable) {
                return ['total_users' => 0, 'by_category' => [], 'recent_trend' => []];
            }
        }, self::CACHE_TTL);
    }

    /**
     * 更新Cookie定义
     */
    public function updateCookieDefinition(int $id, array $data): bool
    {
        $update = [];
        foreach (['name', 'description', 'category', 'provider', 'expiry', 'status'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }

        if (empty($update)) {
            return false;
        }

        $result = Db::name('cookie_definition')->where('id', $id)->update($update);
        Cache::clear();

        return $result > 0;
    }

    /**
     * 添加Cookie定义
     */
    public function addCookieDefinition(array $data): int
    {
        $id = Db::name('cookie_definition')->insertGetId([
            'name'        => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'category'    => $data['category'] ?? self::CATEGORY_NECESSARY,
            'provider'    => $data['provider'] ?? '',
            'expiry'      => $data['expiry'] ?? '',
            'status'      => 1,
            'create_time' => time(),
        ]);

        Cache::clear();

        return $id;
    }
}
