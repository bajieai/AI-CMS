<?php
declare(strict_types=1);

namespace app\common\service\agent;

/**
 * API Scope 统一判定器（V2.9.63 / R-4 裁定）
 *
 * 依据：动作层规格 v1.2 §4.3 R-4
 *  - 格式：<product>:<domain>:<verb> 三段冒号（与 action_id 点分层区分）
 *  - 存储：逗号分隔字符串（同步修正 install.sql:660 的「JSON数组」注释）
 *  - 通配：仅后缀合法（aicms:theme:*）；中间通配 `aicms:*:publish` 禁止；
 *          保留 `admin:all` 作兼容别名；`aicms:*:read` 为官方默认聚合授（读类专用）
 *  - high_risk 逐条单列：通配展开时必须显式排除，不得被通配覆盖
 *
 * 兼容（D-5 裁定 a）：存量可能存 JSON 数组或旧两段格式 `content:read`，解析与匹配双层兼容。
 * 兼容层保留至 V2.9.65，届时移除（见本类 TODO 标记）。
 */
class ScopeMatcher
{
    /** 读类 verb（`aicms:*:<read-verb>` 聚合授合法） */
    public const READ_VERBS = ['read', 'list', 'detail', 'tree', 'overview', 'get', 'health', 'version', 'history', 'context'];

    /** 兼容别名 */
    public const COMPAT_ALL = 'admin:all';

    /**
     * high_risk scope 清单（W1 为空；W3 引入写/高危动作后在此登记，通配不得覆盖）
     * 形如 'aicms:config:write'
     */
    public const HIGH_RISK_SCOPES = [];

    /** 默认授权集合（R-4：不含 high_risk；mockdata 单独授权） */
    public const DEFAULT_GRANT = 'aicms:*:read';

    /**
     * 解析 scopes 存储值为数组（双格式兼容）
     *
     * @param string|array|null $raw
     * @return string[]
     */
    public static function parse($raw): array
    {
        if (is_array($raw)) {
            $list = $raw;
        } else {
            $s = trim((string)$raw);
            if ($s === '') {
                return [];
            }
            // ① 优先按逗号分隔（R-4 规定格式）
            if (strpos($s, ',') !== false) {
                $list = explode(',', $s);
            } elseif (str_starts_with($s, '[')) {
                // ② JSON 数组兜底（兼容 install.sql 旧注释所暗示的历史写法）
                $decoded = json_decode($s, true);
                $list = is_array($decoded) ? $decoded : explode(',', $s);
            } else {
                $list = [$s];
            }
        }

        $out = [];
        foreach ($list as $item) {
            $v = trim((string)$item);
            // 剔除 JSON 数组内层的引号/方括号残留
            $v = trim($v, " \t\n\r\0\x0B\"'[]");
            if ($v !== '') {
                $out[] = $v;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * 判定是否具备所需 scope
     *
     * @param string|array|null $granted 已授权 scopes（原始存储值或数组）
     * @param string            $required 形如 aicms:content:read，或旧格式 content:read
     */
    public static function allows($granted, string $required): bool
    {
        $required = trim($required);
        if ($required === '') {
            return false;
        }

        $scopes = self::parse($granted);
        if ($scopes === []) {
            return false;
        }

        foreach ($scopes as $scope) {
            if (self::matchOne($scope, $required)) {
                return true;
            }
        }
        return false;
    }

    /** 单条授权是否覆盖所需 scope */
    private static function matchOne(string $granted, string $required): bool
    {
        // 兼容别名
        if ($granted === self::COMPAT_ALL || $granted === '*') {
            return true;
        }
        if ($granted === $required) {
            return true;
        }

        $g = explode(':', $granted);
        $r = explode(':', $required);

        // 仅支持 2 段（旧格式）或 3 段（标准格式）
        if (count($g) < 2 || count($g) > 3 || count($r) < 2 || count($r) > 3) {
            return false;
        }

        // 归一化为 [product|null, domain, verb]
        [$gProduct, $gDomain, $gVerb] = self::normalize($g);
        [$rProduct, $rDomain, $rVerb] = self::normalize($r);

        // product 必须一致（旧格式无 product，视为通配）
        if ($gProduct !== null && $rProduct !== null && $gProduct !== $rProduct) {
            return false;
        }

        // domain 匹配
        $domainOk = ($gDomain === '*' || $gDomain === $rDomain);
        if (!$domainOk) {
            return false;
        }

        // verb 匹配
        // R-4 默认集合 `aicms:*:read` 应覆盖全部只读能力，
        // 故 `read` 视为「读类聚合标识」：可覆盖 list/detail/tree/overview/get/health/version/history/context。
        $verbOk = ($gVerb === '*' || $gVerb === $rVerb)
            || ($gVerb === 'read' && in_array($rVerb, self::READ_VERBS, true));
        if (!$verbOk) {
            return false;
        }

        // R-4 中间通配规则：domain 位为 '*' 且 verb 非读类 → 非法授权，不生效
        if ($gDomain === '*' && !in_array($gVerb, self::READ_VERBS, true) && $gVerb !== '*') {
            return false;
        }

        // R-4 high_risk 不被通配覆盖：所需 scope 在 high_risk 清单时，必须逐条精确授权
        if (($gVerb === '*' || $gDomain === '*') && in_array($required, self::HIGH_RISK_SCOPES, true)) {
            return false;
        }

        return true;
    }

    /**
     * 归一化 scope 片段
     * @param string[] $parts
     * @return array{0:string|null,1:string,2:string}
     */
    private static function normalize(array $parts): array
    {
        if (count($parts) === 3) {
            return [$parts[0], $parts[1], $parts[2]];
        }
        return [null, $parts[0], $parts[1]];
    }

    /**
     * 展开通配授权为具体 scope 列表（显式排除 high_risk）
     *
     * @param string|array|null $granted
     * @param string[]          $allScopes 系统内全部 scope（动作 scope 全集）
     * @return string[]
     */
    public static function expand($granted, array $allScopes): array
    {
        $out = [];
        foreach ($allScopes as $scope) {
            if (in_array($scope, self::HIGH_RISK_SCOPES, true)) {
                continue; // 通配展开永不包含 high_risk
            }
            if (self::allows($granted, $scope)) {
                $out[] = $scope;
            }
        }
        return array_values(array_unique($out));
    }

    /** 校验 scope 字面量是否符合 R-4 格式（供后台表单/CLI 校验用） */
    public static function isValidFormat(string $scope): bool
    {
        if ($scope === self::COMPAT_ALL || $scope === '*') {
            return true;
        }
        // 三段：product:domain:verb|*
        // R-4：domain 位允许 '*'（官方默认聚合授 aicms:*:read），其合法性由
        // matchOne() 的「中间通配仅读类生效」规则与 AgentTokenService 签发校验共同裁决。
        if (preg_match('/^[a-z][a-z0-9_]*:(\*|[a-z][a-z0-9_]*):(\*|[a-z][a-z0-9_]*)$/', $scope)) {
            return true;
        }
        // 两段（旧格式）：domain:verb|*
        if (preg_match('/^[a-z][a-z0-9_]*:(\*|[a-z][a-z0-9_]*)$/', $scope)) {
            return true;
        }
        return false;
    }
}
