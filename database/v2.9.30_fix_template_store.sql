-- V2.9.30: 审核工作流+统计缺少字段补充
ALTER TABLE i8j_template_store ADD COLUMN review_status TINYINT DEFAULT 0 COMMENT '审核状态:0草稿1待初审2待终审3通过4驳回' AFTER status;
ALTER TABLE i8j_template_store ADD COLUMN is_published TINYINT DEFAULT 0 COMMENT '是否已发布' AFTER is_recommended;
ALTER TABLE i8j_template_store ADD COLUMN view_count INT UNSIGNED DEFAULT 0 COMMENT '浏览次数' AFTER install_count_7d;
