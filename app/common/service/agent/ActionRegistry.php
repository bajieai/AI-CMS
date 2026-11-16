<?php
declare(strict_types=1);

namespace app\common\service\agent;

use think\facade\Db;
use think\facade\Log;

/**
 * AI 助手动作注册表（ACP-03 / V2.9.63）
 *
 * 依据：
 *  - codearts-task-ai-control-plane-2026-11-10.md v1.2 §4.1 / §4.2 / §4.12（R-1 默认基线）
 *  - V2.9.63-AI助手-开发计划-v1.1 §五 A-4（代码注册为主 + DB 覆盖为辅，合并规则写死）
 *
 * A-4 合并规则（裁定件强制）：
 *  - 代码定义动作的【存在性 + schema】：唯一权威
 *  - DB 可覆盖字段：【仅】 enabled / timeout / rate_limit / remark 四项
 *  - DB 出现代码中不存在的 action_id：启动即报错（防幽灵动作）
 *  - 代码中动作在 DB 无记录：按代码默认值启用，不报错
 *  - GET /actions 返回合并后的实际可调用集合，enabled=0 不出现
 *
 * RD-4 防呆：24 个 action_id 冻结，注册数量或拼写不符抛异常（代码拒绝启动）
 * RD-5 防呆：W1 仅只读，risk_level 只允许 read 三态；注册 write/high_risk 抛异常
 */
class ActionRegistry
{
    /** R-1 原生 ID 正则（2~3 段） */
    public const NATIVE_ID_REGEX = '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*){1,2}$/';

    /** R-1 对外 FQN 正则（3~4 段 = product + native） */
    public const FQN_REGEX = '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*){2,3}$/';

    /** R-1 product 前缀 */
    public const PRODUCT = 'aicms';

    /** R-1 domain 12 个 */
    public const DOMAINS = ['ai', 'api', 'config', 'content', 'member', 'mockdata', 'page', 'report', 'stat', 'system', 'template', 'theme'];

    /** R-1 verb 16 个（11 现有 + 5 W3 预留） */
    public const VERBS = ['batch_create', 'context', 'detail', 'get', 'health', 'history', 'list', 'overview', 'read', 'tree', 'version', 'create', 'delete', 'install', 'publish', 'update'];

    /** R-1 两段 ID 仅允许的 domain */
    public const TWO_SEGMENT_DOMAINS = ['system', 'member', 'config', 'stat', 'page'];

    /** RD-4 冻结的 24 个 action_id（codearts-task §4.2 全集，一个不改） */
    public const FROZEN_IDS = [
        'content.article.list', 'content.article.read', 'content.category.tree',
        'template.store.list', 'template.store.detail', 'template.install.list', 'template.license.list',
        'theme.market.list', 'theme.record.list', 'theme.record.detail', 'theme.palette.list', 'theme.version.history',
        'member.list', 'member.detail',
        'config.get', 'system.version', 'system.health',
        'ai.log.list', 'api.log.list', 'stat.overview', 'report.definition.list', 'page.context',
        'mockdata.content.batch_create', 'mockdata.template.batch_create',
    ];

    /** RD-5：W1 只允许 read */
    public const ALLOWED_RISK_LEVELS = ['read'];

    /** A-4：DB 仅可覆盖这四项 */
    private const DB_OVERRIDABLE = ['enabled', 'timeout', 'rate_limit', 'remark'];

    /** @var array<string, array> actionId => definition */
    private static array $actions = [];

    /** @var bool 合并是否已完成 */
    private static bool $merged = false;

    /**
     * 注册动作定义（由 actions.php 在启动时调用）
     * RD-4/RD-5 校验失败直接抛异常（代码拒绝启动）
     *
     * @param array $def 完整定义（含 input_schema/output_schema/field_map/source_table 等）
     * @throws \RuntimeException
     */
    public static function register(array $def): void
    {
        $actionId = $def['action_id'] ?? '';

        // RD-5：W1 拒绝任何 write/high_risk 注册（配置开关 allow_write=false）
        $risk = $def['risk_level'] ?? 'read';
        if (!in_array($risk, self::ALLOWED_RISK_LEVELS, true)) {
            throw new \RuntimeException(sprintf(
                '[RD-5] ActionRegistry 拒绝注册 risk_level=%s 的动作 %s（W1 仅只读）',
                $risk,
                $actionId
            ));
        }

        // RD-4：只允许冻结清单内的 ID
        if (!in_array($actionId, self::FROZEN_IDS, true)) {
            throw new \RuntimeException(sprintf(
                '[RD-4] ActionRegistry 拒绝注册未冻结的 action_id=%s（24 个 ID 一个不改）',
                $actionId
            ));
        }

        // R-1：正则 + domain/verb 枚举
        $err = self::validateId($actionId);
        if ($err !== null) {
            throw new \RuntimeException(sprintf('[R-1] action_id=%s 校验失败：%s', $actionId, $err));
        }

        // scope 格式（R-4 三段冒号）
        $scope = $def['scope_required'] ?? '';
        if (!preg_match('/^aicms:[a-z][a-z0-9_]*:(\*|[a-z][a-z0-9_]*)$/', $scope)) {
            throw new \RuntimeException(sprintf('[R-4] action_id=%s 的 scope=%s 不符合三段冒号格式', $actionId, $scope));
        }

        self::$actions[$actionId] = $def;
    }

    /**
     * R-1 校验：返回 null 表示合法，否则返回错误描述
     */
    public static function validateId(string $actionId): ?string
    {
        if (!preg_match(self::NATIVE_ID_REGEX, $actionId)) {
            return '不符合 2~3 段正则 ' . self::NATIVE_ID_REGEX;
        }
        $parts = explode('.', $actionId);
        $domain = $parts[0];
        $verb = $parts[count($parts) - 1];

        if (!in_array($domain, self::DOMAINS, true)) {
            return sprintf('domain=%s 不在 12 个枚举内', $domain);
        }
        if (!in_array($verb, self::VERBS, true)) {
            return sprintf('verb=%s 不在 16 个枚举内', $verb);
        }
        if (count($parts) === 2 && !in_array($domain, self::TWO_SEGMENT_DOMAINS, true)) {
            return sprintf('两段 ID 仅允许 domain ∈ {%s}，实际 %s', implode(',', self::TWO_SEGMENT_DOMAINS), $domain);
        }
        return null;
    }

    /** 对外 FQN = <product>.<native_id> */
    public static function toFqn(string $nativeId): string
    {
        $fqn = self::PRODUCT . '.' . $nativeId;
        if (!preg_match(self::FQN_REGEX, $fqn)) {
            throw new \RuntimeException(sprintf('[R-1] FQN=%s 不符合 3~4 段正则', $fqn));
        }
        return $fqn;
    }

    /** FQN → native_id（MCP tool 名映射的可逆过程） */
    public static function fromFqn(string $fqn): string
    {
        if (!str_starts_with($fqn, self::PRODUCT . '.')) {
            throw new \RuntimeException(sprintf('[R-1] FQN=%s 缺少 %s 前缀', $fqn, self::PRODUCT));
        }
        return substr($fqn, strlen(self::PRODUCT) + 1);
    }

    /**
     * 启动合并：冻结清单比对 + DB 可变项覆盖（A-4 合并规则）
     * 必须在枚举/执行前调用一次
     */
    public static function boot(): void
    {
        if (self::$merged) {
            return;
        }

        // 尚未注册任何动作时，加载代码侧定义文件
        if (self::$actions === []) {
            self::loadDefinitions();
        }

        // RD-4：冻结清单与实际注册比对（数量 + 拼写，双向）
        $registered = array_keys(self::$actions);
        $missing = array_diff(self::FROZEN_IDS, $registered);
        $extra = array_diff($registered, self::FROZEN_IDS);
        if ($missing !== [] || $extra !== []) {
            throw new \RuntimeException(sprintf(
                '[RD-4] 冻结清单比对失败：缺注册=%s 多注册=%s（应注册 %d 个）',
                implode(',', $missing) ?: '无',
                implode(',', $extra) ?: '无',
                count(self::FROZEN_IDS)
            ));
        }

        // A-4：DB 只覆盖 enabled/timeout/rate_limit/remark；DB 出现代码没有的 ID → 报错
        try {
            $rows = Db::name('agent_action')->column('action_id');
            $ghost = array_diff($rows, self::FROZEN_IDS);
            if ($ghost !== []) {
                throw new \RuntimeException(sprintf('[A-4] DB 存在代码未注册的幽灵动作：%s', implode(',', $ghost)));
            }

            $overridable = Db::name('agent_action')
                ->whereIn('action_id', self::FROZEN_IDS)
                ->column('action_id,enabled,timeout,rate_limit,remark', 'action_id');
            foreach ($overridable as $actionId => $row) {
                if (!isset(self::$actions[$actionId])) {
                    continue;
                }
                foreach (self::DB_OVERRIDABLE as $field) {
                    if (array_key_exists($field, $row)) {
                        self::$actions[$actionId][$field] = $row[$field];
                    }
                }
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // 表不存在等基础设施问题：降级为纯代码模式并记录，不阻塞只读能力发现
            Log::warning('ActionRegistry DB merge degraded: ' . $e->getMessage());
        }

        self::$merged = true;
    }

    /** 加载代码侧 24 个动作定义 */
    private static function loadDefinitions(): void
    {
        $file = __DIR__ . '/actions.php';
        if (!is_file($file)) {
            throw new \RuntimeException('[RD-4] 动作定义文件缺失：' . $file);
        }
        $definitions = require $file;
        if (!is_array($definitions)) {
            throw new \RuntimeException('[RD-4] 动作定义文件必须返回数组：' . $file);
        }
        foreach ($definitions as $def) {
            self::register($def);
        }
    }

    /**
     * 枚举全部可调用动作（enabled=0 不出现；A-4 合并规则）
     * GET /actions 与 CLI agent:actions 与 MCP tools/list 共用本方法（§4.1 硬要求）
     */
    public static function all(bool $withSchema = true): array
    {
        self::boot();
        $list = [];
        foreach (self::$actions as $actionId => $def) {
            if (empty($def['enabled'])) {
                continue;
            }
            $item = [
                'action_id'   => $actionId,
                'fqn'         => self::toFqn($actionId),
                'display_name' => $def['display_name'] ?? '',
                'description' => $def['description'] ?? '',
                'scope'       => $def['scope_required'],
                'risk_level'  => $def['risk_level'] ?? 'read',
                'rate_limit'  => (int)($def['rate_limit'] ?? 60),
                'timeout'     => (int)($def['timeout'] ?? 10),
                'source_table' => $def['source_table'] ?? '',
                'data_origin' => $def['data_origin'] ?? 'real',
            ];
            if ($withSchema) {
                $item['input_schema'] = $def['input_schema'] ?? ['type' => 'object', 'properties' => new \stdClass()];
                $item['output_schema'] = $def['output_schema'] ?? ['type' => 'object'];
                if (!empty($def['field_map'])) {
                    $item['field_map'] = $def['field_map'];
                }
            }
            $list[] = $item;
        }
        return $list;
    }

    /** 取单个动作定义（含 DB 覆盖），不存在或被禁用返回 null */
    public static function get(string $actionId): ?array
    {
        self::boot();
        $def = self::$actions[$actionId] ?? null;
        if ($def === null || empty($def['enabled'])) {
            return null;
        }
        return $def;
    }

    /** 当前注册数量（测试用） */
    public static function count(): int
    {
        self::boot();
        return count(self::$actions);
    }

    /** 重置（仅测试用） */
    public static function reset(): void
    {
        self::$actions = [];
        self::$merged = false;
    }
}
