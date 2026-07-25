<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiWorkflow;
use think\facade\Cache;

/**
 * AI智能体市场服务
 * V2.9.38 AI-PLUS-5
 * 参照PluginMarketService模式，模板存i8j_ai_workflow表(workflow_type='agent_template')
 */
class AiAgentMarketService
{
    protected const CACHE_TAG = 'agent_market';
    protected const CACHE_TTL = 1800;

    /**
     * 市场列表
     */
    public function getMarketList(array $params = []): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $limit = min(50, max(1, (int)($params['limit'] ?? 12)));
        $query = AiWorkflow::where('is_template', 1)->where('status', 'active');
        
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['sort'])) {
            switch ($params['sort']) {
                case 'rating': $query->order('avg_rating', 'desc'); break;
                case 'install': $query->order('install_count', 'desc'); break;
                case 'newest': $query->order('id', 'desc'); break;
                default: $query->order('sort_order', 'asc')->order('install_count', 'desc');
            }
        } else {
            $query->order('sort_order', 'asc')->order('install_count', 'desc');
        }
        
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        return ['total' => $total, 'list' => $list, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 市场详情
     */
    public function getMarketDetail(int $id): ?array
    {
        return Cache::remember('agent_market_' . $id, function() use ($id) {
            $item = AiWorkflow::find($id);
            return $item ? $item->toArray() : null;
        }, self::CACHE_TTL);
    }

    /**
     * 安装智能体模板(创建副本)
     */
    public function installAgent(int $templateId, int $creatorId): int
    {
        $template = AiWorkflow::find($templateId);
        if (!$template || !$template->is_template) {
            throw new \RuntimeException('Template not found');
        }
        // 创建用户副本
        $copy = new AiWorkflow();
        $copy->save([
            'name' => $template->name . ' (已安装)',
            'description' => $template->description,
            'workflow_type' => $template->workflow_type,
            'workflow_definition' => $template->workflow_definition,
            'trigger_type' => 'manual',
            'is_active' => 1,
            'is_template' => 0,
            'category' => $template->category,
            'creator_id' => $creatorId,
            'status' => 'active',
        ]);
        // 增加安装计数
        AiWorkflow::where('id', $templateId)->inc('install_count')->update();
        Cache::clear();
        return (int) $copy->id;
    }

    /**
     * 卸载智能体
     */
    public function uninstallAgent(int $agentId): bool
    {
        $agent = AiWorkflow::find($agentId);
        if (!$agent || $agent->is_template) return false;
        $agent->delete();
        return true;
    }

    /**
     * 提交模板到市场
     */
    public function submitTemplate(int $workflowId, string $description = ''): bool
    {
        $workflow = AiWorkflow::find($workflowId);
        if (!$workflow) return false;
        $workflow->save([
            'is_template' => 1,
            'status' => 'pending_audit',
            'description' => $description ?: $workflow->description,
        ]);
        return true;
    }

    /**
     * 审核模板
     */
    public function auditTemplate(int $templateId, bool $approved, string $reason = ''): bool
    {
        $template = AiWorkflow::find($templateId);
        if (!$template) return false;
        $template->save([
            'status' => $approved ? 'active' : 'rejected',
        ]);
        Cache::clear();
        return true;
    }

    /**
     * 评分模板
     */
    public function rateTemplate(int $templateId, float $rating): bool
    {
        $template = AiWorkflow::find($templateId);
        if (!$template) return false;
        // 简化: 直接更新平均评分(实际应记录用户评分再计算)
        $currentRating = (float) $template->avg_rating;
        $installCount = (int) $template->install_count;
        $newRating = $installCount > 0 ? (($currentRating * $installCount) + $rating) / ($installCount + 1) : $rating;
        $template->save(['avg_rating' => round($newRating, 1)]);
        Cache::clear();
        return true;
    }
}
