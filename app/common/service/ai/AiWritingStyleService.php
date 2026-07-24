<?php
declare(strict_types=1);

namespace app\common\service\ai;

/**
 * AI写作风格管理服务 — V2.9.30 AI2-4
 * 6种写作风格 + 行业特化prompt
 */
class AiWritingStyleService
{
    public const STYLES = [
        'formal' => [
            'name' => '正式',
            'description' => '严谨、专业的书面语风格',
            'prompt' => '请用正式、专业的语气撰写',
        ],
        'casual' => [
            'name' => '轻松',
            'description' => '亲切、自然的日常语气风格',
            'prompt' => '请用轻松、亲切的语气撰写',
        ],
        'professional' => [
            'name' => '专业',
            'description' => '行业专家视角，深度分析风格',
            'prompt' => '请以行业专家身份，用专业术语撰写',
        ],
        'news' => [
            'name' => '资讯',
            'description' => '新闻资讯风格，简洁、客观',
            'prompt' => '请以新闻资讯风格撰写，标题简洁有力',
        ],
        'tutorial' => [
            'name' => '教程',
            'description' => '步骤清晰、易于理解的教程风格',
            'prompt' => '请以教程风格撰写，步骤清晰，附示例',
        ],
        'marketing' => [
            'name' => '营销',
            'description' => '有说服力、引导转化的推广风格',
            'prompt' => '请以营销推广风格撰写，突出卖点',
        ],
    ];

    public const INDUSTRY_PROMPTS = [
        'article' => [
            'formal' => '请以正式风格撰写一篇关于{title}的专业文章，引用行业数据支撑观点',
            'news' => '请以资讯风格报道{title}，包含时间、地点、事件经过',
            'tutorial' => '请以教程风格撰写{title}的详细指南，分步骤说明',
            'marketing' => '请以营销风格撰写{title}的推广文案，突出价值主张',
        ],
        'product' => [
            'marketing' => '请以营销风格撰写{title}的产品介绍，突出差异化卖点',
            'tutorial' => '请以教程风格撰写{title}的使用指南，分步骤说明',
            'professional' => '请以专业风格撰写{title}的产品评测，包含技术参数',
        ],
        'download' => [
            'tutorial' => '请以教程风格撰写{title}的下载安装指南',
            'formal' => '请以正式风格介绍{title}的功能特点和系统要求',
        ],
        'image' => [
            'professional' => '请以专业风格撰写{title}的图片说明，包含技术参数',
            'casual' => '请以轻松风格描述{title}的图片内容',
        ],
        'video' => [
            'news' => '请以资讯风格撰写{title}的视频简介',
            'casual' => '请以轻松风格介绍{title}的视频内容',
        ],
    ];

    /**
     * 获取所有可用风格
     */
    public function getStyles(): array
    {
        return self::STYLES;
    }

    /**
     * 获取指定风格的prompt模板
     */
    public function getPrompt(string $styleKey, string $industry, string $title): string
    {
        if (isset(self::INDUSTRY_PROMPTS[$industry][$styleKey])) {
            return str_replace('{title}', $title, self::INDUSTRY_PROMPTS[$industry][$styleKey]);
        }
        $basePrompt = self::STYLES[$styleKey]['prompt'] ?? '请撰写';
        return $basePrompt . '关于' . $title . '的内容';
    }

    /**
     * 生成风格预览样例
     */
    public function getPreviewSample(string $styleKey): string
    {
        $samples = [
            'formal' => '根据最新行业数据显示，2026年人工智能市场规模预计突破万亿级别...',
            'casual' => '嘿，大家好！今天我们来聊聊AI的最新进展，其实没有想象中那么复杂...',
            'professional' => '从技术架构层面分析，当前大语言模型存在三大核心挑战：算力瓶颈、数据质量和对齐问题...',
            'news' => '2026年7月9日，北京 — 人工智能领域迎来重大突破，多家企业联合发布新一代AI标准...',
            'tutorial' => '第一步：打开后台管理系统。第二步：在左侧菜单找到"内容管理"。第三步：点击"新建内容"按钮...',
            'marketing' => '还在为内容创作效率低而烦恼？AI-CMS全新升级，让您的内容产出效率提升10倍！',
        ];
        return $samples[$styleKey] ?? '';
    }
}
