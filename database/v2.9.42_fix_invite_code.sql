-- V2.9.42: 修复 member 表空邀请码唯一索引冲突
-- 已存在的空 invite_code 成员生成唯一邀请码
UPDATE i8j_member SET invite_code = UPPER(SUBSTR(MD5(CONCAT(id, username, UNIX_TIMESTAMP())), 1, 8)) WHERE invite_code = '' OR invite_code IS NULL;
