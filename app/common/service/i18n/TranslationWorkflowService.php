<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use app\admin\model\TranslationTask;
use app\common\service\ml\LangSwitchService;
use app\common\service\ml\TranslationMemoryService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 翻译工作流服务
 * V2.9.39 I18N-V2-1
 *
 * 工作流: 创建 -> AI翻译 -> 人工审核 -> 发布 -> 更新
 * 支持任务管理、任务分配、通知、转交
 */
class TranslationWorkflowService
{
    private const CACHE_TAG = 'translation_workflow';

    /** @var TranslationMemoryService */
    private TranslationMemoryService $memoryService;

    /** @var LangSwitchService */
    private LangSwitchService $langSwitchService;

    public function __construct()
    {
        $this->memoryService = new TranslationMemoryService();
        $this->langSwitchService = new LangSwitchService();
    }

    // ===== 任务创建 =====

    /**
     * 创建翻译任务
     *
     * @param array $data [source_content_id, source_lang, target_lang, task_type, priority, deadline, translator_id, reviewer_id]
     * @return int 任务ID
     */
    public function createTask(array $data): int
    {
        $task = TranslationTask::create([
            'source_content_id' => $data['source_content_id'] ?? 0,
            'source_lang'       => $data['source_lang'] ?? 'zh-cn',
            'target_lang'       => $data['target_lang'] ?? 'en',
            'task_type'         => $data['task_type'] ?? TranslationTask::TYPE_CONTENT,
            'status'            => TranslationTask::STATUS_PENDING,
            'translator_id'     => $data['translator_id'] ?? 0,
            'reviewer_id'       => $data['reviewer_id'] ?? 0,
            'priority'          => $data['priority'] ?? TranslationTask::PRIORITY_NORMAL,
            'deadline'          => $data['deadline'] ?? null,
        ]);

        // 发送通知给翻译人员
        if (!empty($data['translator_id'])) {
            $this->notifyTranslator((int) $task->id, (int) $data['translator_id']);
        }

        Cache::clear();
        return (int) $task->id;
    }

    /**
     * 批量创建翻译任务（为一条内容创建多语言翻译任务）
     *
     * @param int $contentId 源内容ID
     * @param array $targetLangs 目标语言列表
     * @param string $sourceLang 源语言
     * @return array [created => int, skipped => int]
     */
    public function batchCreateTasks(int $contentId, array $targetLangs, string $sourceLang = 'zh-cn'): array
    {
        $created = 0;
        $skipped = 0;
        foreach ($targetLangs as $lang) {
            if ($lang === $sourceLang) {
                $skipped++;
                continue;
            }
            // 检查是否已存在未完成任务
            $existing = TranslationTask::where('source_content_id', $contentId)
                ->where('source_lang', $sourceLang)
                ->where('target_lang', $lang)
                ->whereIn('status', [TranslationTask::STATUS_PENDING, TranslationTask::STATUS_TRANSLATING, TranslationTask::STATUS_REVIEWING])
                ->find();
            if ($existing) {
                $skipped++;
                continue;
            }
            $this->createTask([
                'source_content_id' => $contentId,
                'source_lang'       => $sourceLang,
                'target_lang'       => $lang,
                'task_type'         => TranslationTask::TYPE_CONTENT,
            ]);
            $created++;
        }
        return ['created' => $created, 'skipped' => $skipped];
    }

    // ===== AI翻译 =====

    /**
     * 执行AI翻译
     *
     * @param int $taskId 任务ID
     * @return array [success => bool, msg => string]
     */
    public function executeAiTranslation(int $taskId): array
    {
        $task = TranslationTask::find($taskId);
        if (!$task) {
            return ['success' => false, 'msg' => '任务不存在'];
        }
        if ($task->status !== TranslationTask::STATUS_PENDING) {
            return ['success' => false, 'msg' => '任务状态不允许翻译'];
        }

        // 更新状态为翻译中
        $task->status = TranslationTask::STATUS_TRANSLATING;
        $task->save();

        try {
            // 获取源内容文本
            $sourceText = $this->getSourceText($task);
            if (empty($sourceText)) {
                $task->status = TranslationTask::STATUS_REJECTED;
                $task->review_comment = '无法获取源内容';
                $task->save();
                return ['success' => false, 'msg' => '无法获取源内容'];
            }

            // 优先查询翻译记忆库
            $memoryMatch = $this->memoryService->match($sourceText, $task->source_lang, $task->target_lang);
            if ($memoryMatch && $memoryMatch['similarity'] >= 80) {
                $task->ai_translation = $memoryMatch['target_text'];
                $task->translation_quality = $memoryMatch['similarity'] / 100;
            } else {
                // 调用AI翻译
                $aiService = app()->make(\app\common\service\AiService::class);
                $translated = $aiService->translate($sourceText, $task->source_lang, $task->target_lang);
                $task->ai_translation = $translated;
                $task->translation_quality = 0.80; // AI翻译默认质量评分

                // 存入翻译记忆库
                $this->memoryService->store(
                    $sourceText,
                    $translated,
                    $task->source_lang,
                    $task->target_lang,
                    ['context_type' => 'translation_task', 'context_id' => $taskId, 'quality_score' => 4]
                );
            }

            // 如果没有审核人，直接进入待审核状态（等待人工确认）
            $task->status = TranslationTask::STATUS_REVIEWING;
            $task->save();

            // 通知审核人员
            if ($task->reviewer_id > 0) {
                $this->notifyReviewer($taskId, (int) $task->reviewer_id);
            }

            Cache::clear();
            return ['success' => true, 'msg' => 'AI翻译完成，等待审核'];
        } catch (\Throwable $e) {
            Log::error('AI翻译失败: taskId=' . $taskId . ' error=' . $e->getMessage());
            $task->status = TranslationTask::STATUS_PENDING;
            $task->review_comment = 'AI翻译失败: ' . $e->getMessage();
            $task->save();
            return ['success' => false, 'msg' => 'AI翻译失败: ' . $e->getMessage()];
        }
    }

    /**
     * 批量执行AI翻译（处理所有待翻译任务）
     *
     * @param int $limit 单次处理上限
     * @return array [total => int, success => int, failed => int]
     */
    public function batchExecuteAiTranslation(int $limit = 50): array
    {
        // 按优先级排序处理
        $priorityOrder = [TranslationTask::PRIORITY_HIGH, TranslationTask::PRIORITY_NORMAL, TranslationTask::PRIORITY_LOW];
        $total = 0;
        $success = 0;
        $failed = 0;

        foreach ($priorityOrder as $priority) {
            $tasks = TranslationTask::where('status', TranslationTask::STATUS_PENDING)
                ->where('priority', $priority)
                ->order('deadline', 'asc')
                ->limit($limit - $total)
                ->select();

            foreach ($tasks as $task) {
                $total++;
                $result = $this->executeAiTranslation((int) $task->id);
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                }
                if ($total >= $limit) break 2;
            }
        }

        return ['total' => $total, 'success' => $success, 'failed' => $failed];
    }

    // ===== 人工审核 =====

    /**
     * 提交人工翻译结果（翻译人员提交）
     *
     * @param int $taskId 任务ID
     * @param string $humanTranslation 人工翻译文本
     * @param int $translatorId 翻译人员ID
     * @return array
     */
    public function submitHumanTranslation(int $taskId, string $humanTranslation, int $translatorId): array
    {
        $task = TranslationTask::find($taskId);
        if (!$task) {
            return ['success' => false, 'msg' => '任务不存在'];
        }
        if (!in_array($task->status, [TranslationTask::STATUS_PENDING, TranslationTask::STATUS_TRANSLATING, TranslationTask::STATUS_REVIEWING])) {
            return ['success' => false, 'msg' => '任务状态不允许提交'];
        }

        $task->human_translation = $humanTranslation;
        $task->translator_id = $translatorId;
        $task->status = TranslationTask::STATUS_REVIEWING;
        $task->save();

        // 通知审核人员
        if ($task->reviewer_id > 0) {
            $this->notifyReviewer($taskId, (int) $task->reviewer_id);
        }

        Cache::clear();
        return ['success' => true, 'msg' => '已提交，等待审核'];
    }

    /**
     * 审核通过 -> 发布
     *
     * @param int $taskId 任务ID
     * @param int $reviewerId 审核人员ID
     * @param string $comment 审核意见
     * @return array
     */
    public function approveTask(int $taskId, int $reviewerId, string $comment = ''): array
    {
        $task = TranslationTask::find($taskId);
        if (!$task) {
            return ['success' => false, 'msg' => '任务不存在'];
        }
        if ($task->status !== TranslationTask::STATUS_REVIEWING) {
            return ['success' => false, 'msg' => '任务不在审核状态'];
        }

        // 使用人工翻译或AI翻译（优先人工）
        $finalTranslation = $task->human_translation ?: $task->ai_translation;
        if (empty($finalTranslation)) {
            return ['success' => false, 'msg' => '没有可用的翻译结果'];
        }

        $task->status = TranslationTask::STATUS_COMPLETED;
        $task->reviewer_id = $reviewerId;
        $task->review_comment = $comment;
        $task->completed_time = date('Y-m-d H:i:s');
        $task->save();

        // 发布翻译内容
        $this->publishTranslation($task, $finalTranslation);

        Cache::clear();
        return ['success' => true, 'msg' => '审核通过，翻译已发布'];
    }

    /**
     * 审核驳回 -> 重新翻译
     *
     * @param int $taskId 任务ID
     * @param int $reviewerId 审核人员ID
     * @param string $comment 驳回原因
     * @return array
     */
    public function rejectTask(int $taskId, int $reviewerId, string $comment): array
    {
        $task = TranslationTask::find($taskId);
        if (!$task) {
            return ['success' => false, 'msg' => '任务不存在'];
        }
        if ($task->status !== TranslationTask::STATUS_REVIEWING) {
            return ['success' => false, 'msg' => '任务不在审核状态'];
        }

        $task->status = TranslationTask::STATUS_REJECTED;
        $task->reviewer_id = $reviewerId;
        $task->review_comment = $comment;
        $task->save();

        // 通知翻译人员重新翻译
        if ($task->translator_id > 0) {
            $this->notifyTranslator($taskId, (int) $task->translator_id, '翻译被驳回: ' . $comment);
        }

        Cache::clear();
        return ['success' => true, 'msg' => '已驳回'];
    }

    // ===== 任务分配与转交 =====

    /**
     * 分配翻译人员
     *
     * @param int $taskId 任务ID
     * @param int $translatorId 翻译人员ID
     * @return array
     */
    public function assignTranslator(int $taskId, int $translatorId): array
    {
        $task = TranslationTask::find($taskId);
        if (!$task) {
            return ['success' => false, 'msg' => '任务不存在'];
        }

        $oldTranslator = (int) $task->translator_id;
        $task->translator_id = $translatorId;
        $task->save();

        // 通知新翻译人员
        $this->notifyTranslator($taskId, $translatorId);

        // 通知原翻译人员（如果有变化）
        if ($oldTranslator > 0 && $oldTranslator !== $translatorId) {
            $this->notifyTranslator($taskId, $oldTranslator, '任务已转交给其他翻译人员');
        }

        Cache::clear();
        return ['success' => true, 'msg' => '翻译人员已分配'];
    }

    /**
     * 分配审核人员
     *
     * @param int $taskId 任务ID
     * @param int $reviewerId 审核人员ID
     * @return array
     */
    public function assignReviewer(int $taskId, int $reviewerId): array
    {
        $task = TranslationTask::find($taskId);
        if (!$task) {
            return ['success' => false, 'msg' => '任务不存在'];
        }

        $task->reviewer_id = $reviewerId;
        $task->save();

        // 如果任务已在审核状态，通知审核人员
        if ($task->status === TranslationTask::STATUS_REVIEWING) {
            $this->notifyReviewer($taskId, $reviewerId);
        }

        Cache::clear();
        return ['success' => true, 'msg' => '审核人员已分配'];
    }

    /**
     * 转交任务给其他翻译人员
     *
     * @param int $taskId 任务ID
     * @param int $newTranslatorId 新翻译人员ID
     * @param string $reason 转交原因
     * @return array
     */
    public function transferTask(int $taskId, int $newTranslatorId, string $reason = ''): array
    {
        return $this->assignTranslator($taskId, $newTranslatorId);
    }

    // ===== 任务查询 =====

    /**
     * 获取任务列表
     *
     * @param array $filters [status, priority, translator_id, reviewer_id, target_lang, keyword]
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public function getTaskList(array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $query = TranslationTask::order('priority', 'asc')
            ->order('deadline', 'asc')
            ->order('id', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['translator_id'])) {
            $query->where('translator_id', $filters['translator_id']);
        }
        if (!empty($filters['reviewer_id'])) {
            $query->where('reviewer_id', $filters['reviewer_id']);
        }
        if (!empty($filters['target_lang'])) {
            $query->where('target_lang', $filters['target_lang']);
        }
        if (!empty($filters['task_type'])) {
            $query->where('task_type', $filters['task_type']);
        }

        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ])->toArray();
    }

    /**
     * 获取任务详情
     */
    public function getTaskDetail(int $taskId): ?array
    {
        $task = TranslationTask::find($taskId);
        return $task ? $task->toArray() : null;
    }

    /**
     * 获取翻译统计
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $cacheKey = 'translation_workflow_stats';
        return Cache::remember($cacheKey, function () {
            $total = TranslationTask::count();
            $byStatus = TranslationTask::field('status, COUNT(*) as cnt')
                ->group('status')
                ->select()
                ->toArray();
            $byPriority = TranslationTask::field('priority, COUNT(*) as cnt')
                ->group('priority')
                ->select()
                ->toArray();
            $byTargetLang = TranslationTask::field('target_lang, COUNT(*) as cnt')
                ->group('target_lang')
                ->select()
                ->toArray();
            $overdue = TranslationTask::where('deadline', '<', date('Y-m-d H:i:s'))
                ->whereNotIn('status', [TranslationTask::STATUS_COMPLETED, TranslationTask::STATUS_REJECTED])
                ->count();
            $avgQuality = TranslationTask::where('translation_quality', '>', 0)->avg('translation_quality');

            $statusMap = [];
            foreach ($byStatus as $item) {
                $statusMap[$item['status']] = $item['cnt'];
            }
            $priorityMap = [];
            foreach ($byPriority as $item) {
                $priorityMap[$item['priority']] = $item['cnt'];
            }
            $langMap = [];
            foreach ($byTargetLang as $item) {
                $langMap[$item['target_lang']] = $item['cnt'];
            }

            return [
                'total'           => $total,
                'by_status'       => $statusMap,
                'by_priority'     => $priorityMap,
                'by_target_lang'  => $langMap,
                'overdue_count'   => $overdue,
                'avg_quality'     => round((float) $avgQuality, 2),
                'pending'         => $statusMap[TranslationTask::STATUS_PENDING] ?? 0,
                'translating'     => $statusMap[TranslationTask::STATUS_TRANSLATING] ?? 0,
                'reviewing'       => $statusMap[TranslationTask::STATUS_REVIEWING] ?? 0,
                'completed'       => $statusMap[TranslationTask::STATUS_COMPLETED] ?? 0,
                'rejected'        => $statusMap[TranslationTask::STATUS_REJECTED] ?? 0,
            ];
        }, 300);
    }

    /**
     * 获取待处理任务（用于仪表盘提醒）
     *
     * @param int $userId 当前用户ID
     * @return array
     */
    public function getPendingTasks(int $userId = 0): array
    {
        $cacheKey = 'translation_pending_' . $userId;
        return Cache::remember($cacheKey, function () use ($userId) {
            $query = TranslationTask::whereNotIn('status', [TranslationTask::STATUS_COMPLETED, TranslationTask::STATUS_REJECTED]);
            if ($userId > 0) {
                $query->where(function ($q) use ($userId) {
                    $q->where('translator_id', $userId)->whereOr('reviewer_id', $userId);
                });
            }
            return $query->order('deadline', 'asc')->limit(10)->select()->toArray();
        }, 60);
    }

    // ===== 内部方法 =====

    /**
     * 获取源内容文本
     */
    private function getSourceText(TranslationTask $task): string
    {
        if ($task->task_type === TranslationTask::TYPE_CONTENT && $task->source_content_id > 0) {
            $content = Db::name('content')->where('id', $task->source_content_id)->find();
            if ($content) {
                $parts = [];
                if (!empty($content['title'])) $parts[] = $content['title'];
                if (!empty($content['description'])) $parts[] = $content['description'];
                if (!empty($content['content'])) $parts[] = strip_tags($content['content']);
                return implode("\n\n", $parts);
            }
        }
        // 对于template/plugin/system类型，可从其他表获取
        return '';
    }

    /**
     * 发布翻译内容到内容多语言表
     */
    private function publishTranslation(TranslationTask $task, string $translation): void
    {
        if ($task->task_type !== TranslationTask::TYPE_CONTENT || $task->source_content_id <= 0) {
            return;
        }

        // 检查是否已有翻译记录
        $existing = Db::name('content_lang')
            ->where('content_id', $task->source_content_id)
            ->where('lang', $task->target_lang)
            ->find();

        if ($existing) {
            Db::name('content_lang')
                ->where('id', $existing['id'])
                ->update([
                    'title'             => mb_substr($translation, 0, 255),
                    'translate_status'  => 2, // 已翻译
                    'translate_provider' => 'ai_workflow',
                    'translate_time'    => time(),
                    'update_time'       => time(),
                ]);
        } else {
            Db::name('content_lang')->insert([
                'content_id'         => $task->source_content_id,
                'lang'               => $task->target_lang,
                'title'              => mb_substr($translation, 0, 255),
                'content'            => $translation,
                'translate_status'   => 2,
                'translate_provider' => 'ai_workflow',
                'translate_time'     => time(),
                'create_time'        => time(),
                'update_time'        => time(),
            ]);
        }

        // 更新语言翻译进度
        $this->updateLangProgress($task->target_lang);
    }

    /**
     * 更新语言翻译进度
     */
    private function updateLangProgress(string $langCode): void
    {
        try {
            $totalContent = Db::name('content')->where('status', 1)->count();
            if ($totalContent <= 0) return;
            $translated = Db::name('content_lang')
                ->where('lang', $langCode)
                ->where('translate_status', 2)
                ->count();
            $progress = round($translated / $totalContent, 2);
            Db::name('lang')->where('lang_code', $langCode)->update([
                'translation_progress' => $progress,
            ]);
        } catch (\Throwable $e) {
            // 静默失败，不影响主流程
        }
    }

    /**
     * 通知翻译人员
     */
    private function notifyTranslator(int $taskId, int $translatorId, string $extraMsg = ''): void
    {
        try {
            $msg = '您有新的翻译任务（#' . $taskId . '）' . ($extraMsg ? '：' . $extraMsg : '');
            Db::name('message_system')->insert([
                'user_id'     => $translatorId,
                'title'       => '翻译任务通知',
                'content'     => $msg,
                'type'        => 'translation',
                'is_read'     => 0,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 通知失败不影响主流程
            Log::warning('翻译任务通知失败: ' . $e->getMessage());
        }
    }

    /**
     * 通知审核人员
     */
    private function notifyReviewer(int $taskId, int $reviewerId): void
    {
        try {
            $msg = '翻译任务（#' . $taskId . '）已完成，等待您审核';
            Db::name('message_system')->insert([
                'user_id'     => $reviewerId,
                'title'       => '翻译审核通知',
                'content'     => $msg,
                'type'        => 'translation',
                'is_read'     => 0,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('翻译审核通知失败: ' . $e->getMessage());
        }
    }
}
