# 八界AI-CMS V2.0

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.0 是基于 ThinkPHP 8.1 多应用模式构建的企业信息管理系统，集成 DeepSeek AI 接口，为内容创作提供智能辅助。采用服务端模板渲染 + 传统多页应用架构，部署简单、维护方便。

### 核心特性

- **AI智能写作** - 续写/改写/扩写/摘要 4种AI写作模式（DeepSeek API）
- **6种内容类型** - 产品/案例/新闻/下载/招聘/单页，支持扩展字段
- **简化RBAC** - 3级角色（超管/管理员/编辑），配置文件权限控制
- **I8j标签引擎** - 自定义模板标签 `{i8j:infolist}`/`{i8j:catelist}`，灵活调用数据
- **安装向导** - Web端5步安装，自动建表、创建管理员
- **富文本编辑** - TinyMCE 6+ 编辑器，支持图片上传和AI辅助

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端框架 | ThinkPHP 8.1 | 多应用模式(admin/home/api/install/common) |
| 语言 | PHP 8.2+ | 严格类型声明 |
| 数据库 | MySQL 8.0 | 8张MVP表，前缀 i8j_ |
| 缓存 | 文件缓存（Redis可选） | 带标签的缓存管理 |
| Session | PHP原生文件Session | 24小时过期 |
| AI接口 | DeepSeek API (GuzzleHTTP) | 直连，无需Python中间层 |
| 前端UI | Bootstrap 5.3 + jQuery 3.7 | CDN加载 |
| 富文本 | TinyMCE 6+ | CDN加载 |
| 部署 | Docker / Nginx+PHP-FPM | 多入口模式 |

## 快速开始

### 方式一：Docker 部署（推荐）

**前置条件：[Docker Desktop](https://docs.docker.com/get-docker/)**

```bash
# 1. 克隆项目
git clone https://github.com/your-repo/AI-CMS.git
cd AI-CMS

# 2. 一键启动
./install.sh --docker          # Linux/macOS
install.bat --docker           # Windows

# 3. 访问安装向导
# http://localhost:3000/install
```

### 方式二：原生部署

```bash
# 1. 确保已安装 PHP 8.2+, MySQL 8.0+, Composer
# 2. 克隆项目并安装依赖
git clone https://github.com/your-repo/AI-CMS.git
cd AI-CMS
composer install --no-dev --optimize-autoloader

# 3. 编辑 .env 配置数据库信息

# 4. 启动开发服务器
php think run --port=8080

# 5. 访问安装向导
# http://localhost:8080/install
```

## 目录结构

```
AI-CMS/
├── app/                        # 应用目录 (PSR-4: app\)
│   ├── admin/                  # 后台应用
│   │   ├── controller/         #   控制器(Login/Index/Content/Cate/Tag/User/System/Log)
│   │   ├── middleware.php      #   中间件注册(AdminAuth+AdminPermission)
│   │   └── config/view.php    #   视图路径映射
│   ├── home/                   # 前台应用
│   │   ├── controller/         #   控制器(Index/Content/Cate/Search)
│   │   └── config/view.php    #   视图路径映射
│   ├── api/                    # API应用
│   │   └── controller/         #   控制器(Ai/Upload/Cache)
│   ├── install/                # 安装向导应用
│   │   ├── controller/         #   控制器(Index - 5步安装)
│   │   └── view/               #   安装页面模板
│   └── common/                 # 公共模块
│       ├── controller/         #   基类(AdminBaseController/FrontBaseController)
│       ├── middleware/         #   中间件(AdminAuth/AdminPermission/InstallCheck)
│       ├── model/              #   数据模型(Content/ContentExt/Cate/Tag/ContentTag/User/Config/Log)
│       ├── service/            #   业务服务(ContentService/CateService/AiService/CacheService/UploadService)
│       ├── taglib/             #   模板标签引擎(I8j)
│       └── helper.php          #   全局助手函数
├── config/                     # 框架配置
│   ├── app.php                 #   多应用配置
│   ├── database.php            #   数据库配置
│   ├── template.php            #   模板引擎配置(含I8j标签库)
│   ├── session.php             #   Session配置
│   ├── cache.php               #   缓存配置
│   ├── menu.php                #   后台菜单配置
│   ├── permission.php          #   RBAC权限配置
│   ├── ai.php                  #   AI服务配置
│   └── info_type_fields.php   #   扩展字段定义
├── route/                      # 路由定义
│   ├── admin.php               #   后台CRUD路由
│   ├── home.php                #   前台路由(6种内容类型)
│   └── api.php                 #   API路由(3个接口)
├── template/                   # 模板目录
│   ├── admin/default/          #   后台模板(layout/login/dashboard/content/cate/tag/user/system/log)
│   └── pc/default/             #   前台模板(layout/index/list/detail/search)
├── public/                     # Web根目录
│   ├── index.php               #   前台入口
│   ├── admin.php               #   后台入口
│   ├── install.php             #   安装入口
│   ├── static/                 #   静态资源
│   └── uploads/                #   上传目录
├── database/
│   └── migrations/
│       └── install.sql         #   建表SQL(8张表+初始数据)
├── docker/                     # Docker配置
│   ├── php/                    #   PHP-FPM Dockerfile
│   ├── nginx/                  #   Nginx配置
│   ├── mysql/                  #   MySQL配置
│   └── redis/                  #   Redis配置
├── deploy/                     # 生产部署配置
│   └── nginx/aicms.conf       #   Nginx生产配置模板
├── .env                        #   环境配置(不入库)
├── composer.json               #   Composer依赖
├── docker-compose.yml          #   Docker Compose编排
├── install.sh                  #   Linux/macOS安装脚本
├── install.bat                 #   Windows安装脚本
└── README.md
```

## 数据库设计 (8张MVP表)

| 表名 | 说明 | 主要字段 |
|------|------|----------|
| i8j_content | 内容主表 | id,title,content,excerpt,type,status,cate_id,user_id,cover,sort,is_top,views |
| i8j_content_ext | 内容扩展表 | id,content_id,type,data(JSON) |
| i8j_cate | 分类表 | id,name,type,parent_id,sort,status |
| i8j_tag | 标签表 | id,name,sort |
| i8j_content_tag | 内容标签关联 | content_id,tag_id |
| i8j_user | 用户表 | id,username,email,password,nickname,avatar,role_id,status |
| i8j_config | 系统配置表 | id,group,name,value,type,options,sort,remark |
| i8j_log | 操作日志表 | id,user_id,module,action,target,ip,data |

**命名规范(Plan B)**：删除字段前缀，简化表名，主键统一为 `id`

## 3种角色权限

| 角色 | role_id | 权限范围 |
|------|---------|----------|
| 超级管理员 | 1 | 全部权限，跳过权限检查 |
| 管理员 | 2 | 内容管理+分类+标签+部分系统功能 |
| 编辑 | 3 | 内容管理（含发布）+ 分类查看 |

## 3个API接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /api/ai/generate | AI内容生成（Session认证） |
| POST | /api/upload/image | 图片上传 |
| POST | /api/cache/clear | 清除缓存（超管专用） |

## I8j模板标签

```html
<!-- 内容列表 -->
{i8j:infolist type="news" limit="10" order="create_time desc"}
  <div>
    <h3>{$field.title}</h3>
    <a href="{$field.url}">查看详情</a>
  </div>
{/i8j:infolist}

<!-- 分类列表 -->
{i8j:catelist type="1" limit="10" parent="0"}
  <a href="{$field.url}">{$field.name}</a>
{/i8j:catelist}
```

## 默认账户

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 超级管理员 | admin | admin123 |

> **安全提示**: 首次登录后请立即修改默认密码！

## 常用Docker命令

```bash
# 查看服务状态
docker-compose ps

# 查看日志
docker-compose logs -f nginx
docker-compose logs -f php

# 重启服务
docker-compose restart

# 停止并删除容器（保留数据）
docker-compose down

# 完全清除（含数据库数据）
docker-compose down -v
```

## 许可证

MIT License
