# 八界AI-CMS V2.7

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.7.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.7 是基于 ThinkPHP 8.1 多应用模式构建的企业信息管理系统，集成 DeepSeek / Qwen / GLM / ERNIE / OpenAI兼容 多模型AI接口，为内容创作提供智能辅助。V2.7 在V2.6基础上完成API安全加固、付费内容体系完善、积分生态闭环和运营体验升级，标志着产品进入商业变现+安全合规的成熟阶段。

### 核心特性

- **多模型AI引擎** - DeepSeek / Qwen / GLM / ERNIE / OpenAI兼容 5大AI引擎，工厂模式+熔断降级
- **AI智能写作** - 续写/改写/扩写/摘要 4种AI写作模式，支持AI批量生成
- **AI模板** - 预设AI写作模板，一键套用生成不同风格内容
- **6种内容类型** - 产品/案例/新闻/下载/招聘/单页，支持扩展字段
- **简化RBAC** - 3级角色（超管/管理员/编辑），配置文件权限控制
- **I8j标签引擎** - 自定义模板标签 `{i8j:infolist}`/`{i8j:catelist}`/`{i8j:bannerlist}`/`{i8j:linklist}`/`{i8j:medialist}`/`{i8j:commentlist}`，灵活调用数据
- **CSS静态资源分离** - 内联样式提取为独立CSS文件，`public/skin/` 目录统一管理
- **PJAX无刷新** - 后台PJAX切换，51个模板script迁移至js block，体验流畅
- **双主题后台** - default(经典) / corporate(企业) 两套后台主题自由切换
- **安装向导** - Web端5步安装，自动建表、创建管理员
- **富文本编辑** - TinyMCE 6+ 编辑器，支持媒体库选择和AI辅助

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

### V2.5 新增特性

- **微信支付V3** - 会员内容付费，微信支付V3接口集成
- **AI批量生成** - 按分类批量AI生成内容，支持队列任务
- **多AI模型** - GLM / ERNIE / OpenAI兼容 Provider，工厂模式+熔断降级
- **内容采集** - 采集规则管理+AI智能改写，一键导入内容
- **多平台发布** - 微信公众号/头条号/知乎等平台一键发布
- **邮件系统** - 邮件模板/订阅者管理/批量发送/发送日志
- **插件管理** - 插件安装/启用/禁用/评分/配置，可扩展架构
- **多语言** - 中/英/日多语言包，后台界面语言切换
- **模板市场** - 在线模板浏览/安装/评分，主题一键切换
- **Redis缓存** - CacheService标签体系(17标签)，精准缓存管理
- **小程序** - 微信小程序端，支持内容浏览/搜索/详情

### V2.6 改进

- **CSS静态资源分离** - 后台/前台/登录页内联CSS提取为外部文件，`public/skin/` 目录管理
- **PJAX核心修复** - 51个模板`<script>`从content block迁移至js block，解决PJAX切换脚本丢失
- **数据导入修复** - ImportController分类查询+ImportService CSV导入+权限映射修正
- **模板变量注入** - `$skin_admin`(后台) / `$skin`(前台) 自动注入CSS路径变量
- **Nginx配置更新** - deploy/nginx + docker/nginx 添加 `/skin/` 路径支持
- **调试文件清理** - 移除调试临时文件，.gitignore增强忽略规则

### V2.7 新增特性

- **API安全加固** - ApiMemberAuth中间件注入会员ID，PaidContentGuard二级防护，杜绝付费内容绕过
- **VIP权益规范化** - is_vip字段统一标记，登录时实时过期检查，VipExpireCommand定时降级
- **付费章节体系** - UserChapter模型+章节管理UI+阅读页+试读截断，支持按章节单独售卖
- **积分签到生态** - 每日签到+连续签到奖励+消费返积分，前台签到页/积分记录页
- **积分商城前端** - PointsProductController+兑换弹窗+兑换记录+发货管理
- **头条号OAuth** - OAuth 2.0授权+Token自动刷新，发布时无感续期
- **PV统计重构** - JS异步打点+VisitService+蜘蛛过滤，不影响页面渲染
- **验证码增强** - GD库生成(干扰线/噪点/扭曲)，支持切换腾讯验证码
- **邮件队列持久化** - i8j_email_queue表+DB/Cache双写+EmailQueueRecoverCommand
- **表单可视化编辑器** - 12种字段类型+4预设模板+拖拽排序+实时预览
- **搜索增强** - Meilisearch集成+联想补全+热门搜索
- **CDN集成** - StorageService::getCdnUrl() + 后台配置开关
- **双栏菜单(corporate)** - L1图标55px+L2面板200px，hover/click交互
- **AI模板参考示例** - generate_mode=example，参考示例Prompt构建

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端框架 | ThinkPHP 8.1 | 多应用模式(admin/home/api/install/common) |
| 语言 | PHP 8.2+ | 严格类型声明 |
| 数据库 | MySQL 8.0 | 46+张数据表，前缀 i8j_ |
| 缓存 | Redis | CacheService标签体系(17标签) |
| Session | PHP原生文件Session | 24小时过期 |
| AI接口 | DeepSeek/Qwen/GLM/ERNIE/OpenAI | 工厂模式+熔断降级CircuitBreakerTrait |
| 前端UI | Bootstrap 5.3 + jQuery 3.7 | CSS分离至public/skin/目录 |
| 富文本 | TinyMCE 6+ | 本地静态资源，中文语言包 |
| 部署 | Docker / Nginx+PHP-FPM | 多入口模式 |
| 认证 | Session / Cookie+Cache / Bearer+HMAC | 后台/会员/API三套认证 |
| 小程序 | 微信小程序 | 内容浏览/搜索/详情 |

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
# 1. 确保已安装 PHP 8.2+, MySQL 8.0+, Composer, Redis
# 2. 克隆项目并安装依赖
git clone https://gitee.com/bajieai/ai-cms.git
cd AI-CMS
composer install --no-dev --optimize-autoloader

# 3. 复制环境配置
cp .env.example .env
# 编辑 .env 配置数据库和Redis信息

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
│   │   ├── middleware/         #   中间件(PjaxMiddleware等)
│   │   ├── route/app.php       #   路由定义
│   │   ├── middleware.php      #   中间件注册
│   │   └── config/view.php     #   视图路径映射
│   ├── home/                   # 前台应用
│   │   ├── controller/         #   控制器
│   │   ├── route/app.php       #   路由定义
│   │   └── config/view.php    #   视图路径映射
│   ├── api/                    # API应用
│   │   ├── controller/         #   控制器(Ai/Upload/Cache/Content等)
│   │   └── route/app.php       #   API路由定义
│   ├── install/                # 安装向导应用
│   │   ├── controller/         #   控制器(5步安装)
│   │   └── view/               #   安装页面模板
│   └── common/                 # 公共模块
│       ├── controller/         #   基类控制器(AdminBase/FrontBase)
│       ├── middleware/         #   中间件
│       ├── model/              #   数据模型
│       ├── service/            #   业务服务(AI/支付/采集/发布/邮件/插件等)
│       ├── taglib/             #   模板标签引擎(I8j)
│       ├── traits/             #   特性(CircuitBreakerTrait/RedisQueueTrait)
│       └── helper.php          #   全局助手函数
├── config/                     # 框架全局配置
│   ├── app.php                 #   应用配置
│   ├── database.php            #   数据库配置
│   ├── template.php            #   模板引擎(含I8j标签库)
│   ├── session.php             #   Session配置
│   ├── cache.php               #   缓存配置(Redis)
│   ├── menu.php                #   后台菜单配置
│   ├── permission.php          #   RBAC权限配置
│   ├── ai.php                  #   AI服务配置
│   ├── payment.php             #   支付配置
│   ├── storage.php             #   对象存储配置
│   ├── meilisearch.php         #   MeiliSearch配置
│   └── info_type_fields.php    #   扩展字段定义
├── template/                   # 模板目录
│   ├── admin/                  #   后台模板
│   │   ├── default/            #     经典主题
│   │   └── corporate/          #     企业主题
│   └── themes/                 #   前台主题
│       ├── default/            #     默认主题(pc/mobile)
│       └── corporate/          #     企业主题(pc/mobile)
├── public/                     # Web根目录
│   ├── index.php               #   前台入口
│   ├── admin.php               #   后台入口
│   ├── install.php             #   安装入口
│   ├── assets/                 #   公共静态资源(Bootstrap/jQuery/TinyMCE)
│   ├── skin/                   #   主题静态资源(V2.6新增)
│   │   ├── admin/              #     后台CSS/JS/图片/字体
│   │   └── themes/             #     前台主题CSS/JS/图片/字体
│   └── uploads/                #   上传目录
├── database/                   # 数据库SQL
│   ├── install.sql             #   建表SQL+初始数据
│   ├── v2.5.sql                #   V2.5增量更新
│   ├── v2.6.sql                #   V2.6增量更新
│   └── v2.7.sql                #   V2.7增量更新
├── miniprogram/                # 微信小程序
│   ├── pages/                  #   页面(index/detail/search/login)
│   └── utils/                  #   工具(API封装)
├── plugin/                     # 插件目录
├── docker/                     # Docker配置
│   ├── php/Dockerfile          #   PHP-FPM镜像
│   ├── nginx/                  #   Nginx配置(含/skin/路径)
│   └── mysql/                  #   MySQL配置
├── deploy/                     # 生产部署配置
│   └── nginx/aicms.conf       #   Nginx生产配置模板(含/skin/路径)
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
| i8j_member | 会员表 | id,username,email,password,nickname,avatar,level_id,status |
| i8j_config | 系统配置表 | id,group,name,value,type,options,sort,remark |
| i8j_log | 操作日志表 | id,user_id,module,action,target,ip,data |
| i8j_media | 媒体资源表 | id,user_id,filename,filepath,filetype,mimetype,filesize,alt_text |
| i8j_banner | 轮播图表 | id,title,image,link,target,sort,status,start_time,end_time |
| i8j_link | 友情链接表 | id,title,url,logo,sort,status |
| i8j_review | 审核记录表 | id,content_id,user_id,action,remark |
| i8j_ai_log | AI调用日志 | id,provider,model,type,prompt,tokens,cost |
| i8j_collect_source | 采集源 | id,name,url,rule_json,status |
| i8j_publish_log | 发布日志 | id,content_id,platform,status,result |
| i8j_email_template | 邮件模板 | id,name,subject,body,status |
| i8j_paid_order | 付费订单 | id,member_id,content_id,amount,status |
| i8j_plugin | 插件表 | id,name,title,version,status,config |
| i8j_email_queue | 邮件队列 | id,to_email,subject,status,retry_count,created_at |
| i8j_user_chapter | 用户已购章节 | id,user_id,content_id,chapter_id,price |
| i8j_signin_log | 签到记录 | id,member_id,signin_date,points,consecutive_days |
| i8j_points_log | 积分变动日志 | id,member_id,points,type,source_id,note |

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
| GET | /api/csrf/token | 获取CSRF Token（AJAX恢复） |
| GET | /api/content/list | 内容列表 |
| GET | /api/content/detail | 内容详情 |
| POST | /api/member/login | 会员登录 |
| POST | /api/member/register | 会员注册 |
| POST | /api/v1/visit | PV打点统计 |
| GET | /api/v1/search/suggest | 搜索联想补全 |
| GET | /api/v1/search/hot | 热门搜索 |

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

<!-- 评论列表 -->
{i8j:commentlist content_id="$id" limit="10"}
  <div>{$field.content} - {$field.username}</div>
{/i8j:commentlist}
```

## 版本历史

| 版本 | 日期 | 主要更新 |
|------|------|----------|
| V2.0.0 | 2024-Q4 | 基础CMS：内容管理/分类/标签/媒体/轮播图/友情链接 |
| V2.1.0 | 2024-Q4 | AI智能写作(DeepSeek)/审核工作流/媒体资源库 |
| V2.2.0 | 2025-Q1 | 回收站/版本历史/富文本增强/操作日志 |
| V2.3.0 | 2025-Q1 | 定时发布/SEO管理/会员系统/评论/广告/数据导入导出/API |
| V2.4.0 | 2025-Q2 | 多语言支持/模板市场/插件系统/搜索增强 |
| V2.5.1 | 2025-Q3 | 微信支付V3/AI批量生成/多AI模型/采集/多平台发布/邮件/Redis缓存 |
| V2.6.0 | 2025-Q4 | CSS静态资源分离/PJAX核心修复/数据导入修复 |
| V2.7.0 | 2026-Q1 | API安全加固/付费章节/积分签到/表单编辑器/搜索增强/CDN集成 |

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
