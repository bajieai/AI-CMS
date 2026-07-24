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
// 路由配置
return [
    // 控制器后缀（设为true自动添加Controller后缀）
    'controller_suffix' => true,

    // V2.9.2 M19b: Sitemap路由
    'sitemap_index'     => ['sitemap', 'home/Sitemap/index'],
    'sitemap_chunk'     => ['sitemap/:page', 'home/Sitemap/chunk'],
    'sitemap_lang'      => ['sitemap/:lang', 'home/Sitemap/lang'],
    'robots_txt'        => ['robots.txt', 'home/Sitemap/robots'],
];
