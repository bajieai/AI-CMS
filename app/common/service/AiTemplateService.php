<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AiTemplate as AiTemplateModel;
use app\common\model\AiBatchTask;
use app\common\model\Cate;
use think\facade\Log;

/**
 * AI内容模板服务 - V2.6新增
 * CRUD + Prompt引擎 + 批量生成调度 + 参考示例模式
 */
class AiTemplateService
{
    /**
     * 获取模板列表（分页）
     */
    public static function getList(int $page = 1, int $limit = 12, array $filter = []): array
    {
        $query = AiTemplateModel::order('sort desc, id desc');

        if (!empty($filter['keyword'])) {
            $query->where('name', 'like', '%' . $filter['keyword'] . '%');
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }
        if (!empty($filter['mode'])) {
            $query->where('generate_mode', $filter['mode']);
        }

        $list = $query->page($page, $limit)->select();
        $total = $query->count();

        return [
            'success' => true,
            'data'    => [
                'list'  => $list,
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
            ],
        ];
    }

    /**
     * 获取模板详情
     */
    public static function getDetail(int $id): array
    {
        $template = AiTemplateModel::find($id);
        if (!$template) {
            return ['success' => false, 'msg' => '模板不存在'];
        }

        return [
            'success' => true,
            'data'    => $template,
        ];
    }

    /**
     * 创建模板
     */
    public static function create(array $data): array
    {
        try {
            // 校验必填字段
            if (empty($data['name'])) {
                return ['success' => false, 'msg' => '请输入模板名称'];
            }
            if (AiTemplateModel::where('name', $data['name'])->find()) {
                return ['success' => false, 'msg' => '模板名称已存在'];
            }

            $template = AiTemplateModel::create([
                'name'           => $data['name'],
                'description'    => $data['description'] ?? '',
                'generate_mode'  => $data['generate_mode'] ?? 'nlp',
                'cate_id'        => (int) ($data['cate_id'] ?? 0),
                'model_id'       => (int) ($data['model_id'] ?? 0),
                'style'          => $data['style'] ?? 'default',
                'title_rule'     => $data['title_rule'] ?? '',
                'content_rule'   => $data['content_rule'] ?? '',
                'keyword_hint'   => $data['keyword_hint'] ?? '',
                'fields_config'  => !empty($data['fields_config']) ? json_encode($data['fields_config'], JSON_UNESCAPED_UNICODE) : '',
                'image_config'   => !empty($data['image_config']) ? json_encode($data['image_config']) : json_encode(['thumb'=>'0','images'=>'0','count'=>0,'source'=>'0']),
                'publisher'      => $data['publisher'] ?? '',
                'contact'        => $data['contact'] ?? '',
                'example_title'  => $data['example_title'] ?? '',
                'example_content'=> $data['example_content'] ?? '',
                'default_batch'  => min(100, max(1, (int) ($data['default_batch'] ?? 10))),
                'status'         => (int) ($data['status'] ?? 1),
                'sort'           => (int) ($data['sort'] ?? 0),
            ]);

            return ['success' => true, 'msg' => '模板创建成功', 'data' => ['id' => $template->id]];
        } catch (\Exception $e) {
            Log::error('[AiTemplateService::create] ' . $e->getMessage());
            return ['success' => false, 'msg' => '创建失败: ' . $e->getMessage()];
        }
    }

    /**
     * 更新模板
     */
    public static function update(int $id, array $data): array
    {
        try {
            $template = AiTemplateModel::find($id);
            if (!$template) {
                return ['success' => false, 'msg' => '模板不存在'];
            }

            // 名称唯一性校验（排除自身）
            if (!empty($data['name']) && $data['name'] !== $template->name) {
                if (AiTemplateModel::where('name', $data['name'])->where('id', '<>', $id)->find()) {
                    return ['success' => false, 'msg' => '模板名称已存在'];
                }
            }

            $updateData = array_filter([
                'name'            => $data['name'] ?? null,
                'description'     => $data['description'] ?? null,
                'generate_mode'   => $data['generate_mode'] ?? null,
                'cate_id'         => isset($data['cate_id']) ? (int) $data['cate_id'] : null,
                'model_id'        => isset($data['model_id']) ? (int) $data['model_id'] : null,
                'style'           => $data['style'] ?? null,
                'title_rule'      => $data['title_rule'] ?? null,
                'content_rule'    => $data['content_rule'] ?? null,
                'keyword_hint'    => $data['keyword_hint'] ?? null,
                'fields_config'   => isset($data['fields_config']) ? json_encode($data['fields_config'], JSON_UNESCAPED_UNICODE) : null,
                'image_config'    => isset($data['image_config']) ? json_encode($data['image_config']) : null,
                'publisher'       => $data['publisher'] ?? null,
                'contact'         => $data['contact'] ?? null,
                'example_title'   => $data['example_title'] ?? null,
                'example_content' => $data['example_content'] ?? null,
                'default_batch'   => isset($data['default_batch']) ? min(100, max(1, (int) $data['default_batch'])) : null,
                'status'          => isset($data['status']) ? (int) $data['status'] : null,
                'sort'            => isset($data['sort']) ? (int) $data['sort'] : null,
            ], fn($v) => $v !== null);

            $template->save($updateData);

            return ['success' => true, 'msg' => '更新成功'];
        } catch (\Exception $e) {
            Log::error('[AiTemplateService::update] ' . $e->getMessage());
            return ['success' => false, 'msg' => '更新失败: ' . $e->getMessage()];
        }
    }

    /**
     * 删除模板
     */
    public static function delete(int $id): array
    {
        try {
            $template = AiTemplateModel::find($id);
            if (!$template) {
                return ['success' => false, 'msg' => '模板不存在'];
            }

            // 检查是否有关联的任务
            $taskCount = AiBatchTask::where('template_id', $id)->count();
            if ($taskCount > 0) {
                return ['success' => false, 'msg' => '该模板下有 ' . $taskCount . ' 个关联任务，无法删除'];
            }

            $template->delete();

            CacheService::clearByTag(CacheService::TAG_CONTENT);
            return ['success' => true, 'msg' => '删除成功'];
        } catch (\Exception $e) {
            Log::error('[AiTemplateService::delete] ' . $e->getMessage());
            return ['success' => false, 'msg' => '删除失败: ' . $e->getMessage()];
        }
    }

    /**
     * Prompt引擎 — 基于模板构建完整Prompt
     *
     * @param AiTemplateModel $template 模板对象
     * @param array $params 参数(keyword/model_id/cate_id/fields等)
     * @return string 完整Prompt字符串
     */
    public static function buildPrompt(AiTemplateModel $template, array $params): string
    {
        // 1. 写作风格 System Prompt（从 AiWritingService 获取）
        // 使用 getStyles() 列表重建 stylePrompt 映射
        $styleList = \app\common\service\AiWritingService::getStyles();
        $styleMap = [];
        foreach ($styleList as $s) {
            $styleMap[$s['key']] = $s['desc'] . '。' . $s['name'] . '风格';
        }
        $systemPrompt = $styleMap[$template->style] ?? ($styleMap['default'] ?? '你是一位专业的内容创作者。');

        $parts = [];
        $parts[] = $systemPrompt;

        // 2. 标题要求
        if (!empty($template->title_rule)) {
            $parts[] = "【标题要求】" . $template->title_rule;
        }

        // 3. 内容要求
        if (!empty($template->content_rule)) {
            $parts[] = "【内容要求】" . $template->content_rule;
        }

        // 4. 关键词/主题
        $keyword = $params['keyword'] ?? ($template->keyword_hint ?: '');
        if ($keyword) {
            $parts[] = "【主题关键词】" . $keyword;
        }

        // 5. 发布者信息
        $publisherInfo = '';
        if (!empty($template->publisher)) {
            $publisherInfo .= '作者：' . $template->publisher . '；';
        }
        if (!empty($template->contact)) {
            $publisherInfo .= '联系方式：' . $template->contact;
        }
        if ($publisherInfo) {
            $parts[] = "【发布信息】" . $publisherInfo;
        }

        // 6. 自定义字段
        $fields = $template->fields_array;
        foreach ($fields as $field) {
            $rule = $field['rule'] ?? '';
            $value = $params[$field['name']] ?? '';
            if ($rule) {
                $fieldText = "【{$field['name']}】" . $rule;
                if ($value) {
                    $fieldText .= "（当前值：{$value}）";
                }
                $parts[] = $fieldText;
            }
        }

        // 7. 输出格式指令
        $parts[] = "\n请按以下格式输出：\n## 文章标题\n\n正文内容（结构清晰、段落分明）";

        return implode("\n\n", $parts);
    }

    /**
     * 参考示例模式Prompt构建
     *
     * 合并为单次API调用：
     * 先分析示例文章风格特征 → 再按该风格撰写新文章
     *
     * @param AiTemplateModel $template 模板对象
     * @param string $keyword 目标关键词
     * @return string Prompt
     */
    public static function buildExamplePrompt(AiTemplateModel $template, string $keyword): string
    {
        $exampleContent = $template->example_content ?? '';
        $titleRule = $template->title_rule ?? '标题吸引人，包含核心关键词';
        $contentRule = $template->content_rule ?? '内容详实，结构完整，800-1500字';

        if (empty($exampleContent)) {
            // 示例为空时降级为普通NLP模式
            return self::buildPrompt($template, ['keyword' => $keyword]);
        }

        // 获取写作风格描述用于Prompt构建
        $styleList = \app\common\service\AiWritingService::getStyles();
        $styleDesc = '专业内容创作';
        foreach ($styleList as $s) {
            if ($s['key'] === ($template->style ?? 'default')) {
                $styleDesc = $s['desc'] . '（' . $s['name'] . '风格）';
                break;
            }
        }

        $prompt = "你是一位" . $styleDesc . "。请先仔细阅读以下示例文章，分析其写作风格特征（语气口吻、段落结构、用词习惯、句式长短、修辞手法），然后模仿此风格撰写一篇关于「{$keyword}」的新文章。\n\n";
        $prompt .= "--- 示例文章开始 ---\n";
        $prompt .= mb_substr($exampleContent, 0, 4000); // 防止过长截断
        if (mb_strlen($exampleContent) > 4000) {
            $prompt .= "\n...（示例文章已截断）";
        }
        $prompt .= "\n--- 示例文章结束 ---\n\n";

        // 注入模板规则
        if ($titleRule) {
            $prompt .= "标题要求：{$titleRule}\n";
        }
        if ($contentRule) {
            $prompt .= "内容要求：{$contentRule}\n";
        }

        // 自定义字段
        $fields = $template->fields_array;
        foreach ($fields as $field) {
            $rule = $field['rule'] ?? '';
            if ($rule) {
                $prompt .= "{$field['name']}要求：{$rule}\n";
            }
        }

        $prompt .= "\n请直接输出新文章，格式：\n## 文章标题\n\n正文内容";

        return $prompt;
    }

    /**
     * 使用模板发起批量生成
     *
     * @param int $templateId 模板ID
     * @param array $params {keywords(string), total(int), cate_id(int), model_id(int)}
     * @return array {success, msg, data: {task_id}}
     */
    public static function batchGenerate(int $templateId, array $params): array
    {
        try {
            $template = AiTemplateModel::find($templateId);
            if (!$template) {
                return ['success' => false, 'msg' => '模板不存在'];
            }
            if ($template->status !== 1) {
                return ['success' => false, 'msg' => '模板已禁用，无法使用'];
            }

            $keywords = trim($params['keywords'] ?? '');
            if (empty($keywords)) {
                return ['success' => false, 'msg' => '请输入至少一个关键词'];
            }

            $keywordList = array_filter(array_map('trim', explode("\n", $keywords)));
            if (empty($keywordList)) {
                return ['success' => false, 'msg' => '关键词格式不正确，每行一个'];
            }

            $total = min(100, max(1, (int) ($params['total'] ?? $template->default_batch)));
            if (count($keywordList) > $total) {
                $keywordList = array_slice($keywordList, 0, $total);
            }

            // 创建 AiBatchTask 记录，关联 template_id
            $task = AiBatchTask::create([
                'template_id' => $templateId,
                'title'       => $template->name . '_' . date('YmdHis'),
                'keywords'    => implode("\n", $keywordList),
                'style'       => $template->style,
                'cate_id'     => (int) ($params['cate_id'] ?? $template->cate_id),
                'model_id'    => (int) ($params['model_id'] ?? $template->model_id),
                'total'       => count($keywordList),
                'completed'   => 0,
                'status'      => 0, // 待执行
                'extra_data'  => json_encode([
                    'generate_mode' => $template->generate_mode,
                    'template_id'   => $templateId,
                ], JSON_UNESCAPED_UNICODE),
            ]);

            return [
                'success' => true,
                'msg'     => '批量生成任务已创建，共 ' . count($keywordList) . ' 篇',
                'data'    => ['task_id' => $task->id],
            ];
        } catch (\Exception $e) {
            Log::error('[AiTemplateService::batchGenerate] ' . $e->getMessage());
            return ['success' => false, 'msg' => '创建批量任务失败: ' . $e->getMessage()];
        }
    }

    /**
     * 获取分类列表（用于下拉选择）
     */
    public static function getCateList(): array
    {
        return Cate::where('status', 1)
            ->order('sort desc, id asc')
            ->field('id, name, parent_id')
            ->select()
            ->toArray();
    }

    /**
     * 获取AI模型列表（用于下拉选择）
     */
    public static function getModelList(): array
    {
        return \app\common\model\AiModel::where('status', 1)
            ->order('is_default desc, id asc')
            ->field('id, name, provider')
            ->select()
            ->toArray();
    }

    /**
     * 获取写作风格列表
     */
    public static function getStyleList(): array
    {
        return \app\common\service\AiWritingService::getStyles();
    }
}
