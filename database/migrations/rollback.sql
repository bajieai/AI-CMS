-- AI-CMS 数据库回滚脚本
-- 按依赖关系倒序删除所有表
-- 执行前请务必备份数据！

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 删除顺序: 从依赖最深的表开始

-- 1. 删除附件表 (无依赖)
DROP TABLE IF EXISTS `i8j_aicms_attachments`;

-- 2. 删除系统配置表 (无依赖)
DROP TABLE IF EXISTS `i8j_aicms_configs`;

-- 3. 删除AI使用统计表 (无依赖)
DROP TABLE IF EXISTS `i8j_aicms_ai_usage_stats`;

-- 4. 删除AI模型配置表 (无依赖)
DROP TABLE IF EXISTS `i8j_aicms_ai_models`;

-- 5. 删除AI提示词模板表 (无依赖)
DROP TABLE IF EXISTS `i8j_aicms_ai_prompts`;

-- 6. 删除AI任务表 (无依赖)
DROP TABLE IF EXISTS `i8j_aicms_ai_tasks`;

-- 7. 删除文章状态变更日志表 (依赖文章表)
DROP TABLE IF EXISTS `i8j_aicms_article_status_logs`;

-- 8. 删除文章标签关联表 (依赖文章表和标签表)
DROP TABLE IF EXISTS `i8j_aicms_article_tags`;

-- 9. 删除标签表 (依赖文章表)
DROP TABLE IF EXISTS `i8j_aicms_tags`;

-- 10. 删除文章表 (依赖分类表和用户表)
DROP TABLE IF EXISTS `i8j_aicms_articles`;

-- 11. 删除分类表 (无强依赖)
DROP TABLE IF EXISTS `i8j_aicms_categories`;

-- 12. 删除操作日志表 (依赖用户表)
DROP TABLE IF EXISTS `i8j_aicms_operation_logs`;

-- 13. 删除用户角色关联表 (依赖用户表和角色表)
DROP TABLE IF EXISTS `i8j_aicms_user_roles`;

-- 14. 删除角色权限关联表 (依赖角色表和权限表)
DROP TABLE IF EXISTS `i8j_aicms_role_permissions`;

-- 15. 删除权限表 (依赖自身)
DROP TABLE IF EXISTS `i8j_aicms_permissions`;

-- 16. 删除角色表 (依赖用户表)
DROP TABLE IF EXISTS `i8j_aicms_roles`;

-- 17. 删除用户表 (最后删除)
DROP TABLE IF EXISTS `i8j_aicms_users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 显示删除完成信息
SELECT 'All AI-CMS tables have been dropped successfully!' AS 'Rollback Complete';
