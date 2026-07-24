<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateRecommendPosition;
use app\common\model\TemplateRecommendItem;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 推荐位管理服务 — V2.9.28 M-6
 */
class TemplateRecommendPositionService
{
    private const CACHE_TAG = 'template_recommend_position';

    /**
     * 获取推荐位列表
     */
    public function getList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $query = new TemplateRecommendPosition();

        if (!empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }

        $total = $query->count();
        $list = $query->order('sort', 'asc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取推荐位详情（含关联模板）
     */
    public function getDetail(int $id): ?array
    {
        $position = TemplateRecommendPosition::with(['items.template'])->find($id);
        return $position ? $position->toArray() : null;
    }

    /**
     * 保存推荐位
     */
    public function save(array $data, int $id = 0): array
    {
        $templateIds = $data['template_ids'] ?? [];
        unset($data['template_ids']);

        if ($id > 0) {
            $position = TemplateRecommendPosition::find($id);
            if (!$position) {
                return ['success' => false, 'message' => '推荐位不存在'];
            }
            $position->save($data);
        } else {
            $position = TemplateRecommendPosition::create($data);
            $id = $position->id;
        }

        // 更新关联模板（仅人工推荐类型）
        if (($data['type'] ?? 1) == TemplateRecommendPosition::TYPE_MANUAL && !empty($templateIds)) {
            TemplateRecommendItem::where('position_id', $id)->delete();
            $sort = 1;
            $now = time();
            foreach ($templateIds as $tid) {
                $item = [
                    'position_id' => $id,
                    'template_id' => (int)$tid,
                    'sort' => $sort++,
                    'start_time' => $data['start_time'] ? strtotime($data['start_time']) : 0,
                    'end_time' => $data['end_time'] ? strtotime($data['end_time']) : 0,
                    'create_time' => $now,
                ];
                TemplateRecommendItem::create($item);
            }
        }

        Cache::clear();
        return ['success' => true, 'message' => '保存成功', 'id' => $id];
    }

    /**
     * 删除推荐位
     */
    public function delete(int $id): array
    {
        TemplateRecommendItem::where('position_id', $id)->delete();
        TemplateRecommendPosition::destroy($id);
        Cache::clear();
        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * 根据推荐位code获取模板列表（前台调用）
     */
    public function getTemplatesByCode(string $code, int $limit = 10): array
    {
        $cacheKey = 'recommend_position_' . $code . '_' . $limit;
        return Cache::remember($cacheKey, function() use ($code, $limit) {
            $position = TemplateRecommendPosition::where('code', $code)
                ->where('status', 1)
                ->find();

            if (!$position) {
                return [];
            }

            $maxCount = min($position->max_count, $limit);

            if ($position->type == TemplateRecommendPosition::TYPE_MANUAL) {
                // 人工推荐：查关联表
                $now = time();
                $items = TemplateRecommendItem::with('template')
                    ->where('position_id', $position->id)
                    ->where(function($query) use ($now) {
                        $query->where('start_time', 0)->whereOr('start_time', '<=', $now);
                    })
                    ->where(function($query) use ($now) {
                        $query->where('end_time', 0)->whereOr('end_time', '>=', $now);
                    })
                    ->order('sort', 'asc')
                    ->limit($maxCount)
                    ->select();

                $result = [];
                foreach ($items as $item) {
                    if ($item->template && $item->template->status == 1) {
                        $result[] = $item->template;
                    }
                }
                return $result;
            } elseif ($position->type == TemplateRecommendPosition::TYPE_RULE) {
                // 规则推荐：按配置条件查询
                $config = is_array($position->config) ? $position->config : json_decode($position->config ?? '{}', true);
                $query = TemplateStore::where('status', 1);

                $field = $config['field'] ?? 'install_count';
                $desc = $config['desc'] ?? true;
                $query->order($field, $desc ? 'desc' : 'asc');

                return $query->limit($maxCount)->select()->toArray();
            }

            // AI推荐：预留接口
            return [];
        }, 1800); // 30分钟缓存
    }
}
