<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\facade\Cache;

class TemplateQualityTag extends Model
{
    protected $name = 'template_quality_tag';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public const CACHE_TAG = 'template_quality';

    public static function getTemplateScore(int $templateId): array
    {
        return Cache::remember('quality_score_' . $templateId, function () use ($templateId) {
            $tags = self::where('template_id', $templateId)->where('status', 1)->select()->toArray();
            if (empty($tags)) {
                return ['total_score' => 0, 'tags' => [], 'level' => 'unrated'];
            }
            $totalWeight = 0;
            $weightedSum = 0;
            foreach ($tags as $tag) {
                $totalWeight += $tag['weight'];
                $weightedSum += $tag['score'] * $tag['weight'];
            }
            $totalScore = $totalWeight > 0 ? round($weightedSum / $totalWeight, 1) : 0;
            $level = 'C';
            if ($totalScore >= 8) $level = 'S';
            elseif ($totalScore >= 7) $level = 'A';
            elseif ($totalScore >= 5) $level = 'B';
            return ['total_score' => $totalScore, 'tags' => $tags, 'level' => $level];
        }, 3600);
    }

    public static function getTemplateTags(int $templateId): array
    {
        return self::where('template_id', $templateId)
            ->where('status', 1)
            ->order('weight', 'desc')
            ->select()
            ->toArray();
    }
}
