<?php
declare(strict_types=1);

namespace app\common\service\agent;

use think\facade\Db;
use think\facade\Cache;

/**
 * AI 助手 · 机器身份服务（ACP-05 / R-4）
 *
 * 依据 动作层规格 v1.2 §4.3：
 *  - 复用 `i8j_api_key`（scopes / ip_whitelist / rate_limit / is_active 四要素齐备）
 *  - 必须支持：签发 / 吊销 / scope 白名单 / IP 白名单 / 限流
 *  - 默认 IP 白名单含 127.0.0.1
 *  - 高危 scope 默认不授予（HIGH_RISK_SCOPES 一律拒绝签发）
 *
 * S-1 硬要求：机器身份独立、可吊销，禁止把管理员会话 Cookie 给外部工具。
 * 本服务只签发独立 key，不读取/不接受任何后台会话凭证。
 */
class AgentTokenService
{
    /** 默认 IP 白名单（本机回环） */
    public const DEFAULT_IP_WHITELIST = ['127.0.0.1', '::1'];

    /** 默认 scope 集合（R-4：不含 high_risk，不含 mockdata） */
    public const DEFAULT_SCOPES = 'aicms:*:read';

    /**
     * 签发机器身份
     *
     * @param array $opt name / scopes(string|array) / ip_whitelist(string|array) / rate_limit / remark
     * @return array{success:bool,message:string,api_key?:string,api_secret?:string,id?:int}
     */
    public function issue(array $opt = []): array
    {
        $name = trim((string)($opt['name'] ?? '')) ?: ('agent-' . date('YmdHis'));
        $rateLimit = (int)($opt['rate_limit'] ?? (int)config('api.default_rate_limit', 100));
        if ($rateLimit <= 0) {
            $rateLimit = 100;
        }

        // scope 规范化 + 高危拒绝
        $scopes = ScopeMatcher::parse($opt['scopes'] ?? self::DEFAULT_SCOPES);
        if ($scopes === []) {
            $scopes = ScopeMatcher::parse(self::DEFAULT_SCOPES);
        }
        foreach ($scopes as $s) {
            if (!ScopeMatcher::isValidFormat($s)) {
                return ['success' => false, 'message' => "scope 格式非法（R-4 三段式）：{$s}"];
            }
            if (in_array($s, ScopeMatcher::HIGH_RISK_SCOPES, true)) {
                return ['success' => false, 'message' => "高危 scope 默认不授予，须逐条审批：{$s}"];
            }
            // 中间通配禁止（aicms:*:非读类）
            if (preg_match('/^[a-z][a-z0-9_]*:\*:([a-z][a-z0-9_]*)$/', $s, $m)) {
                if (!in_array($m[1], ScopeMatcher::READ_VERBS, true) && $m[1] !== '*') {
                    return ['success' => false, 'message' => "禁止中间通配（R-4）：{$s}"];
                }
            }
        }

        // IP 白名单（默认含回环）
        $ips = $this->normalizeIps($opt['ip_whitelist'] ?? self::DEFAULT_IP_WHITELIST);
        if ($ips === []) {
            $ips = self::DEFAULT_IP_WHITELIST;
        }

        $apiKey = 'ak_' . bin2hex(random_bytes(16));
        $apiSecret = bin2hex(random_bytes(32));

        try {
            $id = (int)Db::name('api_key')->insertGetId([
                'name' => mb_substr($name, 0, 100),
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'scopes' => implode(',', $scopes),
                'ip_whitelist' => implode(',', $ips),
                'rate_limit' => $rateLimit,
                'is_active' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '签发失败：' . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => '签发成功（api_secret 仅本次返回，请立即保存）',
            'id' => $id,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'scopes' => implode(',', $scopes),
            'ip_whitelist' => implode(',', $ips),
            'rate_limit' => $rateLimit,
        ];
    }

    /** 吊销（软删：is_active=0），立即生效 */
    public function revoke(int $id): bool
    {
        try {
            return Db::name('api_key')->where('id', $id)->update([
                'is_active' => 0,
                'update_time' => time(),
            ]) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 校验机器身份（鉴权 + IP 白名单 + 限流）
     *
     * @return array{ok:bool,code:int,message:string,key:?array}
     */
    public function verify(string $apiKey, string $ip = ''): array
    {
        if ($apiKey === '') {
            return ['ok' => false, 'code' => 401, 'message' => '缺少机器身份（api_key）', 'key' => null];
        }

        try {
            $key = Db::name('api_key')->where('api_key', $apiKey)->find();
        } catch (\Throwable $e) {
            return ['ok' => false, 'code' => 500, 'message' => '身份校验失败', 'key' => null];
        }

        if (!$key) {
            return ['ok' => false, 'code' => 401, 'message' => '身份无效', 'key' => null];
        }
        if ((int)$key['is_active'] !== 1) {
            return ['ok' => false, 'code' => 401, 'message' => '身份已被吊销', 'key' => null];
        }

        // S-1 + R-4：IP 白名单
        $ips = $this->normalizeIps($key['ip_whitelist'] ?? '');
        if ($ips !== [] && !in_array($ip, $ips, true)) {
            return ['ok' => false, 'code' => 403, 'message' => '来源 IP 不在白名单内', 'key' => null];
        }

        // S-7：限流
        $limit = (int)($key['rate_limit'] ?? 100);
        if (!$this->allowRate((int)$key['id'], $limit)) {
            return ['ok' => false, 'code' => 429, 'message' => "超出限流（{$limit}/分钟）", 'key' => null];
        }

        $key['scopes_arr'] = ScopeMatcher::parse($key['scopes'] ?? '');

        return ['ok' => true, 'code' => 200, 'message' => 'ok', 'key' => $key];
    }

    /** 列出已签发身份（不返回 secret） */
    public function list(): array
    {
        try {
            return Db::name('api_key')
                ->field('id,name,api_key,scopes,ip_whitelist,rate_limit,is_active,create_time')
                ->order('id desc')
                ->select()
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** IP 白名单规范化 */
    private function normalizeIps($raw): array
    {
        $list = is_array($raw) ? $raw : explode(',', (string)$raw);
        $out = [];
        foreach ($list as $ip) {
            $v = trim((string)$ip);
            if ($v !== '' && filter_var($v, FILTER_VALIDATE_IP) !== false) {
                $out[] = $v;
            }
        }
        return array_values(array_unique($out));
    }

    /** 限流计数（分钟窗口） */
    private function allowRate(int $keyId, int $limit): bool
    {
        if ($limit <= 0) {
            return true;
        }
        $cacheKey = 'agent_token_rate_' . $keyId . '_' . date('YmdHi');
        try {
            $n = (int)Cache::get($cacheKey, 0);
            if ($n >= $limit) {
                return false;
            }
            Cache::set($cacheKey, $n + 1, 70);
            return true;
        } catch (\Throwable $e) {
            return true;
        }
    }
}
