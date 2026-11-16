-- =============================================================
-- V2.9.64 Sprint O：AI 任务队列补 agent_key（智能体标识）
-- 背景：AiAgentService::run() 的 agentId 是字符串 key（如 content_writer），
--       i8j_ai_task_queue.agent_id 为 int 列，只能硬写 0 → 监控页按 agent 维度查不到任何记录。
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE `i8j_ai_task_queue`
  ADD COLUMN `agent_key` varchar(50) NOT NULL DEFAULT '' COMMENT '智能体标识(key,如content_writer)' AFTER `agent_id`,
  ADD KEY `idx_agent_key` (`agent_key`);
