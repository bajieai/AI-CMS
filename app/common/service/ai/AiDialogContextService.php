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

namespace app\common\service\ai;

use think\facade\Db;
use think\facade\Cache;

/**
 * AI对话上下文管理服务 — V2.9.39 AI-DEEP-1
 *
 * 滑动窗口策略：保留最近10轮对话（20条消息），超出部分由AI压缩为摘要
 * 上下文导出/导入：支持JSON格式导出完整对话上下文，导入恢复
 */
class AiDialogContextService
{
    private const CACHE_TAG = 'ai_dialog_context';
    private const CACHE_TTL = 1800; // 30分钟

    /** 滑动窗口最大轮数（1轮=用户消息+AI回复） */
    private const SLIDING_WINDOW_ROUNDS = 10;
    /** 触发压缩的消息阈值 */
    private const COMPRESS_THRESHOLD = 22;
    /** 压缩后摘要最大Token */
    private const SUMMARY_MAX_TOKENS = 500;

    private AiDialogMemoryService $memoryService;

    public function __construct()
    {
        $this->memoryService = new AiDialogMemoryService();
    }

    /**
     * 构建AI请求上下文（滑动窗口）
     * @param int $dialogId 会话ID
     * @return array 格式化的上下文消息 [['role'=>'user','content'=>'...'], ...]
     */
    public function buildContext(int $dialogId): array
    {
        $cacheKey = 'ctx_' . $dialogId;
        return Cache::remember($cacheKey, function () use ($dialogId) {
            $messages = $this->memoryService->getShortTermMemory($dialogId, self::SLIDING_WINDOW_ROUNDS);

            // 如果消息数超过阈值，检查是否有摘要可注入
            $allMessages = Db::name('ai_dialog_message')
                ->where('dialog_id', $dialogId)
                ->where('status', 1)
                ->whereIn('role', ['user', 'assistant'])
                ->count();

            $context = [];
            if ($allMessages > self::COMPRESS_THRESHOLD) {
                // 获取已有摘要
                $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
                $existingSummary = '';
                if ($dialog && !empty($dialog['context_summary'])) {
                    $summary = $dialog['context_summary'];
                    if (is_string($summary)) {
                        $summary = json_decode($summary, true);
                    }
                    if (is_array($summary) && !empty($summary['summary'])) {
                        $existingSummary = $summary['summary'];
                    }
                }

                // 在消息前插入系统摘要
                if (!empty($existingSummary)) {
                    $context[] = [
                        'role'    => 'system',
                        'content' => "以下是之前对话的摘要：\n" . $existingSummary,
                    ];
                }
            }

            // 添加滑动窗口内的消息
            foreach ($messages as $msg) {
                $context[] = [
                    'role'    => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            return $context;
        }, self::CACHE_TTL);
    }

    /**
     * 将上下文消息列表格式化为prompt文本
     * @param array $context 上下文消息
     * @return string 格式化文本
     */
    public function formatAsPrompt(array $context): string
    {
        $parts = [];
        foreach ($context as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';
            $label = match($role) {
                'user' => '用户',
                'assistant' => 'AI',
                'system' => '系统',
                default => $role,
            };
            $parts[] = "{$label}: {$content}";
        }
        return implode("\n\n", $parts);
    }

    /**
     * 触发上下文压缩（AI摘要生成）
     * @param int $dialogId 会话ID
     * @return array ['success' => bool, 'summary' => string]
     */
    public function compressContext(int $dialogId): array
    {
        // 获取需要压缩的旧消息（窗口外的）
        $allMessages = Db::name('ai_dialog_message')
            ->where('dialog_id', $dialogId)
            ->where('status', 1)
            ->whereIn('role', ['user', 'assistant'])
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $totalMessages = count($allMessages);
        if ($totalMessages <= self::COMPRESS_THRESHOLD) {
            return ['success' => true, 'summary' => '', 'message' => '消息数未达压缩阈值'];
        }

        // 保留最近10轮（20条），压缩前面的
        $toCompress = array_slice($allMessages, 0, $totalMessages - self::SLIDING_WINDOW_ROUNDS * 2);

        // 构建压缩prompt
        $compressText = '';
        foreach ($toCompress as $msg) {
            $role = $msg['role'] === 'user' ? '用户' : 'AI';
            $compressText .= "{$role}: {$msg['content']}\n";
        }

        $prompt = "请将以下对话内容总结为一段简洁的摘要，保留关键信息、决策和上下文要点，不超过500字：\n\n" . $compressText;

        try {
            $provider = AiProviderFactory::getDefault();
            $summary = $provider->write($prompt, [
                'temperature' => 0.3,
                'max_tokens'  => self::SUMMARY_MAX_TOKENS,
            ]);

            // 获取已有摘要并合并
            $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
            $existingSummary = [];
            if ($dialog && !empty($dialog['context_summary'])) {
                $existingSummary = $dialog['context_summary'];
                if (is_string($existingSummary)) {
                    $existingSummary = json_decode($existingSummary, true) ?: [];
                }
            }

            $existingSummary['summary'] = $summary;
            $existingSummary['compressed_at'] = date('Y-m-d H:i:s');
            $existingSummary['compressed_count'] = ($existingSummary['compressed_count'] ?? 0) + 1;

            Db::name('ai_dialog')
                ->where('id', $dialogId)
                ->update([
                    'context_summary' => json_encode($existingSummary, JSON_UNESCAPED_UNICODE),
                    'update_time'     => time(),
                ]);

            // 清除上下文缓存
            Cache::delete('ctx_' . $dialogId);

            return ['success' => true, 'summary' => $summary];
        } catch (\Throwable $e) {
            return ['success' => false, 'summary' => '', 'message' => $e->getMessage()];
        }
    }

    /**
     * 导出会话上下文（JSON格式）
     * @param int $dialogId 会话ID
     * @return array 导出数据
     */
    public function exportContext(int $dialogId): array
    {
        $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
        if (!$dialog) {
            return [];
        }

        $messages = Db::name('ai_dialog_message')
            ->where('dialog_id', $dialogId)
            ->where('status', 1)
            ->order('id', 'asc')
            ->field('role, content, token_count, create_time')
            ->select()
            ->toArray();

        $contextSummary = $dialog['context_summary'];
        if (is_string($contextSummary)) {
            $contextSummary = json_decode($contextSummary, true);
        }

        return [
            'dialog' => [
                'id'              => $dialog['id'],
                'session_id'      => $dialog['session_id'] ?? '',
                'title'           => $dialog['title'],
                'model'           => $dialog['model'] ?? '',
                'message_count'   => $dialog['message_count'] ?? count($messages),
                'total_tokens'    => $dialog['total_tokens'] ?? 0,
                'create_time'     => $dialog['create_time'] ?? 0,
                'update_time'     => $dialog['update_time'] ?? 0,
            ],
            'messages'        => $messages,
            'context_summary' => $contextSummary ?: [],
            'exported_at'     => date('Y-m-d H:i:s'),
            'version'         => '1.0',
        ];
    }

    /**
     * 导入会话上下文（从JSON数据恢复）
     * @param int $userId 用户ID
     * @param array $data 导出数据
     * @return int 新会话ID
     */
    public function importContext(int $userId, array $data): int
    {
        $dialogInfo = $data['dialog'] ?? [];
        $messages = $data['messages'] ?? [];
        $contextSummary = $data['context_summary'] ?? [];

        // 创建新会话
        $dialogId = (int) Db::name('ai_dialog')->insertGetId([
            'session_id'      => 'dlg_imp_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)),
            'user_id'         => $userId,
            'title'           => ($dialogInfo['title'] ?? '导入对话') . ' (导入)',
            'model'           => $dialogInfo['model'] ?? '',
            'message_count'   => 0,
            'total_tokens'    => 0,
            'context_summary' => json_encode($contextSummary, JSON_UNESCAPED_UNICODE),
            'status'          => 1,
            'create_time'     => time(),
            'update_time'     => time(),
        ]);

        // 导入消息
        $totalTokens = 0;
        $messageCount = 0;
        foreach ($messages as $msg) {
            $tokens = $msg['token_count'] ?? $this->estimateTokens($msg['content'] ?? '');
            Db::name('ai_dialog_message')->insert([
                'dialog_id'   => $dialogId,
                'role'        => $msg['role'],
                'content'     => $msg['content'],
                'token_count' => $tokens,
                'metadata'    => json_encode(['imported' => true, 'original_create_time' => $msg['create_time'] ?? 0], JSON_UNESCAPED_UNICODE),
                'status'      => 1,
                'create_time' => time(),
            ]);
            $totalTokens += $tokens;
            $messageCount++;
        }

        // 更新统计
        Db::name('ai_dialog')
            ->where('id', $dialogId)
            ->update([
                'message_count' => $messageCount,
                'total_tokens'  => $totalTokens,
                'update_time'   => time(),
            ]);

        Cache::delete('ctx_' . $dialogId);

        return $dialogId;
    }

    /**
     * 获取上下文统计信息
     * @param int $dialogId 会话ID
     * @return array 统计数据
     */
    public function getContextStats(int $dialogId): array
    {
        $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
        if (!$dialog) {
            return [];
        }

        $contextSummary = $dialog['context_summary'];
        $hasSummary = false;
        $compressedCount = 0;
        if (!empty($contextSummary)) {
            if (is_string($contextSummary)) {
                $contextSummary = json_decode($contextSummary, true);
            }
            if (is_array($contextSummary)) {
                $hasSummary = !empty($contextSummary['summary']);
                $compressedCount = $contextSummary['compressed_count'] ?? 0;
            }
        }

        $messageCount = (int) Db::name('ai_dialog_message')
            ->where('dialog_id', $dialogId)
            ->where('status', 1)
            ->count();

        return [
            'dialog_id'          => $dialogId,
            'total_messages'     => $messageCount,
            'window_rounds'      => self::SLIDING_WINDOW_ROUNDS,
            'compress_threshold' => self::COMPRESS_THRESHOLD,
            'has_summary'        => $hasSummary,
            'compressed_count'   => $compressedCount,
            'total_tokens'       => $dialog['total_tokens'] ?? 0,
            'needs_compression'  => $messageCount > self::COMPRESS_THRESHOLD,
        ];
    }

    /**
     * 清除上下文缓存
     * @param int $dialogId 会话ID
     */
    public function clearCache(int $dialogId): void
    {
        Cache::delete('ctx_' . $dialogId);
    }

    /**
     * 估算Token数量
     * @param string $text 文本
     * @return int
     */
    private function estimateTokens(string $text): int
    {
        $chineseCount = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);
        $otherCount = strlen($text) - $chineseCount * 3;
        return (int) ceil($chineseCount / 2 + $otherCount / 4);
    }
}
