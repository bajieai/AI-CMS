<?php
declare(strict_types=1);

namespace app\common\service\agent;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * AI 助手 · 确定性意图路由（S3-T2 / S3-T3 · R-2 默认基线）
 *
 * 依据：
 *  - S-19《W1 规格补充》§3.1 七步算法（白名单 Q-01~Q-20，零模型可达，P-2）
 *  - 动作层规格 v1.2 §4.11 / §4.12 R-2：本类为独立共享组件，四通道（窗口/REST/CLI/MCP）都调它；
 *    只做「问法 → action_id」匹配，执行统一归 ActionRegistry（单一职责）
 *  - R-6 白名单永远第一：匹配顺序不可协商；严禁 LLM 参与匹配（W2 增强也只在未命中后）
 *
 * 数据源（R-2）：`i8j_agent_action_alias` 为准（pattern/action_id/params_rule/q_id/priority/enabled）；
 * `config/agent_whitelist.php` 仅作 seed —— 别名表为空时自动播种，此后 DB 增删即运行时权威。
 *
 * 匹配顺序（S-19 §3.1，先命中先返回）：
 *   ① 归一化（全角转半角、去空白；繁转简留待引入词库后启用）
 *   ② 写意图检测（命中写动词 → 引导 CLI，不进白名单）
 *   ③ 关键词匹配（含即命中）→ ④ 多命中按关键词长度降序消歧（更具体者优先）
 *   ⑤ 参数抽取（id / keyword / limit / current_path）
 *   ⑥ 必填校验（Registry input_schema.required；Q-20 特例走 current_path 上下文）
 *   ⑦ 返回路由决定（执行与渲染归调用方）
 */
class IntentRouter
{
    /** answer_type 枚举（S-19 §3.3） */
    public const TYPE_HIT = 'whitelist_hit';
    public const TYPE_MISS = 'whitelist_miss';
    public const TYPE_PARAM_MISSING = 'param_missing';
    public const TYPE_NOT_IMPLEMENTED = 'not_implemented';
    public const TYPE_WRITE_INTENT = 'write_intent';

    /** seed 播种结果缓存键（别名表为空时播种一次） */
    private const CACHE_SEED_CHECK = 'agent_alias_seed_checked';

    /** 话术（S-19 §4 一字不改；{missing_field}/{example}/{q_id}/{safe_error}/{request_id}/{guess_action} 为占位符） */
    private array $phrases = [];

    /** 写意图关键词 */
    private array $writeIntentKeywords = [];

    /** Q-20 缺上下文返回码 */
    private string $q20NoCtx = 'Q-20-NO-CTX';

    /** 中文数字 → 阿拉伯数字（limit 抽取用） */
    private const CN_NUMBERS = [
        '一' => 1, '二' => 2, '两' => 2, '三' => 3, '四' => 4, '五' => 5,
        '六' => 6, '七' => 7, '八' => 8, '九' => 9, '十' => 10,
    ];

    public function __construct()
    {
        $config = (array) config('agent_whitelist', []);
        $this->phrases = (array) ($config['phrases'] ?? []);
        $this->writeIntentKeywords = (array) ($config['write_intent_keywords'] ?? []);
        $this->q20NoCtx = (string) ($config['q20_no_ctx'] ?? 'Q-20-NO-CTX');
    }

    /**
     * 路由一条问法（步骤①~⑥；不执行动作）
     *
     * @param string $utterance 用户问法原文
     * @param array  $context   上下文（current_path 等，Q-20 专用；禁止携带 Cookie/会话凭证）
     * @return array{answer_type:string, q_id:string, alias_id:int, action_id:string, params:array, missing:string[], message:string, matched_keyword:string}
     */
    public function route(string $utterance, array $context = []): array
    {
        // ① 文本归一化
        $text = $this->normalize($utterance);

        $empty = $this->result(self::TYPE_MISS, '', 0, '', [], [], $this->phrase('miss'));

        if ($text === '') {
            return $empty;
        }

        // ② 写意图检测（S-19 §4：写操作问法引导 CLI，不得进入白名单执行）
        foreach ($this->writeIntentKeywords as $kw) {
            if ($this->contains($text, $kw)) {
                $guess = $this->guessWriteAction($text);
                return $this->result(
                    self::TYPE_WRITE_INTENT,
                    '',
                    0,
                    '',
                    [],
                    [],
                    str_replace('{guess_action}', $guess, $this->phrase('write_intent'))
                );
            }
        }

        // ③④ 加载别名（DB 为准）并按「关键词长度降序」匹配消歧
        $aliases = $this->loadAliases();
        $candidates = [];
        foreach ($aliases as $alias) {
            if ($this->contains($text, $alias['pattern'])) {
                $candidates[] = $alias;
            }
        }
        if ($candidates === []) {
            return $empty;
        }

        usort($candidates, static function (array $a, array $b): int {
            $lenA = mb_strlen((string) $a['pattern']);
            $lenB = mb_strlen((string) $b['pattern']);
            if ($lenA !== $lenB) {
                return $lenB <=> $lenA; // 长关键词优先（更具体）
            }
            return (int) $a['priority'] <=> (int) $b['priority'];
        });

        $winner = $candidates[0];
        $rule = $this->decodeRule($winner['params_rule'] ?? '');
        $actionId = (string) $winner['action_id'];

        // ⑤ 参数组装：固定参数 + 抽取参数
        $params = is_array($rule['fixed'] ?? null) ? $rule['fixed'] : [];
        $missing = [];

        switch ((string) ($rule['extract'] ?? '')) {
            case 'id':
                $id = $this->extractId($text);
                if ($id === null) {
                    $missing[] = 'id';
                } else {
                    $params['id'] = $id;
                }
                break;

            case 'keyword':
                $keyword = $this->extractKeyword($utterance);
                if ($keyword === '') {
                    $missing[] = 'keyword';
                } else {
                    $params['keyword'] = $keyword;
                }
                break;

            case 'current_path':
                // Q-20 特例（S-19 §2.5）：参数来自上下文，前端不上传任何 Cookie/会话凭证
                $currentPath = trim((string) ($context['current_path'] ?? ''));
                if ($currentPath === '') {
                    return $this->result(
                        self::TYPE_PARAM_MISSING,
                        (string) $winner['q_id'],
                        (int) $winner['alias_id'],
                        $actionId,
                        [],
                        ['current_path'],
                        $this->q20NoCtx . '：' . (string) config('agent_whitelist.q20_no_ctx_message', '没拿到当前页面，请刷新后重试')
                    );
                }
                $params['current_path'] = $currentPath;
                break;

            case 'limit':
                $params['limit'] = $this->extractLimit($text) ?? (int) ($params['limit'] ?? 5);
                break;

            default:
                break;
        }

        // 动态 limit 补充（如「最新文章十条」）
        if (!isset($params['limit']) || !isset($rule['extract'])) {
            $cnLimit = $this->extractLimit($text);
            if ($cnLimit !== null && empty($params['count_only']) && !isset($rule['fixed']['limit'])) {
                $params['limit'] = $cnLimit;
            }
        }

        // ⑥ 必填校验（Registry input_schema.required）
        $def = ActionRegistry::get($actionId);
        if ($def === null) {
            // 白名单命中但动作未启用/未注册（S-19 §4：不编造，明确拒答）
            return $this->result(
                self::TYPE_NOT_IMPLEMENTED,
                (string) $winner['q_id'],
                (int) $winner['alias_id'],
                $actionId,
                $params,
                [],
                str_replace('{q_id}', (string) $winner['q_id'], $this->phrase('not_implemented'))
            );
        }

        foreach ((array) ($def['input_schema']['required'] ?? []) as $field) {
            $field = (string) $field;
            if (!in_array($field, $missing, true) && (!isset($params[$field]) || $params[$field] === '' || $params[$field] === null)) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            $example = (string) config('agent_whitelist.q20_no_ctx_message', '');
            $itemConfig = $this->findSeedItem((string) $winner['q_id']);
            $example = (string) ($itemConfig['example'] ?? '请补充参数后重试');

            return $this->result(
                self::TYPE_PARAM_MISSING,
                (string) $winner['q_id'],
                (int) $winner['alias_id'],
                $actionId,
                $params,
                $missing,
                str_replace(
                    ['{missing_field}', '{example}'],
                    [implode('、', $missing), $example],
                    $this->phrase('param_missing')
                )
            );
        }

        // ⑦ 命中返回（执行归调用方：ActionExecutor / AgentInvokeService）
        return $this->result(
            self::TYPE_HIT,
            (string) $winner['q_id'],
            (int) $winner['alias_id'],
            $actionId,
            $params,
            [],
            '',
            (string) $winner['pattern']
        );
    }

    /**
     * 加载别名（R-2：DB 为准；空表时用 config seed 播种一次）
     *
     * @return array<int, array{alias_id:int,pattern:string,action_id:string,params_rule:string,q_id:string,priority:int}>
     */
    public function loadAliases(): array
    {
        try {
            $rows = Db::name('agent_action_alias')
                ->where('enabled', 1)
                ->field('alias_id,pattern,action_id,params_rule,q_id,priority')
                ->select()
                ->toArray();

            if ($rows === []) {
                $this->seedFromConfig();
                $rows = Db::name('agent_action_alias')
                    ->where('enabled', 1)
                    ->field('alias_id,pattern,action_id,params_rule,q_id,priority')
                    ->select()
                    ->toArray();
            }

            return $rows;
        } catch (\Throwable $e) {
            // 基础设施异常：降级为纯 config 匹配（不阻塞对话窗）
            Log::warning('IntentRouter alias load degraded: ' . $e->getMessage());
            return $this->configAliases();
        }
    }

    /** 把 config/agent_whitelist.php 播种进 i8j_agent_action_alias（INSERT IGNORE 幂等，可重复导入） */
    public function seedFromConfig(): int
    {
        $items = (array) config('agent_whitelist.items', []);
        if ($items === []) {
            return 0;
        }

        $now = time();
        $inserted = 0;
        try {
            foreach ($items as $item) {
                foreach ((array) ($item['keywords'] ?? []) as $keyword) {
                    $keyword = trim((string) $keyword);
                    if ($keyword === '') {
                        continue;
                    }
                    $rule = [
                        'fixed' => [],
                        'extract' => '',
                    ];
                    foreach ((array) ($item['params'] ?? []) as $k => $v) {
                        if ($k === '_extract') {
                            $rule['extract'] = (string) $v;
                        } else {
                            $rule['fixed'][$k] = $v;
                        }
                    }

                    $exists = Db::name('agent_action_alias')->where('pattern', $keyword)->count();
                    if ($exists > 0) {
                        continue;
                    }

                    Db::name('agent_action_alias')->insert([
                        'pattern' => mb_substr($keyword, 0, 255),
                        'action_id' => (string) $item['action_id'],
                        'params_rule' => json_encode($rule, JSON_UNESCAPED_UNICODE),
                        'q_id' => (string) $item['q_id'],
                        'priority' => 100,
                        'enabled' => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ]);
                    $inserted++;
                }
            }
            Cache::set(self::CACHE_SEED_CHECK, 1, 3600);
        } catch (\Throwable $e) {
            Log::error('IntentRouter seed failed: ' . $e->getMessage());
        }

        return $inserted;
    }

    /** 枚举白名单（配置 seed 视角，供测试/文档用；运行时权威在 DB） */
    public function listSeedItems(): array
    {
        return (array) config('agent_whitelist.items', []);
    }

    // ==================== 内部：匹配与抽取 ====================

    /** 步骤① 文本归一化：全角转半角、去空白、去标点间隔（保留中英文数字） */
    private function normalize(string $text): string
    {
        $text = str_replace(['　', "\t", "\r", "\n"], ' ', $text);
        $text = $this->fullWidthToHalf($text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        return $text;
    }

    private function fullWidthToHalf(string $text): string
    {
        return str_replace(
            ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９', '＝', '：', '？', '，', '（', '）'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '=', ':', '?', ',', '(', ')'],
            $text
        );
    }

    /** 关键词包含判断（大小写不敏感，关键词同样归一化） */
    private function contains(string $text, string $keyword): bool
    {
        $keyword = $this->normalize($keyword);
        if ($keyword === '') {
            return false;
        }
        return mb_stripos($text, $keyword) !== false;
    }

    /** 步骤⑤ id 抽取：上下文含「第/编号/id」且相邻数字（S-19 §3.1 Step 4） */
    private function extractId(string $text): ?int
    {
        if (preg_match('/(?:id|ID|Id|编号|序号|第)\s*[=:：]?\s*(\d+)/u', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d+)\s*(?:号|号文章|号主题|号会员|号用户)/u', $text, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /** 步骤⑤ keyword 抽取：书名号《》或引号「」""『』'' 内文本 */
    private function extractKeyword(string $raw): string
    {
        if (preg_match('/《([^》]+)》/u', $raw, $m)) {
            return trim($m[1]);
        }
        foreach (['「([^」]+)」', '"([^"]+)"', '"([^"]+)"', "'([^']+)'", '『([^』]+)』'] as $pattern) {
            if (preg_match('/' . $pattern . '/u', $raw, $m)) {
                return trim($m[1]);
            }
        }
        // 「搜索XXX」无引号兜底：取搜索词（去触发词后剩余的名词段，≤30 字）
        // 注意：字符类不能以「:」开头（PCRE 会误判为 POSIX 命名类），冒号须用分组写法
        $norm = $this->normalize($raw);
        if (preg_match('/(?:搜索一下|搜一下|搜索|搜搜|查找)\s*(?::|：)?\s*([\x{4e00}-\x{9fa5}A-Za-z0-9_\-]{1,30})/u', $norm, $m)) {
            $keyword = trim($m[1]);
            // 回退兜底词（正则回溯会让「搜索一下」命中「搜索」分支，把语气词当关键词）
            $fillers = ['一下', '一下下', '一下这些'];
            if ($keyword !== '' && !in_array($keyword, $fillers, true)) {
                return $keyword;
            }
        }
        return '';
    }

    /** 步骤⑤ limit 抽取：中文数字（五个/十条）→ 阿拉伯数字 */
    private function extractLimit(string $text): ?int
    {
        if (preg_match('/(?:最近|最新)?\s*([一二两三四五六七八九十\d]+)\s*(?:条|个|篇|套|名|位|条记录)/u', $text, $m)) {
            $raw = $m[1];
            if (ctype_digit($raw)) {
                $n = (int) $raw;
                return ($n >= 1 && $n <= 50) ? $n : null;
            }
            // 「十二」= 12 / 「十」= 10
            if (preg_match('/^十([一二三四五六七八九])?$/u', $raw, $mm)) {
                $n = isset($mm[1]) ? 10 + self::CN_NUMBERS[$mm[1]] : 10;
                return ($n >= 1 && $n <= 50) ? $n : null;
            }
            if (preg_match('/^([一二两三四五六七八九])十([一二三四五六七八九])?$/u', $raw, $mm)) {
                $n = self::CN_NUMBERS[$mm[1]] * 10 + (isset($mm[2]) ? self::CN_NUMBERS[$mm[2]] : 0);
                return ($n >= 1 && $n <= 50) ? $n : null;
            }
            if (isset(self::CN_NUMBERS[$raw])) {
                return self::CN_NUMBERS[$raw];
            }
        }
        return null;
    }

    /** 写意图「推测动作」：截取动词短语（用于 CLI 引导话术展示） */
    private function guessWriteAction(string $text): string
    {
        foreach ($this->writeIntentKeywords as $kw) {
            $pos = mb_stripos($text, $kw);
            if ($pos !== false) {
                $snippet = mb_substr($text, (int) $pos, 24);
                return trim($snippet) !== '' ? $snippet : $kw;
            }
        }
        return mb_substr($text, 0, 24);
    }

    private function decodeRule(string $raw): array
    {
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['fixed' => [], 'extract' => ''];
    }

    private function findSeedItem(string $qId): ?array
    {
        foreach ($this->listSeedItems() as $item) {
            if ((string) ($item['q_id'] ?? '') === $qId) {
                return $item;
            }
        }
        return null;
    }

    /** config 降级别名（别名表不可用时的兜底形态） */
    private function configAliases(): array
    {
        $out = [];
        foreach ($this->listSeedItems() as $item) {
            foreach ((array) ($item['keywords'] ?? []) as $keyword) {
                $rule = ['fixed' => [], 'extract' => ''];
                foreach ((array) ($item['params'] ?? []) as $k => $v) {
                    if ($k === '_extract') {
                        $rule['extract'] = (string) $v;
                    } else {
                        $rule['fixed'][$k] = $v;
                    }
                }
                $out[] = [
                    'alias_id' => 0,
                    'pattern' => (string) $keyword,
                    'action_id' => (string) $item['action_id'],
                    'params_rule' => json_encode($rule, JSON_UNESCAPED_UNICODE),
                    'q_id' => (string) $item['q_id'],
                    'priority' => 100,
                ];
            }
        }
        return $out;
    }

    private function phrase(string $key): string
    {
        return (string) ($this->phrases[$key] ?? '');
    }

    private function result(
        string $type,
        string $qId,
        int $aliasId,
        string $actionId,
        array $params,
        array $missing,
        string $message,
        string $matchedKeyword = ''
    ): array {
        return [
            'answer_type' => $type,
            'q_id' => $qId,
            'alias_id' => $aliasId,
            'action_id' => $actionId,
            'params' => $params,
            'missing' => $missing,
            'message' => $message,
            'matched_keyword' => $matchedKeyword,
        ];
    }
}
