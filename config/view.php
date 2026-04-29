<?php
// AI-CMS V2.0 模板配置

return [
    // 模板引擎类型使用Think
    'type' => 'Think',
    
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部小写 3 保持操作方法
    'auto_rule' => 1,
    
    // 模板目录名
    'view_dir_name' => 'view',
    
    // 模板后缀
    'view_suffix' => 'html',
    
    // 模板文件名分隔符
    'view_depr' => DIRECTORY_SEPARATOR,
    
    // 模板引擎普通标签开始标记（单花括号，EyouCMS风格）
    'tpl_begin' => '{',
    
    // 模板引擎普通标签结束标记
    'tpl_end' => '}',
    
    // 标签库标签开始标记
    'taglib_begin' => '{',
    
    // 标签库标签结束标记
    'taglib_end' => '}',
    
    // 预先加载的标签库
    'taglib_pre_load' => 'app\common\taglib\I8j',
    
    // 模板路径（在控制器initialize中按应用动态设置）
    // admin应用 -> template/admin/default/
    // home应用 -> template/pc/default/
    'view_path' => '',
    
    // 布局模板开关
    'layout_on' => false,
    
    // 布局模板名称
    'layout_name' => 'layout',
    
    // 布局模板项
    'layout_item' => '{__CONTENT__}',
];
