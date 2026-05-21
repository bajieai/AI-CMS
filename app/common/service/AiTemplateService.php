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
        if (!empty($filter['source'])) {
            $query->where('source', $filter['source']);
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
                'name'             => $data['name'],
                'description'      => $data['description'] ?? '',
                'nl_description'   => $data['nl_description'] ?? '', // V2.9.9新增
                'generate_mode'    => $data['generate_mode'] ?? 'nlp',
                'cate_id'          => (int) ($data['cate_id'] ?? 0),
                'model_id'         => (int) ($data['model_id'] ?? 0),
                'style'            => $data['style'] ?? 'default',
                'title_rule'       => $data['title_rule'] ?? '',
                'content_rule'     => $data['content_rule'] ?? '',
                'keyword_hint'     => $data['keyword_hint'] ?? '',
                'fields_config'    => !empty($data['fields_config']) ? json_encode($data['fields_config'], JSON_UNESCAPED_UNICODE) : '',
                'image_config'     => !empty($data['image_config']) ? json_encode($data['image_config']) : json_encode(['thumb'=>'0','images'=>'0','count'=>0,'source'=>'0']),
                'field_mapping'    => !empty($data['field_mapping']) ? json_encode($data['field_mapping'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE),
                'quality_config'   => !empty($data['quality_config']) ? json_encode($data['quality_config'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE),
                'publisher'        => $data['publisher'] ?? '',
                'contact'          => $data['contact'] ?? '',
                'example_title'    => $data['example_title'] ?? '',
                'example_content'  => $data['example_content'] ?? '',
                'default_batch'    => min(100, max(1, (int) ($data['default_batch'] ?? 10))),
                'status'           => (int) ($data['status'] ?? 1),
                'sort'             => (int) ($data['sort'] ?? 0),
                'source'           => $data['source'] ?? 'custom', // V2.9.9新增
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
                'name'              => $data['name'] ?? null,
                'description'       => $data['description'] ?? null,
                'nl_description'    => $data['nl_description'] ?? null, // V2.9.9新增
                'generate_mode'     => $data['generate_mode'] ?? null,
                'cate_id'           => isset($data['cate_id']) ? (int) $data['cate_id'] : null,
                'model_id'          => isset($data['model_id']) ? (int) $data['model_id'] : null,
                'style'             => $data['style'] ?? null,
                'title_rule'        => $data['title_rule'] ?? null,
                'content_rule'      => $data['content_rule'] ?? null,
                'keyword_hint'      => $data['keyword_hint'] ?? null,
                'fields_config'     => isset($data['fields_config']) ? json_encode($data['fields_config'], JSON_UNESCAPED_UNICODE) : null,
                'image_config'      => isset($data['image_config']) ? json_encode($data['image_config']) : null,
                'field_mapping'     => isset($data['field_mapping']) ? json_encode($data['field_mapping'], JSON_UNESCAPED_UNICODE) : null,
                'quality_config'    => isset($data['quality_config']) ? json_encode($data['quality_config'], JSON_UNESCAPED_UNICODE) : null,
                'publisher'         => $data['publisher'] ?? null,
                'contact'           => $data['contact'] ?? null,
                'example_title'     => $data['example_title'] ?? null,
                'example_content'   => $data['example_content'] ?? null,
                'default_batch'     => isset($data['default_batch']) ? min(100, max(1, (int) $data['default_batch'])) : null,
                'status'            => isset($data['status']) ? (int) $data['status'] : null,
                'sort'              => isset($data['sort']) ? (int) $data['sort'] : null,
                'source'            => $data['source'] ?? null, // V2.9.9新增
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

        // V2.9.9: 如果有自然语言描述，优先注入作为高层意图
        if (!empty($template->nl_description)) {
            $parts[] = "【模板意图】" . $template->nl_description;
        }

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

    /**
     * V2.9.9: 根据自然语言描述生成字段配置Schema
     * 使用AI解析自然语言意图，返回结构化字段定义
     *
     * @param string $nlDescription 自然语言描述
     * @return array {success, msg, data: {fields_config, title_rule, content_rule, keyword_hint}}
     */
    public static function generateFieldsFromNL(string $nlDescription): array
    {
        if (empty($nlDescription)) {
            return ['success' => false, 'msg' => '请输入自然语言描述'];
        }

        try {
            // 构建AI Prompt，要求返回JSON格式的字段定义
            $systemPrompt = '你是一位CMS模板设计专家。请根据用户的自然语言描述，生成对应的内容模板字段配置。';
            $userPrompt = "用户需求：{$nlDescription}\n\n";
            $userPrompt .= "请返回JSON格式（不要包含markdown代码块标记）：\n";
            $userPrompt .= json_encode([
                'fields_config' => [
                    [
                        'name' => '字段标识（英文小写+下划线）',
                        'label' => '字段显示名称',
                        'type' => '字段类型（text/textarea/number/select/radio/checkbox/date/image/file/rich_editor）',
                        'required' => true,
                        'placeholder' => '输入提示',
                        'options' => ['选项1', '选项2'],
                        'rule' => '字段填写要求描述',
                    ],
                ],
                'title_rule' => '标题生成规则描述',
                'content_rule' => '内容生成规则描述',
                'keyword_hint' => '关键词提示',
                'description' => '模板用途简述（1句话）',
            ], JSON_UNESCAPED_UNICODE);
            $userPrompt .= "\n\n注意：\n1. 根据用户需求推断需要哪些字段\n2. 字段类型必须是以下之一：text, textarea, number, select, radio, checkbox, date, image, file, rich_editor\n3. 返回纯JSON，不要markdown代码块\n4. 至少包含2个字段，最多8个字段";

            // 调用AI服务
            $aiService = new \app\common\service\AiService();
            $aiResult = $aiService->generate($systemPrompt, 'continue', ['max_tokens' => 2048, 'prompt' => $userPrompt]);

            if (empty($aiResult['content'])) {
                return ['success' => false, 'msg' => 'AI返回为空，请稍后重试'];
            }

            // 清理并解析JSON
            $jsonStr = $aiResult['content'];
            $jsonStr = preg_replace('/^```json\s*/i', '', $jsonStr);
            $jsonStr = preg_replace('/```\s*$/i', '', $jsonStr);
            $jsonStr = trim($jsonStr);

            $schema = json_decode($jsonStr, true);
            if (!is_array($schema) || empty($schema['fields_config'])) {
                return ['success' => false, 'msg' => 'AI返回格式不正确，请手动配置字段'];
            }

            // 校验并清理字段配置
            $fields = [];
            foreach ($schema['fields_config'] as $idx => $field) {
                $validTypes = ['text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date', 'image', 'file', 'rich_editor'];
                $type = in_array($field['type'] ?? '', $validTypes) ? $field['type'] : 'text';
                $fields[] = [
                    'name' => $field['name'] ?? 'field_' . ($idx + 1),
                    'label' => $field['label'] ?? ($field['name'] ?? '字段' . ($idx + 1)),
                    'type' => $type,
                    'required' => !empty($field['required']),
                    'placeholder' => $field['placeholder'] ?? '',
                    'options' => $field['options'] ?? [],
                    'rule' => $field['rule'] ?? '',
                ];
            }

            return [
                'success' => true,
                'msg' => 'AI字段生成成功',
                'data' => [
                    'fields_config' => $fields,
                    'title_rule' => $schema['title_rule'] ?? '',
                    'content_rule' => $schema['content_rule'] ?? '',
                    'keyword_hint' => $schema['keyword_hint'] ?? '',
                    'description' => $schema['description'] ?? '',
                ],
            ];
        } catch (\Exception $e) {
            Log::error('[AiTemplateService::generateFieldsFromNL] ' . $e->getMessage());
            return ['success' => false, 'msg' => '生成失败: ' . $e->getMessage()];
        }
    }

    // ==================== V2.9 字段映射引擎 ====================

    /**
     * 构建带字段映射指令的结构化Prompt
     *
     * 让AI按指定格式输出JSON，便于后续自动映射到CMS字段
     *
     * @param AiTemplateModel $template 模板对象
     * @param array $params 用户输入参数（含variables变量替换）
     * @return string 结构化Prompt
     */
    public static function buildStructuredPrompt(AiTemplateModel $template, array $params): string
    {
        $mapping = $template->field_mapping_array;
        $mappings = $mappings = $mapping['mappings'] ?? [];
        $variables = $mapping['variables'] ?? [];

        // 替换变量
        $variableValues = [];
        foreach ($variables as $var) {
            $varName = $var['name'] ?? '';
            $variableValues[$varName] = $params[$varName] ?? $var['default'] ?? '';
        }

        // 1. System Prompt：写作风格
        $styleList = \app\common\service\AiWritingService::getStyles();
        $styleMap = [];
        foreach ($styleList as $s) {
            $styleMap[$s['key']] = $s['desc'] . '。' . $s['name'] . '风格';
        }
        $systemPrompt = $styleMap[$template->style] ?? ($styleMap['default'] ?? '你是一位专业的内容创作者。');

        $parts = [];
        $parts[] = $systemPrompt;

        // 2. 标题和内容要求
        if (!empty($template->title_rule)) {
            $parts[] = "【标题要求】" . self::replaceVariables($template->title_rule, $variableValues);
        }
        if (!empty($template->content_rule)) {
            $parts[] = "【内容要求】" . self::replaceVariables($template->content_rule, $variableValues);
        }

        // 3. 关键词
        $keyword = $params['keyword'] ?? ($template->keyword_hint ?: '');
        if ($keyword) {
            $parts[] = "【主题关键词】" . $keyword;
        }

        // 4. 发布者信息
        $publisherInfo = '';
        if (!empty($template->publisher)) {
            $publisherInfo .= '作者：' . self::replaceVariables($template->publisher, $variableValues) . '；';
        }
        if (!empty($template->contact)) {
            $publisherInfo .= '联系方式：' . self::replaceVariables($template->contact, $variableValues);
        }
        if ($publisherInfo) {
            $parts[] = "【发布信息】" . $publisherInfo;
        }

        // 5. 自定义字段
        $fields = $template->fields_array;
        foreach ($fields as $field) {
            $rule = $field['rule'] ?? '';
            if ($rule) {
                $parts[] = "【{$field['name']}】" . self::replaceVariables($rule, $variableValues);
            }
        }

        // 6. 结构化输出指令（核心）
        if (!empty($mappings)) {
            $parts[] = self::buildOutputFormatInstruction($mappings);
        }

        // 7. 表单联动：注入表单结构，让AI输出 form_data（V2.9 M2新增）
        $fieldsConfig = $template->fields_array;
        if (!empty($fieldsConfig)) {
            $formSchema = self::buildFormSchema($fieldsConfig);
            $parts[] = "【表单字段填充要求】\n" .
                "请根据以下表单字段结构，在输出JSON中增加 `form_data` 字段，\n" .
                "其值为对象，键为字段名，值为AI生成的对应内容：\n" .
                $formSchema . "\n" .
                "注意：数值类型字段只输出数字，多选类型输出数组（JSON数组格式），单选择输出字符串。";
        }

        // 无字段映射且无表单时，使用默认格式
        if (empty($mappings) && empty($fieldsConfig)) {
            $parts[] = "\n请按以下格式输出：\n## 文章标题\n\n正文内容（结构清晰、段落分明）";
        }

        return implode("\n\n", $parts);
    }

    /**
     * 构建表单结构描述（用于注入Prompt）
     */
    private static function buildFormSchema(array $fieldsConfig): string
    {
        $lines = [];
        foreach ($fieldsConfig as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'text';
            $rule = $field['rule'] ?? '';

            $typeHint = match ($type) {
                'number'  => '（数值类型，只输出数字）',
                'select'  => '（单选择类型，输出选项值字符串）',
                'date'    => '（日期类型，格式：YYYY-MM-DD）',
                default   => '（文本类型）',
            };

            $line = "- {$name} {$typeHint}";
            if ($rule) {
                $line .= "；规则：{$rule}";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * 将AI输出按字段映射规则转换为CMS字段数组
     *
     * @param AiTemplateModel $template 模板对象
     * @param string|array $aiOutput AI输出（JSON字符串或数组）
     * @return array CMS字段键值对 ['title' => ..., 'content' => ..., 'seo_title' => ..., ...]
     */
    public static function applyFieldMapping(AiTemplateModel $template, $aiOutput): array
    {
        $mapping = $template->field_mapping_array;
        $mappings = $mapping['mappings'] ?? [];

        // 解析AI输出为数组
        if (is_string($aiOutput)) {
            // 尝试解析JSON
            $decoded = json_decode($aiOutput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $aiData = $decoded;
            } else {
                // 非JSON：尝试从文本中提取标题和内容
                $aiData = self::parsePlainTextOutput($aiOutput);
            }
        } else {
            $aiData = is_array($aiOutput) ? $aiOutput : [];
        }

        $result = [];

        // 如果没有配置映射，使用默认映射
        if (empty($mappings)) {
            $result['title']   = $aiData['title'] ?? $aiData['文章标题'] ?? '未命名文章';
            $result['content'] = $aiData['content'] ?? $aiData['正文内容'] ?? (is_string($aiOutput) ? $aiOutput : '');
            return $result;
        }

        // 按映射规则转换
        foreach ($mappings as $map) {
            $aiField    = $map['ai_output_field'] ?? '';
            $cmsField   = $map['cms_field'] ?? '';
            $transform  = $map['transform_rule'] ?? 'none';

            if (empty($cmsField) || empty($aiField)) {
                continue;
            }

            $value = $aiData[$aiField] ?? null;
            if ($value === null) {
                continue;
            }

            // 应用转换规则
            $value = self::applyTransform($value, $transform, $aiData);

            $result[$cmsField] = $value;
        }

        // 确保至少有title和content
        if (empty($result['title'])) {
            $result['title'] = $aiData['title'] ?? $aiData['文章标题'] ?? '未命名文章';
        }
        if (empty($result['content'])) {
            $result['content'] = $aiData['content'] ?? $aiData['正文内容'] ?? '';
        }

        // V2.9 M2 新增：提取 form_data（表单联动）
        if (!empty($aiData['form_data']) && is_array($aiData['form_data'])) {
            $result['form_data'] = $aiData['form_data'];
        }

        return $result;
    }

    /**
     * 校验 field_mapping 结构是否合法
     *
     * @param array $mapping field_mapping数组
     * @return array {valid, errors}
     */
    public static function validateFieldMapping(array $mapping): array
    {
        $errors = [];

        $mappings = $mapping['mappings'] ?? [];
        if (!is_array($mappings)) {
            $errors[] = 'mappings 必须是数组';
        } else {
            foreach ($mappings as $idx => $map) {
                if (empty($map['ai_output_field'])) {
                    $errors[] = "mappings[{$idx}]: ai_output_field 不能为空";
                }
                if (empty($map['cms_field'])) {
                    $errors[] = "mappings[{$idx}]: cms_field 不能为空";
                }
                // cms_field 必须是content表的合法字段
                $allowedCmsFields = [
                    'title', 'content', 'seo_title', 'seo_keywords', 'seo_description',
                    'summary', 'tags', 'source', 'author',
                ];
                if (!empty($map['cms_field']) && !in_array($map['cms_field'], $allowedCmsFields)) {
                    $errors[] = "mappings[{$idx}]: cms_field '{$map['cms_field']}' 不是允许的字段（允许: " . implode(', ', $allowedCmsFields) . ")";
                }
            }
        }

        $variables = $mapping['variables'] ?? [];
        if (!is_array($variables)) {
            $errors[] = 'variables 必须是数组';
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * 应用质量配置：检查AI输出质量，决定是否重试或拒绝
     *
     * @param AiTemplateModel $template 模板对象
     * @param array $cmsData 已映射的CMS字段数据
     * @return array {passed, score, action, retry_suggested}
     */
    public static function applyQualityConfig(AiTemplateModel $template, array $cmsData): array
    {
        $config = $template->quality_config_array;
        $minScore    = $config['min_score'] ?? 70;
        $maxRetry    = $config['max_retry'] ?? 2;
        $action      = $config['action_on_low_quality'] ?? 'notify';
        $checkItems  = $config['check_items'] ?? ['spelling', 'readability', 'seo'];

        // 调用 AiService 进行质量检测（非静态方法，需创建实例）
        $aiService = new \app\common\service\AiService();
        $qualityResult = $aiService->evaluateContentQuality(
            $cmsData['content'] ?? '',
            $checkItems
        );

        $score = $qualityResult['score'] ?? 0;

        if ($score >= $minScore) {
            return [
                'passed'          => true,
                'score'           => $score,
                'action'          => 'accept',
                'retry_suggested' => false,
            ];
        }

        // 质量不达标
        return [
            'passed'          => false,
            'score'           => $score,
            'action'          => $action,  // notify / auto_retry / reject
            'retry_suggested' => ($action === 'auto_retry'),
            'min_score'       => $minScore,
        ];
    }

    /**
     * 预览生成效果（V2.9新增）
     *
     * @param int   $templateId 模板ID
     * @param string $keyword    测试关键词
     * @param array $params     额外参数（含variables）
     * @return array {success, data: {title, content, quality_score, word_count}, msg}
     */
    public static function preview(int $templateId, string $keyword, array $params = []): array
    {
        try {
            $template = AiTemplateModel::find($templateId);
            if (!$template) {
                return ['success' => false, 'msg' => '模板不存在'];
            }

            // 构建变量
            $variables = $params['variables'] ?? [];
            // 系统变量
            $variables['site_name']     = \think\facade\Config::get('site.site_name', 'AI-CMS');
            $variables['category_name'] = $params['category_name'] ?? '';
            $variables['date']           = date('Y-m-d');

            // 替换 prompt 中的变量
            $prompt = self::buildStructuredPrompt($template, array_merge($params, ['keyword' => $keyword]));

            // 调用 AI 生成
            $aiService = new \app\common\service\AiService();
            $aiOutput = $aiService->generate($prompt, 'continue', [
                'max_tokens'   => 2048,
                'temperature'  => 0.7,
            ]);

            if (empty($aiOutput)) {
                return ['success' => false, 'msg' => 'AI生成内容为空，请检查模板配置'];
            }

            // 应用字段映射
            $cmsData = self::applyFieldMapping($template, $aiOutput);

            // 质量检测
            $qualityConfig = $template->quality_config_array;
            $qualityService = $aiService;
            $qualityResult = $qualityService->evaluateContentQuality(
                $cmsData['content'] ?? $aiOutput,
                $qualityConfig['check_items'] ?? ['spelling', 'readability', 'seo']
            );

            return [
                'success'        => true,
                'data'           => [
                    'title'          => $cmsData['title'] ?? '未命名文章',
                    'content'        => $cmsData['content'] ?? $aiOutput,
                    'quality_score'  => $qualityResult['overall_score'] ?? 0,
                    'word_count'     => mb_strlen(strip_tags($cmsData['content'] ?? $aiOutput)),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('[AiTemplateService::preview] ' . $e->getMessage());
            return ['success' => false, 'msg' => '预览失败: ' . $e->getMessage()];
        }
    }

    // ==================== 私有辅助方法 ====================

    /**
     * 替换文本中的变量占位符 {variable_name}
     */
    private static function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }
        // 替换系统变量
        $text = str_replace('{site_name}', \think\facade\Config::get('site.site_name', 'AI-CMS'), $text);
        $text = str_replace('{category_name}', $variables['category_name'] ?? '', $text);
        $text = str_replace('{date}', date('Y-m-d'), $text);
        return $text;
    }

    /**
     * 构建结构化输出格式指令
     */
    private static function buildOutputFormatInstruction(array $mappings): string
    {
        $aiFields = [];
        foreach ($mappings as $map) {
            $aiField = $map['ai_output_field'] ?? '';
            if ($aiField && !in_array($aiField, $aiFields)) {
                $aiFields[] = $aiField;
            }
        }

        $instruction = "【输出格式要求】\n";
        $instruction .= "请严格按照以下JSON格式输出，不要输出任何解释性文字：\n";
        $instruction .= "```json\n{\n";
        foreach ($aiFields as $field) {
            $instruction .= "  \"{$field}\": \"...\",\n";
        }
        $instruction = rtrim($instruction, ",\n") . "\n";
        $instruction .= "}\n```";

        return $instruction;
    }

    /**
     * 从纯文本输出中解析标题和内容
     */
    private static function parsePlainTextOutput(string $text): array
    {
        $result = [];

        // 尝试提取 ## 标题
        if (preg_match('/^##\s+(.+?)$/m', $text, $matches)) {
            $result['title'] = trim($matches[1]);
            // 移除标题行
            $content = preg_replace('/^##\s+.+?$/m', '', $text, 1);
            $result['content'] = trim($content);
        } else {
            // 取第一行作为标题
            $lines = explode("\n", $text, 2);
            $result['title']   = trim($lines[0]);
            $result['content'] = trim($lines[1] ?? $text);
        }

        return $result;
    }

    /**
     * 应用字段转换规则
     */
    private static function applyTransform($value, string $rule, array $aiData)
    {
        switch ($rule) {
            case 'strip_html':
                return strip_tags((string) $value);

            case 'truncate_100':
                return mb_substr((string) $value, 0, 100);

            case 'truncate_200':
                return mb_substr((string) $value, 0, 200);

            case 'to_seo_keywords':
                // 将逗号分隔的字符串转为JSON数组
                if (is_string($value)) {
                    return json_encode(array_map('trim', explode(',', $value)), JSON_UNESCAPED_UNICODE);
                }
                return $value;

            case 'extract_summary':
                // 取内容前150字作为摘要
                $content = is_string($value) ? $value : ($aiData['content'] ?? '');
                return mb_substr(strip_tags($content), 0, 150);

            case 'none':
            default:
                return $value;
        }
    }
}
