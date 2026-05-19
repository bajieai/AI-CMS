<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
/**
 * V2.9.8 A-1: 行业风格设计模式配置
 * 
 * 用于AI模板生成时，按行业选择对应的设计模式指引
 * 每个行业包含：色彩方案、排版规范、组件模式、氛围定位、关键CSS元素
 * 
 * 评分器双线制：新模板65分/历史模板60分
 */
return [
    'industries' => [
        'corporate' => [
            'name' => '企业官网',
            'design_patterns' => [
                'color_schema' => '冷色系为主（蓝/灰/白），主色饱和度高（如 #2563EB），辅色为浅蓝渐变',
                'typography' => '字形工整，标题粗体(700)，正文16px行距1.8，导航14px大写字母间距',
                'components' => '全宽Hero大Banner+三列卡片式特色区+关于区（左图右文）+团队展示区+底部三列信息+联系表单',
                'atmosphere' => '专业感、可信感、现代简约、商务正式',
            ],
            'css_key_elements' => [
                'hero_full_width' => true,
                'card_grid_3col' => true,
                'gradient_primary' => true,
                'nav_sticky' => true,
                'section_alternating' => true,
            ],
            'color_suggestions' => [
                'primary' => '#2563EB',
                'primary_light' => '#DBEAFE',
                'primary_dark' => '#1E40AF',
                'bg' => '#FFFFFF',
                'bg_section' => '#F8FAFC',
                'text' => '#1E293B',
                'text_muted' => '#64748B',
            ],
        ],
        'ecommerce' => [
            'name' => '电商',
            'design_patterns' => [
                'color_schema' => '暖色系为主（红/橙/白），CTA按钮醒目（#F97316或#EF4444），价格标签加粗突出',
                'typography' => '标题展示型字体，价格加粗突出，促销标签大写字母，导航简洁分类清晰',
                'components' => '促销Banner+商品网格(3-4列)+购物车图标+分类导航侧栏+搜索栏+价格/促销标签+快速购买按钮',
                'atmosphere' => '热闹感、促销感、信任感、购买欲',
            ],
            'css_key_elements' => [
                'product_grid' => true,
                'cta_highlight' => true,
                'price_tag' => true,
                'badge_sale' => true,
                'search_bar' => true,
            ],
            'color_suggestions' => [
                'primary' => '#F97316',
                'primary_light' => '#FFEDD5',
                'primary_dark' => '#EA580C',
                'bg' => '#FFFFFF',
                'bg_section' => '#FFF7ED',
                'text' => '#1F2937',
                'text_muted' => '#6B7280',
            ],
        ],
        'blog' => [
            'name' => '博客',
            'design_patterns' => [
                'color_schema' => '浅色背景+主色点缀，阅读体验优先，主色温和（如#059669或#8B5CF6）',
                'typography' => '正文大字号(16-18px)，高行距(1.8-2.0)，标题间距宽松，引用块特殊样式',
                'components' => '文章卡片列表+侧边栏(热门/标签云)+作者简介区+评论区+面包屑导航+阅读进度条',
                'atmosphere' => '文艺感、舒适阅读、极简、知识分享',
            ],
            'css_key_elements' => [
                'article_card' => true,
                'sidebar_widget' => true,
                'tag_cloud' => true,
                'reading_width' => true,
                'breadcrumb' => true,
            ],
            'color_suggestions' => [
                'primary' => '#059669',
                'primary_light' => '#D1FAE5',
                'primary_dark' => '#047857',
                'bg' => '#FFFFFF',
                'bg_section' => '#F9FAFB',
                'text' => '#111827',
                'text_muted' => '#6B7280',
            ],
        ],
        'portal' => [
            'name' => '门户',
            'design_patterns' => [
                'color_schema' => '主色+辅色双色系统，信息密度高，主色稳重（如#1D4ED8），辅色用于分类标识',
                'typography' => '小字号多层级(12-16px)，导航项多(8-12项)，信息密集排列，标题层级分明',
                'components' => '顶部多级导航+多栏内容区+滚动新闻条+链接矩阵+搜索框+热门推荐+快速入口',
                'atmosphere' => '资讯感、信息量感、权威感、时效性',
            ],
            'css_key_elements' => [
                'multi_column' => true,
                'news_ticker' => true,
                'link_matrix' => true,
                'dense_layout' => true,
                'mega_nav' => true,
            ],
            'color_suggestions' => [
                'primary' => '#1D4ED8',
                'primary_light' => '#DBEAFE',
                'primary_dark' => '#1E3A8A',
                'bg' => '#FFFFFF',
                'bg_section' => '#F1F5F9',
                'text' => '#0F172A',
                'text_muted' => '#475569',
            ],
        ],

        'medical' => [
            'name' => '医疗',
            'design_patterns' => [
                'color_schema' => '清洁感为主（白/蓝绿/浅灰），主色低饱和度（如 #0EA5E9 或 #14B8A6），辅色用于信任标识',
                'typography' => '字形清晰易读，标题16-20px，正文14-16px行距1.8，导航简洁',
                'components' => '顶部导航+Hero信任区(资质/口碑)+服务介绍卡片+科室/项目列表+医生/团队展示+预约表单+患者评价区+页脚联系',
                'atmosphere' => '专业感、信任感、清洁感、安心感',
            ],
            'css_key_elements' => [
                'hero_full_width' => true,
                'card_grid_3col' => true,
                'gradient_primary' => false,
                'nav_sticky' => true,
                'section_alternating' => true,
                'trust_badge' => true,
                'appointment_form' => true,
            ],
            'color_suggestions' => [
                'primary' => '#0EA5E9',
                'primary_light' => '#E0F2FE',
                'primary_dark' => '#0284C7',
                'bg' => '#FFFFFF',
                'bg_section' => '#F8FAFC',
                'text' => '#1E293B',
                'text_muted' => '#64748B',
            ],
        ],
        'education' => [
            'name' => '教育',
            'design_patterns' => [
                'color_schema' => '活力感为主（蓝/橙/白），主色明亮（如 #3B82F6 或 #F59E0B），辅色用于课程分类标识',
                'typography' => '标题醒目粗体，正文16px行距1.8，标签/徽章突出，信息层级清晰',
                'components' => '顶部导航+Hero号召区(课程/活动)+课程卡片列表+师资力量展示+学员成果/评价+报名入口+FAQ手风琴+页脚校区信息',
                'atmosphere' => '活力感、知识感、信任感、成长感',
            ],
            'css_key_elements' => [
                'hero_full_width' => true,
                'card_grid_3col' => true,
                'gradient_primary' => true,
                'nav_sticky' => true,
                'section_alternating' => true,
                'course_card' => true,
                'accordion_faq' => true,
            ],
            'color_suggestions' => [
                'primary' => '#3B82F6',
                'primary_light' => '#DBEAFE',
                'primary_dark' => '#1D4ED8',
                'bg' => '#FFFFFF',
                'bg_section' => '#FEF3C7',
                'text' => '#1E293B',
                'text_muted' => '#64748B',
            ],
        ],
        'catering' => [
            'name' => '餐饮',
            'design_patterns' => [
                'color_schema' => '食欲感为主（橙/红/暖黄/白），主色温暖（如 #F97316 或 #EF4444），辅色用于食材/口味标签',
                'typography' => '标题有食欲感(略圆润)，正文16px，价格/促销加粗，导航分类清晰',
                'components' => '顶部导航+Hero大图Banner(菜品/环境)+菜品网格卡片(价格/标签)+特色推荐区+菜单分类+在线预订/外卖入口+顾客评价+页脚门店信息',
                'atmosphere' => '食欲感、温暖感、热闹感、品质感',
            ],
            'css_key_elements' => [
                'hero_full_width' => true,
                'card_grid_3col' => true,
                'gradient_primary' => true,
                'nav_sticky' => true,
                'section_alternating' => false,
                'price_tag' => true,
                'badge_sale' => true,
            ],
            'color_suggestions' => [
                'primary' => '#F97316',
                'primary_light' => '#FFEDD5',
                'primary_dark' => '#EA580C',
                'bg' => '#FFFFFF',
                'bg_section' => '#FFF7ED',
                'text' => '#1F2937',
                'text_muted' => '#6B7280',
            ],
        ],
        'finance' => [
            'name' => '金融',
            'design_patterns' => [
                'color_schema' => '稳重感为主（深蓝/金/白），主色沉稳（如 #1E3A8A 或 #0F172A），辅色金色用于高亮/收益数据',
                'typography' => '字形严谨工整，标题粗体，正文15-16px，数字/金额等宽字体，数据突出',
                'components' => '顶部导航+Hero数据区(核心指标/收益)+产品/服务列表+安全保障展示+数据图表区+计算器工具+客户案例+页脚合规信息',
                'atmosphere' => '稳重感、专业感、信任感、安全感',
            ],
            'css_key_elements' => [
                'hero_full_width' => true,
                'card_grid_3col' => true,
                'gradient_primary' => false,
                'nav_sticky' => true,
                'section_alternating' => true,
                'data_highlight' => true,
                'security_badge' => true,
            ],
            'color_suggestions' => [
                'primary' => '#1E3A8A',
                'primary_light' => '#DBEAFE',
                'primary_dark' => '#0F172A',
                'bg' => '#FFFFFF',
                'bg_section' => '#F1F5F9',
                'text' => '#0F172A',
                'text_muted' => '#475569',
            ],
        ],
    ],

    // 默认行业（未指定时使用）
    'default_industry' => 'corporate',

    // 兜底：无法理解设计模式时的最低质量要求
    'fallback' => [
        'must_have_vars' => 5,
        'min_transitions' => 3,
        'min_media_queries' => 1,
    ],
];
