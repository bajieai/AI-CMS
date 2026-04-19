-- AI-CMS 数据库字段重命名迁移脚本
-- 将文章相关字段统一重命名为信息相关字段
-- 执行日期: 2026-04-20

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 更新分类表中的字段注释和默认值
-- ----------------------------

-- 检查并重命名分类表的article_count字段（如果存在）
-- 注意：ThinkPHP模型已经使用content_count，这里确保数据库同步

-- 如果存在article_count字段，将其数据复制到content_count（如果不存在则添加）
SET @dbname = DATABASE();
SET @tablename = 'i8j_aicms_categories';
SET @columnname = 'content_count';

SET @sql = CONCAT(
    'SELECT COUNT(*) INTO @exists FROM information_schema.columns 
    WHERE table_schema = ''', @dbname, ''' 
    AND table_name = ''', @tablename, ''' 
    AND column_name = ''', @columnname, ''''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 如果content_count不存在但article_count存在，重命名字段
SET @sql = CONCAT(
    'ALTER TABLE `', @tablename, '` 
    CHANGE COLUMN `article_count` `content_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''信息数量'''
);
SET @exec = IF(@exists = 0, @sql, 'SELECT 1');
PREPARE stmt FROM @exec;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 2. 更新标签表中的字段
-- ----------------------------

SET @tablename = 'i8j_aicms_tags';
SET @columnname = 'content_count';

SET @sql = CONCAT(
    'SELECT COUNT(*) INTO @exists FROM information_schema.columns 
    WHERE table_schema = ''', @dbname, ''' 
    AND table_name = ''', @tablename, ''' 
    AND column_name = ''', @columnname, ''''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = CONCAT(
    'ALTER TABLE `', @tablename, '` 
    CHANGE COLUMN `article_count` `content_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT ''信息数量'''
);
SET @exec = IF(@exists = 0, @sql, 'SELECT 1');
PREPARE stmt FROM @exec;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------
-- 3. 更新文章表注释（保持表名不变以确保兼容性）
-- ----------------------------

ALTER TABLE `i8j_aicms_articles` COMMENT = '信息表（文章表）';

-- 更新AI提示词模板中的分类
UPDATE `i8j_aicms_ai_prompts` 
SET `category` = 'content',
    `description` = REPLACE(`description`, '文章', '内容'),
    `system_prompt` = REPLACE(`system_prompt`, '文章', '内容'),
    `user_prompt_template` = REPLACE(`user_prompt_template`, '文章', '内容')
WHERE `category` = 'article';

-- ----------------------------
-- 4. 验证更新结果
-- ----------------------------

SELECT 
    table_name,
    column_name,
    column_comment
FROM information_schema.columns
WHERE table_schema = DATABASE()
AND column_name IN ('content_count', 'article_count')
ORDER BY table_name, ordinal_position;

SET FOREIGN_KEY_CHECKS = 1;
