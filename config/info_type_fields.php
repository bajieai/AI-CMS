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
// AI-CMS V2.0 扩展字段配置（已废弃，仅保留作历史兼容）
// 注意：V2.9.42+ 已改用 content_model_field 表动态管理模型扩展字段，
// 字段名统一为无前缀命名（price/author/source/brand 等），
// 类型映射统一见 app/common/support/ContentTypeMap.php（1=信息/2=单页/3=产品/4=案例/5=下载/6=招聘/7=图集/8=视频）。
// 本文件中的 product_price/case_client/news_source 等旧前缀命名及旧类型映射(1=产品/3=新闻)均已废弃，
// 仅供 getExtFields() 兼容兜底，前端不再调用，请勿据此文件修改或新增字段。

return [
    // 产品类型（1）
    1 => [
        [
            'name' => 'product_price',
            'title' => '产品价格',
            'type' => 'number',
            'required' => false,
            'placeholder' => '请输入产品价格',
        ],
        [
            'name' => 'product_params',
            'title' => '产品参数',
            'type' => 'textarea',
            'required' => false,
            'placeholder' => '每行一个参数，格式：参数名:参数值',
        ],
        [
            'name' => 'product_specs',
            'title' => '产品规格',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：500ml / 100g / 标准版',
        ],
    ],
    
    // 案例类型（2）
    2 => [
        [
            'name' => 'case_client',
            'title' => '客户名称',
            'type' => 'text',
            'required' => false,
            'placeholder' => '请输入客户/合作方名称',
        ],
        [
            'name' => 'case_duration',
            'title' => '项目周期',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：3个月 / 2024.01-2024.04',
        ],
        [
            'name' => 'case_scale',
            'title' => '项目规模',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：百人团队 / 千万级预算',
        ],
    ],

    // 新闻类型（3）
    3 => [
        [
            'name' => 'news_source',
            'title' => '新闻来源',
            'type' => 'text',
            'required' => false,
            'placeholder' => '请输入新闻来源',
        ],
        [
            'name' => 'news_author',
            'title' => '作者',
            'type' => 'text',
            'required' => false,
            'placeholder' => '请输入作者',
        ],
    ],

    // 下载类型（4）
    4 => [
        [
            'name' => 'dl_size',
            'title' => '文件大小',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：15.6 MB',
        ],
        [
            'name' => 'dl_format',
            'title' => '文件格式',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：PDF / ZIP / EXE',
        ],
        [
            'name' => 'dl_count',
            'title' => '下载次数',
            'type' => 'number',
            'required' => false,
            'placeholder' => '初始下载次数',
        ],
    ],

    // 招聘类型（5）
    5 => [
        [
            'name' => 'job_salary',
            'title' => '薪资范围',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：15K-25K / 面议',
        ],
        [
            'name' => 'job_location',
            'title' => '工作地点',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：北京 / 远程',
        ],
        [
            'name' => 'job_num',
            'title' => '招聘人数',
            'type' => 'number',
            'required' => false,
            'placeholder' => '如：2',
        ],
        [
            'name' => 'job_edu',
            'title' => '学历要求',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：本科及以上',
        ],
    ],

    // 单页类型（6）
    6 => [
        [
            'name' => 'page_template',
            'title' => '页面模板',
            'type' => 'text',
            'required' => false,
            'placeholder' => '如：about / contact（留空使用默认）',
        ],
        [
            'name' => 'page_seo_title',
            'title' => 'SEO标题',
            'type' => 'text',
            'required' => false,
            'placeholder' => '自定义SEO标题，留空使用页面标题',
        ],
    ],
];
