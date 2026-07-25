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
 * AI对话记忆服务 — V2.9.39 AI-DEEP-1
 *
 * 短期记忆：当前会话的消息列表（直接从i8j_ai_dialog_message读取）
 * 长期记忆：跨会话关键信息JSON存储（i8j_ai_dialog.context_summary字段 + 独立缓存层）
 *
 * 记忆层级：
 *   1. 短期记忆 — 当前会话最近N轮消息，保持上下文连贯
 *   2. 长期记忆 — 跨会话关键事实/偏好/决策，JSON存储，可检索
 *   3. 过期清理 — 长期记忆自动过期，避免无限增长
 */
class AiDialogMemoryService
{
    private const CACHE_TAG = 'ai_dialog_memory';
    private const CACHE_TTL = 7200; // 2小时

    /** 短期记忆最大轮数 */
    private const SHORT_TERM_MAX_ROUNDS = 20;
    /** 长期记忆最大条目数 */
    private const LONG_TERM_MAX_ENTRIES = 100;
    /** 长期记忆默认过期天数 */
    private const LONG_TERM_DEFAULT_EXPIRE_DAYS = 30;

    /**
     * 获取短期记忆（当前会话消息）
     * @param int $dialogId 会话ID
     * @param int $maxRounds 最大轮数（0=不限）
     * @return array 消息列表 [['role'=>'user','content'=>'...','create_time'=>123], ...]
     */
    public function getShortTermMemory(int $dialogId, int $maxRounds = 0): array
    {
        $limit = $maxRounds > 0 ? $maxRounds * 2 : self::SHORT_TERM_MAX_ROUNDS * 2;

        $messages = Db::name('ai_dialog_message')
            ->where('dialog_id', $dialogId)
            ->where('status', 1)
            ->whereIn('role', ['user', 'assistant'])
            ->order('id', 'desc')
            ->limit($limit)
            ->field('role, content, create_time')
            ->select()
            ->toArray();

        // 倒序取出后恢复正序
        return array_reverse($messages);
    }

    /**
     * 添加短期记忆（保存消息）
     * @param int $dialogId 会话ID
     * @param string $role 角色（user/assistant/system）
     * @param string $content 内容
     * @param array $metadata 元数据
     * @return int 消息ID
     */
    public function addShortTermMemory(int $dialogId, string $role, string $content, array $metadata = []): int
    {
        $tokenCount = $this->estimateTokens($content);

        $id = (int) Db::name('ai_dialog_message')->insertGetId([
            'dialog_id'   => $dialogId,
            'role'        => $role,
            'content'     => $content,
            'token_count' => $tokenCount,
            'metadata'    => json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'status'      => 1,
            'create_time' => time(),
        ]);

        // 更新会话的token统计
        Db::name('ai_dialog')
            ->where('id', $dialogId)
            ->inc('total_tokens', $tokenCount)
            ->inc('message_count')
            ->update(['update_time' => time()]);

        // 清除短期记忆缓存
        Cache::delete('stm_' . $dialogId);

        return $id;
    }

    /**
     * 获取长期记忆（跨会话关键信息）
     * @param int $userId 用户ID
     * @param string $keyword 搜索关键词（可选）
     * @return array 记忆条目列表
     */
    public function getLongTermMemory(int $userId, string $keyword = ''): array
    {
        $cacheKey = 'ltm_' . $userId . '_' . md5($keyword);
        return Cache::remember($cacheKey, function () use ($userId, $keyword) {
            $query = Db::name('ai_dialog')
                ->where('user_id', $userId)
                ->where('status', '<>', 0)
                ->whereNotNull('context_summary')
                ->where('context_summary', '<>', '')
                ->field('id, title, context_summary, update_time');

            if (!empty($keyword)) {
                $query->where('context_summary', 'like', '%' . $keyword . '%');
            }

            $dialogs = $query->order('update_time', 'desc')
                ->limit(self::LONG_TERM_MAX_ENTRIES)
                ->select()
                ->toArray();

            $memories = [];
            foreach ($dialogs as $dialog) {
                $summary = $dialog['context_summary'];
                if (is_string($summary)) {
                    $summary = json_decode($summary, true);
                }
                if (!is_array($summary)) {
                    continue;
                }
                $memories[] = [
                    'dialog_id'   => $dialog['id'],
                    'title'       => $dialog['title'],
                    'summary'     => $summary,
                    'update_time' => $dialog['update_time'],
                ];
            }
            return $memories;
        }, self::CACHE_TTL);
    }

    /**
     * 保存长期记忆（更新会话的上下文摘要）
     * @param int $dialogId 会话ID
     * @param array $summary 摘要数据（key facts, preferences, decisions等）
     * @return bool
     */
    public function saveLongTermMemory(int $dialogId, array $summary): bool
    {
        $result = Db::name('ai_dialog')
            ->where('id', $dialogId)
            ->update([
                'context_summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
                'update_time'     => time(),
            ]);

        // 清除长期记忆缓存
        $userId = (int) Db::name('ai_dialog')->where('id', $dialogId)->value('user_id');
        if ($userId > 0) {
            Cache::delete('ltm_' . $userId . '_' . md5(''));
        }

        return (bool) $result;
    }

    /**
     * 从会话消息中提取关键信息（用于生成长期记忆摘要）
     * @param int $dialogId 会话ID
     * @return array 提取的关键信息
     */
    public function extractKeyInfo(int $dialogId): array
    {
        $messages = $this->getShortTermMemory($dialogId, 0);
        if (empty($messages)) {
            return [];
        }

        $keyInfo = [
            'topics'     => [],
            'decisions'  => [],
            'preferences'=> [],
            'facts'      => [],
            'summary'    => '',
        ];

        // 简易关键词提取（高频词）
        $allText = '';
        foreach ($messages as $msg) {
            $allText .= $msg['content'] . ' ';
        }

        // 提取中文关键词（2-6字的词组，出现≥3次）
        if (preg_match_all('/[\x{4e00}-\x{9fff}]{2,6}/u', $allText, $matches)) {
            $wordFreq = array_count_values($matches[0]);
            arsort($wordFreq);
            $keyInfo['topics'] = array_slice(array_keys($wordFreq), 0, 10);
        }

        // 生成简要摘要（取最后几轮对话的要点）
        $recentMessages = array_slice($messages, -6);
        $summaryParts = [];
        foreach ($recentMessages as $msg) {
            $role = $msg['role'] === 'user' ? '用户' : 'AI';
            $excerpt = mb_substr($msg['content'], 0, 100);
            $summaryParts[] = "{$role}: {$excerpt}";
        }
        $keyInfo['summary'] = implode("\n", $summaryParts);

        return $keyInfo;
    }

    /**
     * 检索相关记忆（基于关键词相似度）
     * @param int $userId 用户ID
     * @param string $query 查询文本
     * @param int $limit 返回数量
     * @return array 排序后的相关记忆
     */
    public function searchRelevantMemory(int $userId, string $query, int $limit = 5): array
    {
        $allMemories = $this->getLongTermMemory($userId);
        if (empty($allMemories)) {
            return [];
        }

        // 简易相关度评分：关键词重叠数
        $queryWords = $this->tokenize($query);
        $scored = [];
        foreach ($allMemories as $memory) {
            $memoryText = $memory['title'] . ' ' . json_encode($memory['summary'], JSON_UNESCAPED_UNICODE);
            $memoryWords = $this->tokenize($memoryText);
            $overlap = count(array_intersect($queryWords, $memoryWords));
            if ($overlap > 0) {
                $scored[] = [
                    'memory' => $memory,
                    'score'  => $overlap,
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scored, 'memory'), 0, $limit);
    }

    /**
     * 清理过期长期记忆
     * @param int $expireDays 过期天数
     * @return int 清理的条目数
     */
    public function cleanupExpiredMemory(int $expireDays = 0): int
    {
        $days = $expireDays > 0 ? $expireDays : self::LONG_TERM_DEFAULT_EXPIRE_DAYS;
        $threshold = time() - ($days * 86400);

        $count = Db::name('ai_dialog')
            ->where('status', 1)
            ->where('update_time', '<', $threshold)
            ->whereNotNull('context_summary')
            ->where('context_summary', '<>', '')
            ->update([
                'context_summary' => null,
                'update_time'     => time(),
            ]);

        if ($count > 0) {
            Cache::clear();
        }

        return $count;
    }

    /**
     * 构建注入AI请求的记忆上下文
     * @param int $dialogId 会话ID
     * @param int $userId 用户ID
     * @return string 格式化的记忆上下文文本
     */
    public function buildMemoryContext(int $dialogId, int $userId): string
    {
        $parts = [];

        // 短期记忆
        $shortTerm = $this->getShortTermMemory($dialogId, 10);
        if (!empty($shortTerm)) {
            $parts[] = "=== 近期对话 ===";
            foreach ($shortTerm as $msg) {
                $role = $msg['role'] === 'user' ? '用户' : 'AI';
                $parts[] = "{$role}: {$msg['content']}";
            }
        }

        // 长期记忆摘要
        $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
        if ($dialog && !empty($dialog['context_summary'])) {
            $summary = $dialog['context_summary'];
            if (is_string($summary)) {
                $summary = json_decode($summary, true);
            }
            if (is_array($summary) && !empty($summary['summary'])) {
                $parts[] = "\n=== 历史摘要 ===";
                $parts[] = $summary['summary'];
            }
        }

        return implode("\n", $parts);
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

    /**
     * 简易分词
     * @param string $text 文本
     * @return array 词列表
     */
    private function tokenize(string $text): array
    {
        $words = [];
        // 中文词组（2-6字）
        if (preg_match_all('/[\x{4e00}-\x{9fff}]{2,6}/u', $text, $matches)) {
            $words = array_merge($words, $matches[0]);
        }
        // 英文单词
        if (preg_match_all('/[a-zA-Z]{2,}/', $text, $matches)) {
            $words = array_merge($words, array_map('strtolower', $matches[0]));
        }
        return array_unique($words);
    }
}
