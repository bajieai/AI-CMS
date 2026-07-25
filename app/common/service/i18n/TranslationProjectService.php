<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 翻译项目管理服务 - V2.9.40 I18N-V3-1
 *
 * 翻译项目CRUD + 任务分配 + 进度跟踪 + AI辅助翻译
 * 基于V2.9.34 TranslationWorkflowService扩展
 */
class TranslationProjectService
{
    private const CACHE_TAG = 'translation_project';
    private const CACHE_TTL = 600;

    /** 项目状态 */
    private const STATUS_MAP = [
        'draft'       => '草稿',
        'in_progress' => '翻译中',
        'review'      => '审核中',
        'completed'   => '已完成',
        'archived'    => '已归档',
    ];

    /**
     * 创建翻译项目
     */
    public function create(array $data): int
    {
        $id = Db::name('translation_project')->insertGetId([
            'name'          => $data['name'] ?? '',
            'description'   => $data['description'] ?? '',
            'source_lang'   => $data['source_lang'] ?? 'zh',
            'target_langs'  => json_encode($data['target_langs'] ?? ['en']),
            'priority'      => $data['priority'] ?? 'normal',
            'deadline'      => (int) ($data['deadline'] ?? 0),
            'owner_id'      => $data['owner_id'] ?? 0,
            'status'        => 'draft',
            'progress'      => 0,
            'total_items'   => 0,
            'completed_items' => 0,
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        Cache::clear();
        return (int) $id;
    }

    /**
     * 添加翻译项目条目
     */
    public function addItems(int $projectId, array $items): int
    {
        $project = Db::name('translation_project')->find($projectId);
        if (!$project) return 0;

        $targetLangs = json_decode($project['target_langs'] ?? '[]', true);
        $count = 0;

        foreach ($items as $item) {
            foreach ($targetLangs as $lang) {
                Db::name('translation_project_item')->insert([
                    'project_id'    => $projectId,
                    'source_text'   => $item['source_text'] ?? '',
                    'source_lang'   => $project['source_lang'],
                    'target_lang'   => $lang,
                    'target_text'   => '',
                    'status'        => 'pending',
                    'assignee_id'   => 0,
                    'priority'      => $item['priority'] ?? 'normal',
                    'domain'        => $item['domain'] ?? 'general',
                    'created_at'    => time(),
                    'updated_at'    => time(),
                ]);
                $count++;
            }
        }

        Db::name('translation_project')->where('id', $projectId)->update([
            'total_items'     => Db::raw('total_items + ' . $count),
            'status'          => 'in_progress',
            'updated_at'      => time(),
        ]);

        Cache::clear();
        return $count;
    }

    /**
     * AI辅助翻译单个条目
     */
    public function aiTranslate(int $itemId): array
    {
        $item = Db::name('translation_project_item')->find($itemId);
        if (!$item) return ['success' => false, 'msg' => '条目不存在'];

        // 查询翻译记忆
        $tmService = new TranslationMemoryEnhanceService();
        $tmResult = $tmService->search($item['source_text'], $item['source_lang'], $item['target_lang']);

        $targetText = '';
        $method = 'ai';

        if ($tmResult['type'] === 'exact' && $tmResult['score'] >= 0.95) {
            $targetText = $tmResult['target'];
            $method = 'tm_exact';
        } elseif ($tmResult['type'] === 'fuzzy' && $tmResult['score'] >= 0.7) {
            // 模糊匹配+AI修正
            $targetText = $tmResult['target'];
            $method = 'tm_fuzzy_ai';
        } else {
            // 纯AI翻译
            $targetText = $this->callAiTranslate($item['source_text'], $item['source_lang'], $item['target_lang']);
            $method = 'ai';
        }

        // 术语表一致性处理
        $termService = new TerminologyService();
        $targetText = $termService->batchTranslate($targetText, $item['target_lang'], $item['domain']);

        Db::name('translation_project_item')->where('id', $itemId)->update([
            'target_text' => $targetText,
            'status'      => 'ai_translated',
            'method'      => $method,
            'updated_at'  => time(),
        ]);

        return ['success' => true, 'target_text' => $targetText, 'method' => $method];
    }

    /**
     * 调用AI翻译
     */
    private function callAiTranslate(string $text, string $sourceLang, string $targetLang): string
    {
        try {
            $aiService = new \app\common\service\AiService();
            $prompt = "将以下{$sourceLang}文本翻译为{$targetLang}，保持语义准确、自然流畅：\n\n{$text}";
            return $aiService->generate($prompt, ['max_tokens' => 2000]) ?? '';
        } catch (\Exception $e) {
            Log::error('AI翻译失败: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * 分配翻译任务
     */
    public function assignTask(int $itemId, int $assigneeId): bool
    {
        Db::name('translation_project_item')->where('id', $itemId)->update([
            'assignee_id' => $assigneeId,
            'status'      => 'assigned',
            'updated_at'  => time(),
        ]);
        return true;
    }

    /**
     * 提交翻译
     */
    public function submitTranslation(int $itemId, string $targetText): bool
    {
        Db::name('translation_project_item')->where('id', $itemId)->update([
            'target_text' => $targetText,
            'status'      => 'submitted',
            'updated_at'  => time(),
        ]);

        $this->updateProjectProgress($itemId);
        return true;
    }

    /**
     * 审核翻译
     */
    public function review(int $itemId, string $status, string $comment = ''): bool
    {
        $update = [
            'status'     => $status === 'approved' ? 'approved' : 'rejected',
            'review_comment' => $comment,
            'updated_at' => time(),
        ];

        Db::name('translation_project_item')->where('id', $itemId)->update($update);

        if ($status === 'approved') {
            // 存入翻译记忆
            $item = Db::name('translation_project_item')->find($itemId);
            if ($item) {
                $tmService = new TranslationMemoryEnhanceService();
                $tmService->store($item['source_text'], $item['target_text'], $item['source_lang'], $item['target_lang']);
            }
        }

        $this->updateProjectProgress($itemId);
        return true;
    }

    /**
     * 更新项目进度
     */
    private function updateProjectProgress(int $itemId): void
    {
        $item = Db::name('translation_project_item')->find($itemId);
        if (!$item) return;

        $projectId = $item['project_id'];
        $completed = Db::name('translation_project_item')
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->count();

        $total = Db::name('translation_project_item')
            ->where('project_id', $projectId)
            ->count();

        $progress = $total > 0 ? round($completed / $total * 100, 1) : 0;

        $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'draft');

        Db::name('translation_project')->where('id', $projectId)->update([
            'progress'        => $progress,
            'completed_items' => $completed,
            'status'          => $status,
            'updated_at'      => time(),
        ]);

        Cache::clear();
    }

    /**
     * 获取项目详情
     */
    public function getDetail(int $projectId): ?array
    {
        $project = Db::name('translation_project')->find($projectId);
        if (!$project) return null;

        $project['target_langs'] = json_decode($project['target_langs'] ?? '[]', true);
        $project['items'] = Db::name('translation_project_item')
            ->where('project_id', $projectId)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        return $project;
    }

    /**
     * 获取项目列表
     */
    public function getList(int $page = 1, int $limit = 20): array
    {
        return Db::name('translation_project')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }
}
