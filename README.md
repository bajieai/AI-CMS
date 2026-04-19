# 八界AI-CMS

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.4+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)
![Vue.js](https://img.shields.io/badge/Vue.js-3-orange)

## 项目简介

八界AI-CMS 是一款基于 ThinkPHP + Vue.js 构建的新一代智能内容管理系统，集成了先进的人工智能技术（DeepSeek API），为内容创作者提供更智能、更高效的内容创作体验。

### 核心特性

- **AI智能助手** - 内置AI写作助手，支持文章摘要、标签生成、内容优化等功能
- **可视化编辑** - 强大的富文本编辑器，支持 Markdown 和富文本切换
- **灵活的权限管理** - 基于RBAC的完善权限系统
- **强大的SEO优化** - 内置SEO优化功能，支持自定义TDK
- **媒体库管理** - 支持图片、视频、文档等多种文件类型
- **操作日志审计** - 完整的操作日志记录和审计功能

## 技术栈

### 后端

- **框架**: ThinkPHP 8.1
- **语言**: PHP 8.4+
- **数据库**: MySQL 8.0
- **缓存**: Redis 7
- **队列**: Redis Queue / AI任务队列

### 前端

- **框架**: Vue 3 (Composition API)
- **构建工具**: Vite
- **UI框架**: Element Plus
- **状态管理**: Pinia
- **HTTP客户端**: Axios

### 基础设施

- **Web服务器**: Nginx
- **容器化**: Docker / Docker Compose
- **PHP运行时**: PHP-FPM 8.4+

## 快速开始

> **推荐使用 Docker 部署，一条命令即可完成所有环境配置，无需手动安装 PHP/MySQL/Redis/Nginx 等依赖。**

### 方式一：Docker 部署（推荐，适合所有用户）

**前置条件：仅需安装 [Docker Desktop](https://docs.docker.com/get-docker/)**

| 操作系统 | 安装方式 | 预计耗时 |
|---------|---------|---------|
| Windows | 下载 Docker Desktop 安装包，双击安装 | 5 分钟 |
| macOS | 同上 | 5 分钟 |
| Linux | `curl -fsSL https://get.docker.com \| sh` | 3 分钟 |

**一键启动（3 步搞定）：**

```bash
# 步骤1: 克隆项目
git clone https://github.com/your-repo/AI-CMS.git
cd AI-CMS

# 步骤2: 一键部署（自动完成：构建镜像→创建容器→初始化数据库）
./install.sh --docker        # Linux / macOS
# 或 Windows:
install.bat --docker

# 步骤3: 访问系统 ✅
# 前台地址: http://localhost
# 后台地址: http://localhost/admin   默认账号: admin / Admin@2026
```

**Docker 内部自动完成的工作：**
- 构建 PHP 8.4-FPM 镜像（含 pdo_mysql、redis、gd、bcmath 等 12 个扩展）
- 启动 MySQL 8.0 并自动建库导入 17 张表结构
- 启动 Redis 7 并加载缓存配置
- 配置 Nginx 反向代理 + URL 重写 + 静态资源缓存 + 安全头
- 可选导入示例数据

**常用运维命令：**
```bash
# 查看服务状态
docker-compose ps

# 查看日志 (排查问题时用)
docker-compose logs -f nginx    # 前端/Nginx日志
docker-compose logs -f php      # 后端PHP错误日志
docker-compose logs -f mysql    # 数据库日志

# 重启服务
docker-compose restart

# 停止并删除容器（保留数据）
docker-compose down

# 完全清除（含数据库数据）⚠️
docker-compose down -v
```

---

### 方式二：原生部署（高级选项）

> ⚠️ **此方式需要手动安装和配置多个软件组件，建议仅在有 Linux 运维经验时选择。**

#### 为什么推荐 Docker 而非原生部署？

| 对比维度 | Docker 部署 | 原生部署 |
|---------|------------|---------|
| **前置条件** | 仅安装 Docker | PHP 8.4+、MySQL 8.0+、Redis 7+、Composer、Node.js、Nginx |
| **操作步骤数** | 3 步 | 16-20 步 |
| **PHP 扩展安装** | 自动编译安装 | 手动逐个安装（不同系统命令不同） |
| **环境冲突风险** | 无（容器隔离） | 高（版本冲突、路径冲突等） |
| **预估耗时** | 10 分钟 | 30-120 分钟 |
| **出错概率** | 极低 | 中高（扩展缺失/权限/Nginx配置等问题） |
| **适合人群** | 所有用户 | 有运维经验的开发者 |

#### 如果仍需原生部署，环境要求：

```bash
# 必须安装的组件及版本：
PHP >= 8.0.5 （推荐 8.4），必须包含以下扩展：
  pdo_mysql, redis, gd, bcmath, mbstring, xml, zip, opcache, json
MySQL >= 8.0
Redis >= 7
Composer >= 2.x
Node.js >= 18 （前端构建需要）
Nginx 或 Apache （Web服务器）
```

#### 原生部署步骤：

```bash
# 1. 克隆项目
git clone https://github.com/your-repo/AI-CMS.git
cd AI-CMS

# 2. 一键脚本（会引导检查环境和执行各步骤）
./install.sh --native          # Linux/macOS
install.bat --native           # Windows

# 3. 编辑 backend/.env 配置数据库连接信息

# 4. 启动服务后访问
cd backend && php think run --port=8080
# 或配置 Nginx 反向代理（参考 deploy/nginx/aicms.conf）
```

生产环境的 Nginx 配置模板已提供在 `deploy/nginx/aicms.conf`，可直接复制到 `/etc/nginx/sites-available/` 使用。

---

### 部署方案选择指南

```
你是哪种用户？
│
├─ 我只是想快速试用 / 本地开发
│  └─ 👉 用 Docker！3步搞定，10分钟内跑起来
│
├─ 我想部署到云服务器上
│  ├─ 服务器支持Docker？ → 👉 用 Docker（最省心）
│  └─ 不支持/不想用Docker？ → 👉 用原生模式（需有Linux基础）
│
├─ 我是运维工程师，要深度定制
│  └─ 👉 用原生模式，完全掌控每个组件的配置
│
└─ 我想做二次开发
   └─ 👉 Docker模式更方便，代码热更新通过volume映射实时生效
```

## 目录结构

```
AI-CMS/
├── backend/                 # 后端项目 (ThinkPHP 8.1)
│   ├── app/
│   │   ├── controller/api/ # API控制器
│   │   ├── model/          # 数据模型
│   │   ├── service/        # 业务服务层
│   │   ├── middleware/      # 中间件
│   │   ├── exception/       # 异常处理
│   │   ├── config/          # 配置文件
│   │   └── helper.php      # 助手函数
│   ├── route/              # 路由定义
│   ├── config/             # 框架配置
│   ├── runtime/            # 运行时缓存/日志
│   ├── public/             # Web根目录
│   └── composer.json
├── frontend/                # 前端项目 (Vue 3 + Vite)
│   ├── src/
│   │   ├── api/            # API请求封装
│   │   ├── components/     # 公共组件
│   │   ├── views/          # 页面组件
│   │   ├── router/         # Vue Router
│   │   ├── stores/         # Pinia状态管理
│   │   ├── types/          # TypeScript类型定义
│   │   └── assets/         # 静态资源
│   └── package.json
├── deploy/                  # 生产部署辅助文件（原生部署使用）
│   └── nginx/
│       └── aicms.conf      # Nginx生产环境配置模板
├── docker/                  # Docker配置
│   ├── php/               # PHP Dockerfile (含12个扩展)
│   ├── nginx/             # Docker内Nginx配置
│   ├── mysql/             # MySQL自定义配置
│   └── redis/             # Redis自定义配置
├── database/                # 数据库脚本
│   ├── migrations/        # 建表SQL (17张表)
│   └── seeds/             # 示例数据
├── docker-compose.yml        # Docker Compose编排 (4个服务)
├── install.sh                # Linux/macOS 一键安装脚本 v2.0
├── install.bat               # Windows 一键安装脚本 v2.0
├── 产品文档/                 # 产品设计文档
└── README.md                # 项目说明
```

## 主要功能

### 内容管理

- [x] 文章 CRUD 操作
- [x] 分类管理（支持多级分类）
- [x] 标签管理
- [x] 文章审核工作流
- [x] 草稿箱
- [x] 回收站

### AI功能

- [x] AI文章摘要生成
- [x] AI标签智能推荐
- [x] AI内容优化建议
- [x] AI文章续写
- [x] AI对话助手
- [x] AI提示词模板管理
- [x] AI模型配置管理
- [x] AI使用统计

### 用户权限

- [x] 用户管理
- [x] 角色管理
- [x] 权限管理
- [x] 登录日志
- [x] 操作日志

### 系统设置

- [x] 基础设置
- [x] 上传设置
- [x] AI设置
- [x] 邮件设置

### 媒体管理

- [x] 本地上传
- [x] 图片预览
- [x] 文件管理
- [x] 缩略图生成

## API接口说明

### 认证接口

| 方法 | 路径 | 描述 |
|------|------|------|
| POST | /api/auth/login | 用户登录 |
| POST | /api/auth/logout | 用户登出 |
| GET | /api/auth/user | 获取当前用户信息 |

### 文章接口

| 方法 | 路径 | 描述 |
|------|------|------|
| GET | /api/articles | 获取文章列表 |
| GET | /api/articles/{id} | 获取文章详情 |
| POST | /api/articles | 创建文章 |
| PUT | /api/articles/{id} | 更新文章 |
| DELETE | /api/articles/{id} | 删除文章 |

### AI接口

| 方法 | 路径 | 描述 |
|------|------|------|
| POST | /api/ai/summarize | 文章摘要 |
| POST | /api/ai/generate-tags | 生成标签 |
| POST | /api/ai/chat | AI对话 |

### 完整API文档

启动服务后访问 `/api/docs` 查看完整API文档。

## 配置说明

### .env 配置文件

```env
# 应用配置
APP_NAME="AI-CMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ai_cms
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis配置
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=null

# AI配置
AI_PROVIDER=deepseek
AI_API_KEY=your-api-key
AI_MODEL=deepseek-chat
```

### AI模型配置

系统支持多种AI模型，通过后台配置或数据库配置：

| 提供商 | 模型 | 说明 |
|--------|------|------|
| DeepSeek | deepseek-chat | 通用对话 |
| DeepSeek | deepseek-coder | 代码生成 |
| OpenAI | gpt-4 | GPT-4模型 |
| 百度 | ernie-bot | 文心一言 |

## 默认账户

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 超级管理员 | admin | Admin@2026 |

> ⚠️ **安全提示**: 首次登录后请立即修改默认密码！

## 开发团队

- **项目负责人**: AI Team
- **技术栈**: ThinkPHP / Vue.js / Docker
- **版本**: v1.0.0

## 许可证

本项目采用 [MIT 许可证](LICENSE) 开源。

## 反馈与支持

- **问题反馈**: [GitHub Issues](https://github.com/your-repo/AI-CMS/issues)
- **功能建议**: [GitHub Discussions](https://github.com/your-repo/AI-CMS/discussions)

---

<p align="center">Built with ❤️ by AI-CMS Team</p>
