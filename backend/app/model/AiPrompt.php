<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI Prompt模板模型
 */
class AiPrompt extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_ai_prompts';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 类型转换
     */
    protected $type = [
        'status' => 'integer',
        'type' => 'string',
        'is_default' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 模板类型常量
     */
    const TYPE_ARTICLE = 'article';           // 文章生成
    const TYPE_TITLE = 'title';               // 标题生成
    const TYPE_SUMMARY = 'summary';           // 摘要生成
    const TYPE_TAG = 'tag';                   // 标签生成
    const TYPE_KEYWORD = 'keyword';           // 关键词生成
    const TYPE_OPTIMIZE = 'optimize';        // 内容优化
    const TYPE_GEO_CHECK = 'geo_check';       // 地理核查
    const TYPE_SEO = 'seo';                   // SEO优化

    /**
     * 获取模板类型文本
     */
    public function getTypeText(): string
    {
        $types = [
            self::TYPE_ARTICLE => '文章生成',
            self::TYPE_TITLE => '标题生成',
            self::TYPE_SUMMARY => '摘要生成',
            self::TYPE_TAG => '标签生成',
            self::TYPE_KEYWORD => '关键词生成',
            self::TYPE_OPTIMIZE => '内容优化',
            self::TYPE_GEO_CHECK => '地理核查',
            self::TYPE_SEO => 'SEO优化',
        ];
        return $types[$this->type] ?? '未知';
    }

    /**
     * 渲染模板
     */
    public function render(array $variables = []): string
    {
        $template = $this->template;
        
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        return $template;
    }

    /**
     * 获取指定类型的模板
     */
    public static function getByType(string $type): array
    {
        return self::where('type', '=', $type)
            ->where('status', '=', 1)
            ->order('is_default', 'desc')
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取默认模板
     */
    public static function getDefaultByType(string $type): ?AiPrompt
    {
        return self::where('type', '=', $type)
            ->where('status', '=', 1)
            ->where('is_default', '=', 1)
            ->find();
    }

    /**
     * 设置为默认
     */
    public function setAsDefault(): bool
    {
        // 取消同类型其他默认
        self::where('type', '=', $this->type)
            ->where('is_default', '=', 1)
            ->update(['is_default' => 0]);
        
        $this->is_default = 1;
        return $this->save();
    }

    /**
     * 获取模板列表
     */
    public static function getList(): array
    {
        $prompts = self::where('status', '=', 1)
            ->order('type', 'asc')
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
        
        // 按类型分组
        $grouped = [];
        foreach ($prompts as $prompt) {
            $type = $prompt['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'name' => self::getTypeName($type),
                    'templates' => [],
                ];
            }
            $grouped[$type]['templates'][] = $prompt;
        }
        
        return array_values($grouped);
    }

    /**
     * 获取类型名称
     */
    protected static function getTypeName(string $type): string
    {
        $types = [
            self::TYPE_ARTICLE => '文章生成',
            self::TYPE_TITLE => '标题生成',
            self::TYPE_SUMMARY => '摘要生成',
            self::TYPE_TAG => '标签生成',
            self::TYPE_KEYWORD => '关键词生成',
            self::TYPE_OPTIMIZE => '内容优化',
            self::TYPE_GEO_CHECK => '地理核查',
            self::TYPE_SEO => 'SEO优化',
        ];
        return $types[$type] ?? $type;
    }

    /**
     * 获取模板信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_text' => $this->getTypeText(),
            'template' => $this->template,
            'description' => $this->description,
            'variables' => json_decode($this->variables, true) ?? [],
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'is_default' => $this->is_default,
            'status' => $this->status,
            'sort' => $this->sort,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}
