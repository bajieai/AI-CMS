# 八界AI-CMS V2.5

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.5.1-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.5 是基于 ThinkPHP 8.1 多应用模式构建的企业信息管理系统，集成 DeepSeek / GLM / ERNIE 多模型AI接口，为内容创作提供智能辅助。V2.5 聚焦「AI内容生产与分发平台」，在V2.4基础上新增支付变现、AI批量生成、内容采集、多平台发布、邮件系统、插件扩展、多语言国际化等核心能力。采用服务端模板渲染 + 传统多页应用架构，部署简单、维护方便。

### 核心特性

- **AI智能写作** - 续写/改写/扩写/摘要 4种AI写作模式（DeepSeek API）
- **6种内容类型** - 产品/案例/新闻/下载/招聘/单页，支持扩展字段
- **简化RBAC** - 3级角色（超管/管理员/编辑），配置文件权限控制
- **I8j标签引擎** - 自定义模板标签 `{i8j:infolist}`/`{i8j:catelist}`/`{i8j:bannerlist}`/`{i8j:linklist}`/`{i8j:medialist}`/`{i8j:commentlist}`，灵活调用数据
- **安装向导** - Web端5步安装，自动建表、创建管理员
- **富文本编辑** - TinyMCE 6+ 编辑器，支持媒体库选择和AI辅助
- **内容回收站** - 软删除+回收站，防止误删
- **版本历史** - 内容修改自动保存历史版本，支持回滚
- **媒体资源库** - 统一管理图片/视频/文件，支持分类、搜索、批量操作
- **内容审核工作流** - 待审列表、通过/驳回操作、审核历史追溯
- **轮播图管理** - 后台管理轮播图，前台自动展示，支持有效期控制
- **友情链接** - 后台管理友情链接，支持分组，前台页脚展示
- **数据库备份恢复** - 手动备份、下载、一键恢复，保障数据安全
- **搜索增强** - MySQL FULLTEXT 全文搜索，支持自动降级

### V2.3 新增特性

- **定时发布** - 支持内容定时上线，配合命令行自动执行
- **SEO管理** - Sitemap自动生成、robots.txt管理、死链检测、JSON-LD结构化数据
- **消息通知** - 站内通知系统，支持管理员/会员双端推送
- **数据导入导出** - Excel/CSV格式批量导入导出内容数据
- **前台会员系统** - 独立会员注册/登录/资料管理，支持Gitee OAuth登录
- **评论系统** - 前台评论提交、后台审核管理、自动计数
- **API开放接口** - RESTful API v1，Bearer/HMAC双模式认证，支持速率限制
- **广告系统** - 广告位管理、广告上下线、展示/点击统计
- **功能模块开关** - 后台一键启用/禁用各功能模块
- **操作日志增强** - 详细记录后台操作行为

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端框架 | ThinkPHP 8.1 | 多应用模式(admin/home/api/install/common) |
| 语言 | PHP 8.2+ | 严格类型声明 |
| 数据库 | MySQL 8.0 | 27+张数据表，前缀 i8j_ |
| 缓存 | 文件缓存（Redis可选） | 带标签的缓存管理 |
| Session | PHP原生文件Session | 24小时过期 |
| AI接口 | DeepSeek API (GuzzleHTTP) | 直连，无需Python中间层 |
| 前端UI | Bootstrap 5.3 + jQuery 3.7 | 本地静态资源 |
| 富文本 | TinyMCE 6+ | 本地静态资源，中文语言包 |
| 部署 | Docker / Nginx+PHP-FPM | 多入口模式 |

## 快速开始

### 方式一：Docker 部署（推荐）

**前置条件：[Docker Desktop](https://docs.docker.com/get-docker/)**

```bash
# 1. 克隆项目
git clone https://gitee.com/bajieai/ai-cms.git
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
git clone https://gitee.com/bajieai/ai-cms.git
cd AI-CMS
composer install --no-dev --optimize-autoloader

# 3. 复制环境配置
cp .env.example .env
# 编辑 .env 配置数据库信息

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
│   │   ├── controller/         #   控制器
│   │   ├── route/app.php       #   路由定义
│   │   ├── middleware.php      #   中间件注册
│   │   └── config/view.php    #   视图路径映射
│   ├── home/                   # 前台应用
│   │   ├── controller/         #   控制器
│   │   ├── route/app.php       #   路由定义
│   │   └── config/view.php    #   视图路径映射
│   ├── api/                    # API应用
│   │   ├── controller/         #   控制器(Ai/Upload/Cache)
│   │   └── route/app.php       #   API路由定义
│   ├── install/                # 安装向导应用
│   │   ├── controller/         #   控制器(5步安装)
│   │   └── view/               #   安装页面模板
│   └── common/                 # 公共模块
│       ├── controller/         #   基类控制器
│       ├── middleware/         #   中间件
│       ├── model/              #   数据模型
│       ├── service/            #   业务服务
│       ├── taglib/             #   模板标签引擎(I8j)
│       └── helper.php          #   全局助手函数
├── config/                     # 框架全局配置
│   ├── app.php                 #   应用配置
│   ├── database.php            #   数据库配置
│   ├── template.php            #   模板引擎(含I8j标签库)
│   ├── session.php             #   Session配置
│   ├── cache.php               #   缓存配置
│   ├── menu.php                #   后台菜单配置
│   ├── permission.php          #   RBAC权限配置
│   ├── ai.php                  #   AI服务配置
│   └── info_type_fields.php   #   扩展字段定义
├── template/                   # 模板目录
│   ├── admin/default/          #   后台模板
│   └── pc/default/             #   前台模板
├── public/                     # Web根目录
│   ├── index.php               #   前台入口
│   ├── admin.php               #   后台入口
│   ├── install.php             #   安装入口
│   ├── assets/                 #   静态资源(Bootstrap/jQuery/TinyMCE)
│   └── uploads/                #   上传目录
├── database/
│   └── migrations/
│       └── install.sql         #   建表SQL+初始数据
├── docker/                     # Docker配置
│   ├── php/Dockerfile          #   PHP-FPM镜像
│   ├── nginx/                  #   Nginx配置
│   └── mysql/                  #   MySQL配置
├── deploy/                     # 生产部署配置
│   └── nginx/aicms.conf       #   Nginx生产配置模板
├── .env.example                #   环境变量模板
├── .gitignore                  #   Git忽略规则
├── composer.json               #   Composer依赖
├── docker-compose.yml          #   Docker Compose编排
├── install.sh                  #   Linux/macOS安装脚本
├── install.bat                 #   Windows安装脚本
└── README.md                   #   项目说明
```

## 数据库设计

| 表名 | 说明 | 主要字段 |
|------|------|----------|
| i8j_content | 内容主表 | id,title,content,excerpt,type,status,cate_id,user_id,cover,sort,is_top,views,deleted_status |
| i8j_content_ext | 内容扩展表 | id,content_id,type,data(JSON) |
| i8j_content_version | 内容版本历史 | id,content_id,title,content,excerpt,version,user_id |
| i8j_cate | 分类表 | id,name,type,parent_id,sort,status,seo_title,seo_keywords,seo_description |
| i8j_tag | 标签表 | id,name,sort |
| i8j_content_tag | 内容标签关联 | content_id,tag_id |
| i8j_user | 用户表 | id,username,email,password,nickname,avatar,role_id,status |
| i8j_config | 系统配置表 | id,group,name,value,type,options,sort,remark |
| i8j_log | 操作日志表 | id,user_id,module,action,target,ip,data |
| i8j_media | 媒体资源表 | id,user_id,filename,filepath,filetype,mimetype,filesize,alt_text |
| i8j_banner | 轮播图表 | id,title,image,link,target,sort,status,start_time,end_time |
| i8j_link | 友情链接表 | id,title,url,logo,sort,status |
| i8j_review | 审核记录表 | id,content_id,user_id,action,remark |

## 角色权限

| 角色 | role_id | 权限范围 |
|------|---------|----------|
| 超级管理员 | 1 | 全部权限，跳过权限检查 |
| 管理员 | 2 | 内容管理+分类+标签+媒体+运营+审核+部分系统功能 |
| 编辑 | 3 | 内容管理（含发布）+ 分类查看 + 媒体上传 |

## API接口

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

<!-- 轮播图 -->
{i8j:bannerlist limit="5" status="1"}
  <img src="{$field.image}" alt="{$field.title}">
{/i8j:bannerlist}

<!-- 友情链接 -->
{i8j:linklist limit="10" status="1"}
  <a href="{$field.url}" target="_blank">{$field.title}</a>
{/i8j:linklist}

<!-- 媒体资源 -->
{i8j:medialist filetype="image" limit="10"}
  <img src="{$field.filepath}" alt="{$field.alt_text}">
{/i8j:medialist}
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
