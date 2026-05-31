-- ============================================================
-- V2.9.12: 模板商店种子数据
-- 插入2-3个官方演示模板（幂等：先删后插）
-- ============================================================

SET NAMES utf8mb4;

-- 清除旧种子数据（ID 1-3保留给官方模板）
DELETE FROM `i8j_template_store` WHERE `id` BETWEEN 1 AND 3;

INSERT INTO `i8j_template_store` (`id`, `slug`, `name`, `category_id`, `description`, `screenshots`, `price`, `author_name`, `author_id`, `status`, `is_featured`, `quality_score`, `install_count`, `rating_avg`, `rating_count`, `version`, `requirements`, `file_size`, `create_time`, `update_time`) VALUES
(1, 'default-official', '官方默认模板', 1, '八界AI-CMS官方默认模板，简洁大方，适用于各类企业官网。响应式设计，支持PC和移动端。', '["/skin/default/preview1.jpg","/skin/default/preview2.jpg"]', 0.00, '八界AI官方', 0, 1, 1, 92, 128, 4.8, 56, '2.9.12', '{"php":">=8.0","cms":">=2.9.0"}', 2048000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 'corporate-pro', '企业商务Pro', 1, '专为企业打造的商务风格模板，蓝色主色调，专业可信。包含首页、关于、服务、案例、联系等页面。', '["/skin/corporate/preview1.jpg","/skin/corporate/preview2.jpg"]', 99.00, '八界AI官方', 0, 1, 1, 95, 86, 4.9, 42, '2.9.12', '{"php":">=8.0","cms":">=2.9.0"}', 3584000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, 'blog-minimal', '极简博客', 3, '文艺清新的博客模板，注重阅读体验，排版优雅。适合个人博客、技术博客、知识分享类网站。', '["/skin/blog/preview1.jpg","/skin/blog/preview2.jpg"]', 0.00, '八界AI官方', 0, 1, 0, 88, 215, 4.6, 103, '2.9.12', '{"php":">=8.0","cms":">=2.9.0"}', 1536000, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
