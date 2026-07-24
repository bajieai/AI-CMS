<?php
declare(strict_types=1);

namespace app\common\service\ai;

use think\facade\Cache;
use think\facade\Db;

/**
 * AI智能体记忆管理
 * V2.9.38 AI-PLUS-3
 * 
 * 短期记忆: Redis会话级(当前执行上下文)
 * 长期记忆: DB JSON字段(历史经验和知识)
 */
class AiAgentMemory
{
    protected const SHORT_TERM_PREFIX = 'agent_st_';
    protected const SHORT_TERM_TTL = 3600; // 1小时

    /**
     * 初始化会话
     */
    public function initSession(string $sessionId, array $agentConfig): void
    {
        Cache::set(self::SHORT_TERM_PREFIX . $sessionId, [
            'agent_id' => $agentConfig['id'] ?? '',
            'agent_name' => $agentConfig['name'] ?? '',
            'started_at' => date('Y-m-d H:i:s'),
            'items' => [],
        ], self::SHORT_TERM_TTL);
    }

    /**
     * 添加短期记忆
     */
    public function addShortTerm(string $sessionId, string $key, mixed $value): void
    {
        $memory = Cache::get(self::SHORT_TERM_PREFIX . $sessionId, ['items' => []]);
        $memory['items'][$key] = $value;
        Cache::set(self::SHORT_TERM_PREFIX . $sessionId, $memory, self::SHORT_TERM_TTL);
    }

    /**
     * 获取短期记忆
     */
    public function getShortTerm(string $sessionId, string $key = ''): mixed
    {
        $memory = Cache::get(self::SHORT_TERM_PREFIX . $sessionId, ['items' => []]);
        if (empty($key)) return $memory;
        return $memory['items'][$key] ?? null;
    }

    /**
     * 获取完整上下文(短期记忆全部)
     */
    public function getContext(string $sessionId): array
    {
        return $this->getShortTerm($sessionId) ?: ['items' => []];
    }

    /**
     * 获取会话记忆(用于持久化)
     */
    public function getSessionMemory(string $sessionId): array
    {
        return $this->getShortTerm($sessionId) ?: [];
    }

    /**
     * 保存长期记忆
     */
    public function saveLongTerm(string $sessionId, array $data): void
    {
        $memory = $this->getShortTerm($sessionId);
        $agentId = $memory['agent_id'] ?? 'unknown';
        
        // 存储到DB(system_config中的agent_memory_{agentId})
        $key = 'agent_memory_' . $agentId;
        $existing = Db::name('system_config')->where('config_key', $key)->value('config_value');
        $memories = $existing ? json_decode($existing, true) : [];
        if (!is_array($memories)) $memories = [];
        
        $memories[] = array_merge($data, [
            'session_id' => $sessionId,
            'saved_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 只保留最近100条记忆
        if (count($memories) > 100) {
            $memories = array_slice($memories, -100);
        }

        $exists = Db::name('system_config')->where('config_key', $key)->find();
        if ($exists) {
            Db::name('system_config')->where('config_key', $key)->update(['config_value' => json_encode($memories, JSON_UNESCAPED_UNICODE)]);
        } else {
            Db::name('system_config')->insert(['config_key' => $key, 'config_value' => json_encode($memories, JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * 获取长期记忆
     */
    public function getLongTerm(string $agentId, int $limit = 10): array
    {
        $key = 'agent_memory_' . $agentId;
        $existing = Db::name('system_config')->where('config_key', $key)->value('config_value');
        $memories = $existing ? json_decode($existing, true) : [];
        if (!is_array($memories)) return [];
        return array_slice(array_reverse($memories), 0, $limit);
    }

    /**
     * 清除会话记忆
     */
    public function clearSession(string $sessionId): void
    {
        Cache::delete(self::SHORT_TERM_PREFIX . $sessionId);
    }
}
