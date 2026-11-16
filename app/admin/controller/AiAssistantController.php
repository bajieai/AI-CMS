<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\agent\ActionExecutor;
use app\common\service\agent\AgentInvokeService;
use app\common\service\agent\IntentRouter;
use app\common\service\agent\ScopeMatcher;
use think\facade\Db;
use think\facade\Log;

/**
 * AI 助手 · 对话悬浮窗后端（ACP-15 / S3-T4~T8）
 *
 * 依据 S-19 / 动作层规格 v1.2 §4.9 / §4.12：
 *  - R-6 白名单永远第一：问法 → IntentRouter 确定性路由，零模型可达（P-2）；
 *    禁止走 AiDialogService::sendMessage LLM 路径
 *  - R-1 / §4.1：执行必须经 AgentInvokeService → ActionExecutor（四通道同源），不另写实现
 *  - S-8：不携带 Cookie / 会话凭证；上下文仅 current_path；data_origin 水印由前端渲染
 *  - 会话落库复用 i8j_ai_dialog + i8j_ai_dialog_message（member_id=0 表示后台管理端，
 *    session_id='admin_<uid>'），不新建第 4 套会话表
 *
 * 路由：POST /admin/ai_assistant/ask（AdminCsrf 全局校验，前端以 X-CSRF-TOKEN 头携带）
 */
class AiAssistantController extends AdminBaseController
{
    /** 后台管理端会话标记（i8j_ai_dialog.member_id=0） */
    private const DIALOG_MEMBER_ID = 0;

    private IntentRouter $router;
    private AgentInvokeService $agent;

    public function __construct()
    {
        parent::__construct(app());
        $this->router = new IntentRouter();
        $this->agent = new AgentInvokeService();
    }

    /**
     * 对话入口：白名单路由 + 执行 + 落库
     */
    public function ask()
    {
        $utterance = trim((string) $this->request->param('utterance', ''));
        $currentPath = trim((string) $this->request->param('current_path', ''));

        if ($utterance === '' || mb_strlen($utterance) > 200) {
            return $this->error('问法为空或超过 200 字');
        }

        $adminId = (int) session('user_id');

        // ①~⑦ 确定性路由（禁止 LLM 参与，P-2）
        $routed = $this->router->route($utterance, ['current_path' => $currentPath]);

        try {
            $answer = $this->buildAnswer($routed, $utterance, $adminId);
        } catch (\Throwable $e) {
            Log::error('AiAssistantController ask failed: ' . $e->getMessage());
            $safe = '系统繁忙，请稍后重试';
            $answer = [
                'answer_type' => 'whitelist_miss',
                'q_id' => '',
                'request_id' => '',
                'message' => str_replace(['{safe_error}', '{request_id}'], [$safe, ''], (string) config('agent_whitelist.phrases.error', '')),
                'card' => [],
            ];
        }

        // 落库（i8j_ai_dialog / i8j_ai_dialog_message；失败不阻塞对话）
        try {
            $sessionId = $this->persistDialog($adminId, $utterance, $routed, $answer);
            $answer['session_id'] = $sessionId;
        } catch (\Throwable $e) {
            Log::warning('AiAssistantController dialog persist failed: ' . $e->getMessage());
            $answer['session_id'] = '';
        }

        return $this->success('ok', $answer);
    }

    /**
     * 动态 CSRF Token（GET 免 CSRF；页面级 token() 多次渲染会覆盖 session，
     * 悬浮窗不依赖片段内的静态值，发送前实时取用，403 时自动刷新重试）
     */
    public function csrf()
    {
        $token = (string) session('__token__');
        if ($token === '') {
            $token = md5(uniqid((string) mt_rand(), true));
            session('__token__', $token);
        }

        return $this->success('ok', ['token' => $token]);
    }

    /**
     * 会话历史（最近 N 轮，PJAX 切页后前端可按需回填；W1 仅本管理员的 session）
     */
    public function history()
    {
        $adminId = (int) session('user_id');
        $sessionId = 'admin_' . $adminId;

        try {
            $messages = Db::name('ai_dialog_message')
                ->where('session_id', $sessionId)
                ->order('id', 'desc')
                ->limit(20)
                ->select()
                ->toArray();
            $messages = array_reverse($messages);
        } catch (\Throwable $e) {
            $messages = [];
        }

        return $this->success('ok', [
            'session_id' => $sessionId,
            'messages' => $messages,
        ]);
    }

    /**
     * 组装回答卡片（S3-T7 渲染所需字段；W1 不调 LLM，纯结构化输出）
     */
    private function buildAnswer(array $routed, string $utterance, int $adminId): array
    {
        $base = [
            'answer_type' => (string) $routed['answer_type'],
            'q_id' => (string) $routed['q_id'],
            'alias_id' => (int) $routed['alias_id'],
            'action_id' => (string) $routed['action_id'],
            'request_id' => '',
            'card' => [],
            'message' => (string) $routed['message'],
        ];

        switch ($base['answer_type']) {
            case IntentRouter::TYPE_HIT:
                return $this->executeWhitelistHit($base, $routed, $adminId);

            case IntentRouter::TYPE_PARAM_MISSING:
            case IntentRouter::TYPE_WRITE_INTENT:
            case IntentRouter::TYPE_MISS:
            case IntentRouter::TYPE_NOT_IMPLEMENTED:
            default:
                return $base;
        }
    }

    /**
     * 执行白名单命中：经 AgentInvokeService（channel=window），安全异常 → 话术 + request_id
     */
    private function executeWhitelistHit(array $base, array $routed, int $adminId): array
    {
        $result = $this->agent->invoke(
            (string) $routed['action_id'],
            is_array($routed['params'] ?? null) ? $routed['params'] : [],
            [ScopeMatcher::COMPAT_ALL], // 后台管理端为本地管理员代操作，与 CLI 口径一致
            [
                'channel' => 'window',
                'actor_type' => 'human',
                'actor_id' => (string) $adminId,
                'agent_name' => 'admin-window',
                'idempotency_key' => 'win_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)),
                'dry_run' => false,
            ]
        );

        $body = $result['body'];
        $base['request_id'] = (string) ($body['request_id'] ?? '');

        if ((int) $result['status'] !== 200) {
            // S-19 §4：查询出错 → safe_error + request_id（不回显堆栈/密钥）
            $safeError = (string) ($body['msg'] ?? '查询失败');
            $base['message'] = str_replace(
                ['{safe_error}', '{request_id}'],
                [$safeError, $base['request_id']],
                (string) config('agent_whitelist.phrases.error', '')
            );
            return $base;
        }

        $base['card'] = [
            'action_id' => (string) ($body['action_id'] ?? $routed['action_id']),
            'data' => $body['data'] ?? [],
            'meta' => is_array($body['meta'] ?? null) ? $body['meta'] : [],
        ];

        return $base;
    }

    /**
     * 对话落库（复用 i8j_ai_dialog / i8j_ai_dialog_message，S-08 真实表结构）
     *
     * @return string session_id
     */
    private function persistDialog(int $adminId, string $utterance, array $routed, array $answer): string
    {
        $sessionId = 'admin_' . $adminId;
        // 注意：i8j_ai_dialog / i8j_ai_dialog_message 的 create_time 为 datetime 类型（非 int 时间戳）
        $now = date('Y-m-d H:i:s');

        $exists = Db::name('ai_dialog')->where('session_id', $sessionId)->count();
        if ($exists === 0) {
            Db::name('ai_dialog')->insert([
                'member_id' => self::DIALOG_MEMBER_ID,
                'session_id' => $sessionId,
                'title' => mb_substr('后台助手 · ' . $utterance, 0, 255),
                'model_name' => 'whitelist-router',
                'token_usage' => 0,
                'message_count' => 0,
                'is_active' => 1,
                'create_time' => $now,
                'update_time' => $now,
            ]);
        }

        $assistantContent = mb_substr(
            (string) $answer['message'] !== ''
                ? (string) $answer['message']
                : json_encode(['answer_type' => $answer['answer_type'], 'card' => $answer['card']], JSON_UNESCAPED_UNICODE),
            0,
            65535
        );

        $count = (int) Db::name('ai_dialog_message')->where('session_id', $sessionId)->count();

        Db::name('ai_dialog_message')->insertAll([
            [
                'session_id' => $sessionId,
                'role' => 'user',
                'content' => mb_substr($utterance, 0, 65535),
                'tokens' => 0,
                'create_time' => $now,
            ],
            [
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $assistantContent,
                'tokens' => 0,
                'create_time' => $now,
            ],
        ]);

        Db::name('ai_dialog')->where('session_id', $sessionId)->update([
            'message_count' => $count + 2,
            'update_time' => $now,
        ]);

        return $sessionId;
    }
}
