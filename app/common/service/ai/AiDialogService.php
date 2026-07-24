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
use think\facade\Log;

/**
 * AI对话管理服务 — V2.9.39 AI-DEEP-1
 *
 * 功能：
 *   - 会话CRUD（创建/查询/更新/删除）
 *   - 多会话管理（用户可同时拥有多个独立会话）
 *   - 标题自动生成（首条消息后由AI生成标题）
 *   - 历史搜索（按标题/内容关键词搜索）
 *   - 对话导出（Markdown/JSON格式）
 *
 * 复用 AiProviderFactory 调用AI模型
 * 复用 AiDialogMemoryService 管理对话记忆
 * 复用 AiDialogContextService 管理上下文窗口
 */
class AiDialogService
{
    private const CACHE_TAG = 'ai_dialog';
    private const CACHE_TTL = 600; // 10分钟

    /** 标题自动生成的首条消息字数阈值 */
    private const TITLE_GEN_MIN_LENGTH = 5;
    /** 标题最大长度 */
    private const TITLE_MAX_LENGTH = 50;

    private AiDialogMemoryService $memoryService;
    private AiDialogContextService $contextService;

    public function __construct()
    {
        $this->memoryService = new AiDialogMemoryService();
        $this->contextService = new AiDialogContextService();
    }

    /**
     * 创建新会话
     * @param int $userId 用户ID
     * @param string $title 会话标题（可选，留空则自动生成）
     * @param string $model AI模型标识（可选）
     * @param array $metadata 元数据
     * @return int 会话ID
     */
    public function createDialog(int $userId, string $title = '', string $model = '', array $metadata = []): int
    {
        $sessionId = \app\admin\model\AiDialog::generateSessionId();

        $dialogId = (int) Db::name('ai_dialog')->insertGetId([
            'session_id' => $sessionId,
            'user_id'    => $userId,
            'title'      => $title ?: '新对话',
            'model'      => $model,
            'message_count' => 0,
            'total_tokens'  => 0,
            'context_summary' => null,
            'metadata'   => json_encode($metadata, JSON_UNESCAPED_UNICODE),
            'status'     => \app\admin\model\AiDialog::STATUS_ACTIVE,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        $this->clearUserDialogsCache($userId);

        return $dialogId;
    }

    /**
     * 发送消息并获取AI回复
     * @param int $dialogId 会话ID
     * @param int $userId 用户ID
     * @param string $message 用户消息
     * @param array $options 额外选项（system_prompt, temperature, max_tokens等）
     * @return array ['success' => bool, 'reply' => string, 'dialog_id' => int]
     */
    public function sendMessage(int $dialogId, int $userId, string $message, array $options = []): array
    {
        // 验证会话归属
        $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
        if (!$dialog || $dialog['user_id'] != $userId) {
            return ['success' => false, 'message' => '会话不存在或无权访问'];
        }

        if ($dialog['status'] == \app\admin\model\AiDialog::STATUS_DELETED) {
            return ['success' => false, 'message' => '会话已删除'];
        }

        // 保存用户消息
        $this->memoryService->addShortTermMemory($dialogId, 'user', $message, [
            'options' => $options,
        ]);

        // 清除上下文缓存
        $this->contextService->clearCache($dialogId);

        // 构建上下文
        $context = $this->contextService->buildContext($dialogId);
        $contextPrompt = $this->contextService->formatAsPrompt($context);

        // 如果有系统提示词，前置
        $systemPrompt = $options['system_prompt'] ?? '你是一个专业的AI助手。请根据用户的指令提供有帮助的回答。保持回复准确、简洁、有条理。';

        $fullPrompt = $systemPrompt . "\n\n" . $contextPrompt . "\n\n用户: " . $message;

        // 调用AI
        try {
            $provider = AiProviderFactory::getDefault();

            $aiReply = $provider->write($fullPrompt, [
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens'  => $options['max_tokens'] ?? 2000,
            ]);

            // 保存AI回复
            $this->memoryService->addShortTermMemory($dialogId, 'assistant', $aiReply, [
                'model' => $dialog['model'] ?? '',
            ]);

            // 首条消息后自动生成标题
            $messageCount = (int) Db::name('ai_dialog_message')
                ->where('dialog_id', $dialogId)
                ->where('status', 1)
                ->count();

            if ($messageCount <= 2 && mb_strlen($message) >= self::TITLE_GEN_MIN_LENGTH) {
                $this->autoGenerateTitle($dialogId, $message);
            }

            // 检查是否需要压缩上下文
            if ($messageCount > 22 && $messageCount % 10 === 0) {
                $this->contextService->compressContext($dialogId);
            }

            return [
                'success'   => true,
                'reply'     => $aiReply,
                'dialog_id' => $dialogId,
            ];
        } catch (\Throwable $e) {
            Log::error('AiDialogService sendMessage failed: ' . $e->getMessage());
            return [
                'success'   => false,
                'message'   => 'AI服务异常: ' . $e->getMessage(),
                'dialog_id' => $dialogId,
            ];
        }
    }

    /**
     * 获取会话详情
     * @param int $dialogId 会话ID
     * @return array|null
     */
    public function getDialog(int $dialogId): ?array
    {
        return Cache::remember('dialog_' . $dialogId, function () use ($dialogId) {
            $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
            return $dialog ?: null;
        }, self::CACHE_TTL);
    }

    /**
     * 获取用户的所有会话列表
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param int $status 状态筛选（-1=全部）
     * @return array ['list' => [], 'total' => int, 'page' => int, 'limit' => int]
     */
    public function listDialogs(int $userId, int $page = 1, int $limit = 20, int $status = -1): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        $query = Db::name('ai_dialog')->where('user_id', $userId);

        if ($status >= 0) {
            $query->where('status', $status);
        } else {
            $query->where('status', '<>', \app\admin\model\AiDialog::STATUS_DELETED);
        }

        $total = $query->count();
        $list = $query->order('update_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 更新会话信息
     * @param int $dialogId 会话ID
     * @param array $data 更新数据
     * @return bool
     */
    public function updateDialog(int $dialogId, array $data): bool
    {
        $allowed = ['title', 'model', 'status', 'metadata'];

        $update = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                if ($field === 'metadata') {
                    $update[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                } else {
                    $update[$field] = $data[$field];
                }
            }
        }

        if (empty($update)) {
            return false;
        }

        $update['update_time'] = time();

        $result = Db::name('ai_dialog')->where('id', $dialogId)->update($update);

        Cache::delete('dialog_' . $dialogId);

        $userId = (int) Db::name('ai_dialog')->where('id', $dialogId)->value('user_id');
        $this->clearUserDialogsCache($userId);

        return (bool) $result;
    }

    /**
     * 删除会话（软删除）
     * @param int $dialogId 会话ID
     * @return bool
     */
    public function deleteDialog(int $dialogId): bool
    {
        $result = Db::name('ai_dialog')
            ->where('id', $dialogId)
            ->update([
                'status'      => \app\admin\model\AiDialog::STATUS_DELETED,
                'update_time' => time(),
            ]);

        $userId = (int) Db::name('ai_dialog')->where('id', $dialogId)->value('user_id');
        $this->clearUserDialogsCache($userId);
        Cache::delete('dialog_' . $dialogId);

        return (bool) $result;
    }

    /**
     * 归档会话
     * @param int $dialogId 会话ID
     * @return bool
     */
    public function archiveDialog(int $dialogId): bool
    {
        return $this->updateDialog($dialogId, ['status' => \app\admin\model\AiDialog::STATUS_ARCHIVED]);
    }

    /**
     * 获取会话消息列表
     * @param int $dialogId 会话ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getMessages(int $dialogId, int $page = 1, int $limit = 50): array
    {
        $page = max(1, $page);
        $limit = min(200, max(1, $limit));

        $query = Db::name('ai_dialog_message')
            ->where('dialog_id', $dialogId)
            ->where('status', 1);

        $total = $query->count();
        $list = $query->order('id', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return [
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 搜索对话历史
     * @param int $userId 用户ID
     * @param string $keyword 搜索关键词
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function searchDialogs(int $userId, string $keyword, int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));

        // 按标题搜索
        $titleQuery = Db::name('ai_dialog')
            ->where('user_id', $userId)
            ->where('status', '<>', \app\admin\model\AiDialog::STATUS_DELETED)
            ->where('title', 'like', '%' . $keyword . '%');

        // 按消息内容搜索（子查询匹配dialog_id）
        $dialogIdsFromMessages = Db::name('ai_dialog_message')
            ->alias('m')
            ->join('ai_dialog d', 'm.dialog_id = d.id')
            ->where('d.user_id', $userId)
            ->where('d.status', '<>', \app\admin\model\AiDialog::STATUS_DELETED)
            ->where('m.content', 'like', '%' . $keyword . '%')
            ->distinct()
            ->column('m.dialog_id');

        $query = Db::name('ai_dialog')
            ->where('user_id', $userId)
            ->where('status', '<>', \app\admin\model\AiDialog::STATUS_DELETED)
            ->where(function ($q) use ($keyword, $dialogIdsFromMessages) {
                $q->where('title', 'like', '%' . $keyword . '%');
                if (!empty($dialogIdsFromMessages)) {
                    $q->whereOr('id', 'in', $dialogIdsFromMessages);
                }
            });

        $total = $query->count();
        $list = $query->order('update_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 为每个会话添加匹配的消息片段
        foreach ($list as &$item) {
            $matchedMsg = Db::name('ai_dialog_message')
                ->where('dialog_id', $item['id'])
                ->where('status', 1)
                ->where('content', 'like', '%' . $keyword . '%')
                ->order('id', 'desc')
                ->find();

            if ($matchedMsg) {
                $content = $matchedMsg['content'];
                $pos = mb_strpos($content, $keyword);
                $start = max(0, $pos - 30);
                $item['matched_snippet'] = mb_substr($content, $start, 80 + mb_strlen($keyword)) . '...';
            } else {
                $item['matched_snippet'] = '';
            }
        }

        return [
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'keyword' => $keyword,
        ];
    }

    /**
     * 导出会话（Markdown或JSON格式）
     * @param int $dialogId 会话ID
     * @param string $format 格式（markdown/json）
     * @return string 导出内容
     */
    public function exportDialog(int $dialogId, string $format = 'markdown'): string
    {
        $dialog = Db::name('ai_dialog')->where('id', $dialogId)->find();
        if (!$dialog) {
            return '';
        }

        $messages = Db::name('ai_dialog_message')
            ->where('dialog_id', $dialogId)
            ->where('status', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        if ($format === 'json') {
            $data = $this->contextService->exportContext($dialogId);
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // Markdown格式
        $md = "# AI对话记录: " . ($dialog['title'] ?: '未命名') . "\n\n";
        $md .= "> 会话ID: " . ($dialog['session_id'] ?? '') . "\n";
        $md .= "> 创建时间: " . date('Y-m-d H:i:s', (int) $dialog['create_time']) . "\n";
        $md .= "> 消息数: " . count($messages) . "\n";
        $md .= "> 导出时间: " . date('Y-m-d H:i:s') . "\n\n---\n\n";

        foreach ($messages as $msg) {
            $roleLabel = match($msg['role']) {
                'user' => '**用户**',
                'assistant' => '**AI助手**',
                'system' => '**系统**',
                default => '**' . $msg['role'] . '**',
            };
            $time = date('H:i:s', (int) $msg['create_time']);
            $md .= "{$roleLabel} ({$time})\n\n" . $msg['content'] . "\n\n---\n\n";
        }

        return $md;
    }

    /**
     * 导入会话
     * @param int $userId 用户ID
     * @param string $json JSON格式数据
     * @return int 新会话ID
     */
    public function importDialog(int $userId, string $json): int
    {
        $data = json_decode($json, true);
        if (!$data || !isset($data['messages'])) {
            throw new \InvalidArgumentException('无效的导入数据格式');
        }

        $dialogId = $this->contextService->importContext($userId, $data);

        $this->clearUserDialogsCache($userId);

        return $dialogId;
    }

    /**
     * 自动生成会话标题
     * @param int $dialogId 会话ID
     * @param string $firstMessage 首条用户消息
     * @return void
     */
    private function autoGenerateTitle(int $dialogId, string $firstMessage): void
    {
        try {
            $prompt = "请为以下用户消息生成一个简洁的对话标题（不超过" . self::TITLE_MAX_LENGTH . "字，不要加引号和标点）：\n\n" . mb_substr($firstMessage, 0, 200);

            $provider = AiProviderFactory::getDefault();
            $title = $provider->write($prompt, [
                'temperature' => 0.3,
                'max_tokens'  => 50,
            ]);

            // 清理标题
            $title = trim($title);
            $title = trim($title, '"\'""\'\'【】');
            $title = mb_substr($title, 0, self::TITLE_MAX_LENGTH);

            if (!empty($title)) {
                $this->updateDialog($dialogId, ['title' => $title]);
            }
        } catch (\Throwable $e) {
            // 标题生成失败不影响主流程
            Log::warning('Auto generate title failed: ' . $e->getMessage());
        }
    }

    /**
     * 清除用户会话列表缓存
     * @param int $userId 用户ID
     */
    private function clearUserDialogsCache(int $userId): void
    {
        Cache::delete('user_dialogs_' . $userId);
    }
}
