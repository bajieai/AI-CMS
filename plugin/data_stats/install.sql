<?php

-- 数据统计插件安装SQL
-- 演示插件自定义数据表（表前缀 plugin_data_stats_）

CREATE TABLE IF NOT EXISTS `plugin_data_stats_daily` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stats_date` DATE NOT NULL COMMENT '统计日期',
    `content_published` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日发布内容数',
    `user_registered` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日注册用户数',
    `content_views` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '当日内容浏览量',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stats_date` (`stats_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='数据统计插件-每日统计表';
