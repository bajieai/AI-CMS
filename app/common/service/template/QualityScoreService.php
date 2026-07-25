<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateQualityTag;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板质量评分服务 — V2.9.26 P-5
 *
 * 评分维度：设计质量/代码质量/SEO友好度/响应式适配/文档完整性
 * 自动评分基于安装量、评分、更新频率等指标
 */
class QualityScoreService
{
    /**
     * 自动计算模板质量评分
     */
    public function autoCalculate(int $templateId): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'message' => '模板不存在'];

        // 基于安装量评分
        $installScore = min(10, round(($template->install_count ?? 0) / 10, 1));
        // 基于用户评分
        $ratingScore = (float)($template->rating ?? 0) * 2;
        // 基于更新频率
        $updateScore = 5;
        if ($template->updated_at) {
            $daysSinceUpdate = (time() - strtotime((string)$template->updated_at)) / 86400;
            if ($daysSinceUpdate < 30) $updateScore = 9;
            elseif ($daysSinceUpdate < 90) $updateScore = 7;
            elseif ($daysSinceUpdate < 180) $updateScore = 5;
            else $updateScore = 3;
        }

        $tags = [
            ['tag_name' => '用户评分', 'tag_type' => 'auto', 'score' => $ratingScore, 'weight' => 30],
            ['tag_name' => '安装热度', 'tag_type' => 'auto', 'score' => $installScore, 'weight' => 25],
            ['tag_name' => '更新活跃度', 'tag_type' => 'auto', 'score' => $updateScore, 'weight' => 20],
        ];

        // 清除旧自动标签
        TemplateQualityTag::where('template_id', $templateId)
            ->where('tag_type', 'auto')
            ->delete();

        // 写入新标签
        foreach ($tags as $tag) {
            TemplateQualityTag::create(array_merge($tag, [
                'template_id' => $templateId,
                'status'      => 1,
            ]));
        }

        Cache::clear();
        $score = TemplateQualityTag::getTemplateScore($templateId);
        return ['success' => true, 'message' => '评分已更新', 'data' => $score];
    }

    /**
     * 添加手动评分标签
     */
    public function addManualTag(int $templateId, string $tagName, float $score, int $weight, int $auditorId, string $auditorName): array
    {
        TemplateQualityTag::create([
            'template_id'  => $templateId,
            'tag_name'     => $tagName,
            'tag_type'     => 'manual',
            'score'        => $score,
            'weight'       => $weight,
            'auditor_id'   => $auditorId,
            'auditor_name' => $auditorName,
            'status'       => 1,
        ]);
        Cache::clear();
        return ['success' => true, 'message' => '标签已添加'];
    }

    /**
     * 获取模板质量评分
     */
    public function getScore(int $templateId): array
    {
        return TemplateQualityTag::getTemplateScore($templateId);
    }

    /**
     * 批量自动评分
     */
    public function batchAutoCalculate(int $limit = 100): array
    {
        $templates = TemplateStore::where('status', 1)->limit($limit)->select();
        $count = 0;
        foreach ($templates as $tpl) {
            $this->autoCalculate((int)$tpl->id);
            $count++;
        }
        return ['success' => true, 'message' => "已评分 {$count} 个模板"];
    }
}
