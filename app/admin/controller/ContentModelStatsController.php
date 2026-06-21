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

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Content;
use app\common\model\ContentModel;
use app\common\model\ContentModelField;
use think\facade\Cache;
use think\facade\Db;

/**
 * V2.9.27 S-7: 内容模型数据统计控制器
 * 5维度统计：内容数量/发布率/浏览量/质量分/趋势
 */
class ContentModelStatsController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 统计面板首页
     */
    public function index()
    {
        $models = ContentModel::order('sort', 'asc')->select();
        $summary = $this->getSummaryStats();

        $modelStats = [];
        foreach ($models as $model) {
            $modelStats[] = $this->getModelStats($model->id);
        }

        $trend = $this->getTrendData(7);

        $this->assign([
            'models' => $models,
            'summary' => $summary,
            'model_stats' => $modelStats,
            'trend' => $trend,
        ]);
        return $this->view('/content_model_stats_index');
    }

    /**
     * AJAX: 获取单个模型详细统计
     */
    public function getModelDetail(int $modelId)
    {
        $stats = $this->getModelStats($modelId);
        $fieldStats = $this->getFieldStats($modelId);
        return $this->success('获取成功', [
            'stats' => $stats,
            'field_stats' => $fieldStats,
        ]);
    }

    /**
     * AJAX: 获取趋势数据
     */
    public function getTrend(int $days = 7)
    {
        $data = $this->getTrendData($days);
        return $this->success('获取成功', $data);
    }

    /**
     * AJAX: 刷新统计缓存
     */
    public function refresh()
    {
        Cache::tag('content_model_stats')->clear();
        return $this->success('统计已刷新');
    }

    // === 私有方法 ===

    private function getSummaryStats(): array
    {
        $cacheKey = 'content_model_stats_summary';
        $cached = Cache::tag('content_model_stats')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $total = Content::where('status', '>=', 0)->count();
        $published = Content::where('status', 2)->count();
        $draft = Content::where('status', 0)->count();
        $pending = Content::where('status', 1)->count();
        $totalViews = (int) Content::where('status', '>=', 0)->sum('views');
        $withModel = Content::where('model_id', '>', 0)->where('status', '>=', 0)->count();
        $modelCount = ContentModel::where('status', 1)->count();
        $fieldCount = ContentModelField::where('status', 1)->count();

        $stats = [
            'total' => $total,
            'published' => $published,
            'draft' => $draft,
            'pending' => $pending,
            'total_views' => $totalViews,
            'with_model' => $withModel,
            'without_model' => $total - $withModel,
            'model_count' => $modelCount,
            'field_count' => $fieldCount,
            'publish_rate' => $total > 0 ? round($published / $total * 100, 1) : 0,
        ];

        Cache::tag('content_model_stats')->set($cacheKey, $stats, 300);
        return $stats;
    }

    private function getModelStats(int $modelId): array
    {
        $cacheKey = 'content_model_stats_' . $modelId;
        $cached = Cache::tag('content_model_stats')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $model = ContentModel::find($modelId);
        $total = Content::where('model_id', $modelId)->where('status', '>=', 0)->count();
        $published = Content::where('model_id', $modelId)->where('status', 2)->count();
        $draft = Content::where('model_id', $modelId)->where('status', 0)->count();
        $pending = Content::where('model_id', $modelId)->where('status', 1)->count();
        $totalViews = (int) Content::where('model_id', $modelId)->where('status', '>=', 0)->sum('views');
        $avgQuality = (float) Content::where('model_id', $modelId)->where('status', '>=', 0)->avg('quality_score');
        $fieldCount = ContentModelField::where('model_id', $modelId)->where('status', 1)->count();

        $stats = [
            'model_id' => $modelId,
            'model_name' => $model ? $model->name : '未知',
            'model_code' => $model ? $model->code : '',
            'total' => $total,
            'published' => $published,
            'draft' => $draft,
            'pending' => $pending,
            'total_views' => $totalViews,
            'avg_quality' => round($avgQuality, 1),
            'field_count' => $fieldCount,
            'publish_rate' => $total > 0 ? round($published / $total * 100, 1) : 0,
        ];

        Cache::tag('content_model_stats')->set($cacheKey, $stats, 300);
        return $stats;
    }

    private function getFieldStats(int $modelId): array
    {
        $fields = ContentModelField::where('model_id', $modelId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        $result = [];
        foreach ($fields as $field) {
            $filledCount = 0;
            $contents = Content::where('model_id', $modelId)->where('status', '>=', 0)->limit(500)->select();
            foreach ($contents as $content) {
                if ($content->ext && $content->ext->data) {
                    $val = $content->ext->data[$field->name] ?? null;
                    if ($val !== null && $val !== '') {
                        $filledCount++;
                    }
                }
            }
            $result[] = [
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'filled_count' => $filledCount,
                'total_checked' => count($contents),
                'fill_rate' => count($contents) > 0 ? round($filledCount / count($contents) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function getTrendData(int $days = 7): array
    {
        $cacheKey = 'content_model_stats_trend_' . $days;
        $cached = Cache::tag('content_model_stats')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $startTs = strtotime($date . ' 00:00:00');
            $endTs = $startTs + 86400;

            $count = Content::where('create_time', '>=', $startTs)
                ->where('create_time', '<', $endTs)
                ->where('status', '>=', 0)
                ->count();

            $result[] = [
                'date' => $date,
                'new_count' => $count,
            ];
        }

        Cache::tag('content_model_stats')->set($cacheKey, $result, 300);
        return $result;
    }
}
