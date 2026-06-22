<?php
declare(strict_types=1);

namespace app\common\service\ai;

use app\common\model\AiEditorTemplate;
use think\facade\Cache;

/**
 * AI编辑器模板库服务 — V2.9.28 A-5
 */
class AiEditorTemplateService
{
    private const CACHE_TAG = 'ai_editor_template';

    /**
     * 获取模板列表
     */
    public function getList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $query = AiEditorTemplate::where('status', 1);

        if (!empty($params['keyword'])) {
            $query->where('name|description|tags', 'like', '%' . $params['keyword'] . '%');
        }
        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['industry'])) {
            $query->where('industry', $params['industry']);
        }
        if (!empty($params['is_system'])) {
            $query->where('is_system', (int)$params['is_system']);
        }
        if (!empty($params['user_id'])) {
            $query->where(function($q) use ($params) {
                $q->where('is_system', 1)->whereOr('user_id', (int)$params['user_id']);
            });
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
     * 获取模板详情
     */
    public function getDetail(int $id): ?array
    {
        return AiEditorTemplate::find($id)?->toArray();
    }

    /**
     * 保存模板
     */
    public function save(array $data, int $id = 0, int $userId = 0): array
    {
        if ($id > 0) {
            $template = AiEditorTemplate::find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }
            if ($template->is_system && $userId > 0) {
                return ['success' => false, 'message' => '系统模板不可修改'];
            }
            $template->save($data);
        } else {
            $data['user_id'] = $userId;
            $data['is_system'] = 0;
            AiEditorTemplate::create($data);
        }
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '保存成功'];
    }

    /**
     * 删除模板
     */
    public function delete(int $id, int $userId = 0): array
    {
        $template = AiEditorTemplate::find($id);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }
        if ($template->is_system) {
            return ['success' => false, 'message' => '系统模板不可删除'];
        }
        if ($template->user_id != $userId) {
            return ['success' => false, 'message' => '无权删除他人模板'];
        }
        $template->delete();
        Cache::tag(self::CACHE_TAG)->clear();
        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * 使用模板（填充变量后返回Prompt）
     */
    public function useTemplate(int $id, array $variables = []): array
    {
        $template = AiEditorTemplate::find($id);
        if (!$template) {
            return ['success' => false, 'message' => '模板不存在'];
        }

        $prompt = $template->prompt;
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{' . $key . '}', $value, $prompt);
        }

        AiEditorTemplate::incrementUseCount($id);
        Cache::tag(self::CACHE_TAG)->clear();

        return [
            'success' => true,
            'name' => $template->name,
            'prompt' => $prompt,
            'example_output' => $template->example_output,
        ];
    }

    /**
     * 获取分类列表
     */
    public function getCategories(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember('ai_template_categories', function() {
            $categories = AiEditorTemplate::where('status', 1)
                ->distinct(true)
                ->column('category');
            $industries = AiEditorTemplate::where('status', 1)
                ->distinct(true)
                ->column('industry');
            return [
                'categories' => array_filter($categories),
                'industries' => array_filter($industries),
            ];
        }, 3600);
    }
}
