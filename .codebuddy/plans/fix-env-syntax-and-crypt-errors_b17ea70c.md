---
name: fix-env-syntax-and-crypt-errors
overview: 修复 .env 文件中括号导致的语法错误，以及 helper.php 中 think\facade\Crypt 未定义的问题
todos:
  - id: fix-env-syntax
    content: 修复 backend/.env 文件中含括号的注释（第9行、第18行），移除括号避免 parse_ini_file 解析失败
    status: completed
  - id: fix-crypt-helper
    content: 将 helper.php 中 encode_id/decode_id 的 think\facade\Crypt 替换为 PHP 原生 openssl_encrypt/openssl_decrypt 实现
    status: completed
    dependencies:
      - fix-env-syntax
  - id: verify-page
    content: 访问 http://localhost:8080 验证页面正常加载无报错
    status: completed
    dependencies:
      - fix-env-syntax
      - fix-crypt-helper
---

## Product Overview

修复 AI-CMS 后端两个导致页面无法正常加载的运行时错误。

## Core Features

1. **修复 .env 语法错误**: ThinkPHP 使用 `parse_ini_file()` 解析 `.env` 文件，该函数将括号 `(` 视为非法字符。当前第9行注释 `(Docker内部网络)` 和第18行 `(Docker内部网络)` 导致解析失败，需要移除所有含括号的注释。
2. **修复 Crypt 类未定义错误**: `helper.php:46-62` 的 `encode_id()` / `decode_id()` 函数依赖 `\think\facade\Crypt::encrypt()/decrypt()`，但 ThinkPHP 8.1 没有内置 Crypt facade，composer.json 中也未引入任何加密包。需要使用 PHP 原生 `openssl_encrypt` / `openssl_decrypt` 替代，密钥复用已有的 `JWT_SECRET` 环境变量。

## Tech Stack

- PHP 8.4 + ThinkPHP 8.1 框架
- PHP OpenSSL 扩展（容器已内置）

## Implementation Approach

### 问题1: .env 文件修复（高优先级 - 阻塞性问题）

ThinkPHP 的 `Env::load()` 方法调用 `parse_ini_file($file, true, INI_SCANNER_RAW)` 解析 .env 文件。PHP 的 `parse_ini_file()` 对注释中的特殊字符有严格限制，`(` 字符会导致语法错误。

**方案**: 移除 .env 中所有含中文括号的注释行中的括号内容。涉及第9行和第18行两处：

- 第9行: `# 数据库配置 (Docker内部网络)` → `# 数据库配置`
- 第18行: `# Redis配置 (Docker内部网络)` → `# Redis配置`

### 问题2: Crypt 加密替换（中优先级 - 功能性依赖）

ThinkPHP 8.1 未内置 Crypt facade。`encode_id()` / `decode_id()` 目前未被其他代码调用（搜索确认仅声明于 helper.php），但一旦调用即会崩溃。

**方案**: 在 helper.php 内直接使用 `openssl_encrypt` / `openssl_decrypt` 实现 AES-256-CBC 加解密：

- 密钥: 取环境变量 `JWT_SECRET` 的 MD5 值作为32字节AES密钥
- IV: 固定16字节向量（从项目名派生）
- 输出: Base64 编码
- 完全不引入新的 Composer 依赖

## Implementation Notes

- **问题1 是阻塞性问题**：.env 解析失败后整个框架无法初始化，必须先修
- **问题2 需同步修复**：虽然目前 encode_id/decode_id 无外部调用者，但 helper.php 作为全局自动加载文件，只要框架启动就会解析其中所有函数定义；Crypt 引用在运行时才会触发报错，但在 ID 加密功能被使用时会立刻崩溃
- 修改后 Docker volume 会自动同步文件到容器，无需重启