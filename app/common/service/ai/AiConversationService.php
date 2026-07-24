<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiEditorConversation;
use app\common\provider\AiProviderFactory;
use think\facade\Cache;

/**
 * AI编辑器多轮对话服务 — V2.9.28 A-2
 *
 * Q5: 对话按session_id存储，绑定content_id，30min超时
 * 小产v2审核问题1: content_id=0时仅允许临时对话，不支持跨content对话
 * 小扣建议: session_token_total冗余字段
 */
class AiConversationService
{
    private AiProviderFactory $factory;
    private const SESSION_TIMEOUT = 1800; // 30分钟
    private const MAX_TOKEN = 4096; // 单轮上下文Token上限

    public function __construct()
    {
        $this->factory = new AiProviderFactory();
    }

    /**
     * 发送消息（多轮对话）
     */
    public function chat(string $sessionId, int $userId, string $message, int $contentId = 0): array
    {
        // 检查会话超时
        if ($this->isSessionExpired($sessionId)) {
            return ['success' => false, 'message' => '会话已超时，请重新开始对话'];
        }

        // 获取会话历史
        $history = $this->getHistory($sessionId);
        $sessionTokenTotal = AiEditorConversation::where('session_id', $sessionId)
            ->order('id', 'desc')
            ->value('session_token_total') ?? 0;

        // 构建AI请求消息
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        // 保存用户消息
        AiEditorConversation::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'content_id' => $contentId,
            'role' => AiEditorConversation::ROLE_USER,
            'content' => $message,
            'token_count' => $this->estimateTokens($message),
            'session_token_total' => $sessionTokenTotal + $this->estimateTokens($message),
            'create_time' => time(),
        ]);

        // 调用AI
        try {
            $provider = $this->factory->getDefault();
            $systemPrompt = '你是一个专业的内容编辑助手。请根据用户的指令帮助编辑和优化内容。保持回复简洁有用。';
            array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);

            $response = $provider->chat($messages, [
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            $aiReply = $response['content'] ?? '抱歉，我无法处理您的请求。';
            $replyTokens = $this->estimateTokens($aiReply);

            // 保存AI回复
            AiEditorConversation::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'content_id' => $contentId,
                'role' => AiEditorConversation::ROLE_ASSISTANT,
                'content' => $aiReply,
                'token_count' => $replyTokens,
                'session_token_total' => $sessionTokenTotal + $this->estimateTokens($message) + $replyTokens,
                'create_time' => time(),
            ]);

            // 更新会话活跃时间
            Cache::set('conv_active_' . $sessionId, time(), self::SESSION_TIMEOUT);

            return [
                'success' => true,
                'reply' => $aiReply,
                'session_token_total' => $sessionTokenTotal + $this->estimateTokens($message) + $replyTokens,
                'max_token' => self::MAX_TOKEN,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'AI服务异常: ' . $e->getMessage()];
        }
    }

    /**
     * 获取会话历史
     */
    public function getHistory(string $sessionId): array
    {
        return AiEditorConversation::where('session_id', $sessionId)
            ->order('id', 'asc')
            ->field('role, content, create_time')
            ->select()
            ->toArray();
    }

    /**
     * 创建新会话
     */
    public function createSession(int $userId, int $contentId = 0): string
    {
        $sessionId = AiEditorConversation::generateSessionId();
        Cache::set('conv_active_' . $sessionId, time(), self::SESSION_TIMEOUT);
        Cache::set('conv_user_' . $sessionId, $userId, self::SESSION_TIMEOUT);
        Cache::set('conv_content_' . $sessionId, $contentId, self::SESSION_TIMEOUT);
        return $sessionId;
    }

    /**
     * 检查会话是否超时
     */
    public function isSessionExpired(string $sessionId): bool
    {
        return !Cache::has('conv_active_' . $sessionId);
    }

    /**
     * 导出对话
     */
    public function exportSession(string $sessionId, string $format = 'markdown'): string
    {
        $history = $this->getHistory($sessionId);

        if ($format === 'json') {
            return json_encode($history, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Markdown格式
        $md = "# AI对话记录\n\n";
        $md .= "> 会话ID: {$sessionId}\n";
        $md .= "> 导出时间: " . date('Y-m-d H:i:s') . "\n\n---\n\n";

        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? '**用户**' : '**AI助手**';
            $time = date('H:i:s', (int)$msg['create_time']);
            $md .= "{$role} ({$time})\n\n{$msg['content']}\n\n---\n\n";
        }

        return $md;
    }

    /**
     * 估算Token数量（粗略：中文约2字/token，英文约4字符/token）
     */
    private function estimateTokens(string $text): int
    {
        $chineseCount = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);
        $otherCount = strlen($text) - $chineseCount * 3;
        return (int)ceil($chineseCount / 2 + $otherCount / 4);
    }
}
