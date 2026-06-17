# 八界AI-CMS V2.9.24

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.9.24-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.9.24 "模板商店运营完善 · 移动端体验升级 · AI编辑器增强" 是基于 ThinkPHP 8.1 多应用模式构建的企业智能内容管理系统，集成 DeepSeek / OpenAI / Qwen / GLM / ERNIE 多模型AI接口，为内容创作提供智能辅助。

**V2.9.24 核心定位：模板商店运营完善 · 移动端体验升级 · AI编辑器增强** — 5大Sprint 24项功能点（100%完成）：

1. **Sprint F 遗留修复** — 恢复system/config路由、插件开发者文档
2. **Sprint G 模板商店后台管理完善** — Banner管理、推荐位配置、分类排序展示、安装/购买统计看板、评论批量管理
3. **Sprint H 移动端体验升级** — 下拉刷新兼容修复、底部导航栏、搜索增强、分享功能（含分享卡片图）、骨架屏加载
4. **Sprint I AI编辑器增强** — 设计面板界面优化、配色自定义保存、多页面设计支持、AI配色超时优化、区块编辑基础能力
5. **Sprint J 系统管理与体验优化** — 缓存管理仪表盘、系统设置页面优化、后台导航完善、双皮肤同步回归检查

**V2.9.23 核心定位：模板生态启动 · AI能力深化** — 5大Sprint 20项功能点（100%完成）：

1. **Sprint A 验收修复+体验地基** — 模板缓存机制优化
2. **Sprint B 模板商店生态闭环** — 商店首页重写、安装进度可视化、评分评论展示、热门排行
3. **Sprint C 前台AI化/可视化编辑MVP** — AI换色/换布局面板、区块拖拽排序(SortableJS)、设备预览切换、配色方案行业推荐
4. **Sprint D 插件CLI+文档** — Plugin CLI命令增强(list/install/uninstall/enable/disable/config/hooks)、插件开发者文档
5. **Sprint E 移动端全覆盖** — 移动端详情模板差异化、PWA离线缓存优化(v2)、触摸交互增强(下拉刷新)

**V2.9.22 核心定位：验收修复·体验对齐** — 3项功能修复（100%完成）：
1. **F-1 移动端list_item模板补齐(P0)** — 6模型×2皮肤=12个移动端模板文件新建
2. **F-2 D-4展示字段与PRD对齐(P1)** — PC端产品展示价格+库存、下载展示文件大小+版本号
3. **F-3 corporate皮肤D-5搜索增强同步(P1)** — 搜索框+搜索无结果提示+空分类提示+JS函数

**V2.9.20 核心定位：内容模型差异化 + 模板商店强化 + 基础设施补齐** — 3大方向10+功能点（100%完成）：
1. **A-1 内容模型数据层(P0)** — 2张新表 + 6种预置模型 + `model_id` 字段
2. **A-2 后台模型管理UI(P0)** — 模型CRUD + 字段管理（14种字段类型）+ 动态表单
3. **A-3 前台差异化渲染(P0)** — 6种模型详情模板片段 × 4套模板 = 24个模板文件
4. **A-4 预置交互行为(P0)** — 图片轮播 + 视频播放器 + 下载计数 API
5. **B-1 模板分类体系(P0)** — 2张新表 + 三维度18条种子数据 + 5级缓存体系
6. **B-2 模板搜索推荐(P0)** — 多维度搜索 + 热门标签 API + 关联推荐
7. **B-3 一键安装预览(P0)** — iframe预览 + 安装/卸载 + 安装计数统计
8. **C-1 SSE推送深化(P1)** — 3通道 + 自动重连 + 指数退避 + 轮询降级
9. **C-2 邮件统一增强(P1)** — 模板变量替换 + 失败重试机制 + mail:retry 命令行
10. **C-3 移动端适配收尾(P1)** — CSS按压态 + 分页touch优化 + 兼容性兜底
11. **C-4 V2.9.19 遗留修复(P1)** — 通知设置页 + 双皮肤模板 + 路由注册

**V2.9.19 核心定位：推送增强·通知深化·风险修复** — 4个Sprint 10项功能点（100%完成）：
1. **R-1：ShareClick Model(P0)** — 补全分享点击追踪模型
2. **R-2：广播推送批量INSERT(P0)** — chunk 500 + insertAll，性能提升60x
3. **R-3：SwiftMailer依赖声明(P0)** — composer.json显式依赖
4. **R-4：时区一致性修复(P0)** — Asia/Shanghai统一
5. **R-5：菜单同步CLI(P0)** — MenuSyncCommand 一键同步
6. **D-1：推送超时+重试队列(P0)** — 60s超时 + 指数退避 + 通道健康信号灯
7. **N-1：通知系统深化(P1)** — 6种类型 + 分类Tab + 批量已读
8. **S-1：邮件订阅增强(P1)** — 模板恢复 + 订阅管理 + 退订处理
9. **D-2：AI-GEO生成式引擎优化(P1)** — 结构化数据 + 实体提取 + 友好度评分
10. **D-3：社交分享增强(P1)** — 分享链接生成 + UTM参数 + OG/TwitterCard

## 技术栈

- **后端**: PHP 8.2+, ThinkPHP 8.1 多应用模式 (admin/home/api)
- **前端**: Bootstrap 5 + jQuery + 传统HTML模板
- **数据库**: MySQL 8.0 (utf8mb4)
- **缓存**: Redis + 文件缓存
- **AI API**: DeepSeek / OpenAI / Qwen / GLM / ERNIE
- **PWA**: Service Worker + 离线缓存
- **拖拽**: SortableJS v1.15+

## 快速开始

### 环境要求

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- Docker & Docker Compose (推荐)

### 安装步骤

```bash
# 1. 克隆仓库
git clone https://gitee.com/bajieai/ai-cms.git
cd ai-cms

# 2. 启动Docker环境
docker-compose up -d

# 3. 安装依赖
docker-php.bat composer install

# 4. 配置数据库
# 复制 .env.example 为 .env 并修改数据库配置

# 5. 导入数据库
bin\migrate.bat

# 6. 访问系统
# 前台: http://localhost
# 后台: http://localhost/admin
```

## 版本历史

| 版本 | 日期 | 核心功能 |
|------|------|----------|
| V2.9.23 | 2026-06 | **模板生态启动·AI能力深化**: 模板商店闭环/前台AI设计面板/插件CLI/移动端全覆盖 |
| V2.9.22 | 2026-06 | **验收修复·体验对齐**: 移动端模板补齐/展示字段对齐/搜索增强同步 |
| V2.9.21 | 2026-05 | **模板商店·会员等级**: 模板分类体系/搜索推荐/一键安装/SSE推送/邮件增强/移动端适配/通知设置/菜单同步 |
| V2.9.20 | 2026-05 | **内容模型差异化+模板商店强化+基础设施补齐**: 6种模型×4套模板/24个模板文件/插件市场/付费阅读/下载计数/备份日志 |
| V2.9.19 | 2026-05 | **推送增强·通知深化·风险修复**: 分享追踪/批量推送/时区修复/菜单同步/推送重试/通知分类/邮件订阅/AI-GEO/社交分享 |
| V2.9.18 | 2026-05 | **性能优化**: Docker overlayfs优化/模板缓存/OPcache/路由显式注册/高频查询缓存/菜单DB插入 |
| V2.9.17 | 2026-05 | **AI翻译+SEO**: 深度翻译/JSON-LD/hreflang/Sitemap/插件市场/会员权益/PWA/数据导出 |
| V2.9.16 | 2026-05 | **Flux异步+懒加载**: CDN/CSS变量/AI报告/API文档/评价媒体/免邮券/AI配色/配图本地化 |
| V2.9.15 | 2026-05 | **模板定制+FLUX配图**: 优惠券/评价评分/邀请奖励/小程序完善 |
| V2.9.14 | 2026-05 | **AI配图+质量检测+SEO+社交分享**: 运营报表/流量分析/AI统计/邀请返积分 |
| V2.9.13 | 2026-Q1 | **API安全+付费章节**: 积分签到/表单编辑器/搜索增强/CDN |
| V2.9.12 | 2025-Q4 | **CSS静态资源分离**: PJAX修复/数据导入修复 |
| V2.9.11 | 2025-Q3 | **微信支付V3+AI批量**: 多AI模型/采集/多平台发布/邮件/Redis |
| V2.9.10 | 2025-Q2 | **多语言+模板市场**: 插件系统/搜索增强 |
| V2.9.9 | 2025-Q1 | **定时发布+SEO**: 会员系统/评论/广告/数据导入导出/API |
| V2.9.8 | 2025-Q1 | **回收站+版本历史**: 富文本增强/操作日志 |
| V2.9.7 | 2024-Q4 | **AI智能写作**: 审核工作流/媒体资源库 |
| V2.9.6 | 2024-Q4 | **基础CMS**: 内容管理/分类/标签/媒体/轮播图/友情链接 |

## 默认账户

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 超级管理员 | admin | admin123 |

> **安全提示**: 首次登录后请立即修改默认密码！

## 常用命令

```bash
# PHP 命令（Docker环境）
docker-php.bat php think plugin list          # 列出插件
docker-php.bat php think plugin install xxx   # 安装插件
docker-php.bat composer dump-autoload          # 刷新自动加载

# 数据库迁移
bin\migrate.bat database\v2.9.23.sql            # 导入指定SQL
bin\migrate.bat                               # 自动导入最新SQL

# Docker 操作
docker-compose ps                             # 查看服务状态
docker-compose logs -f php                    # 查看PHP日志
docker-compose restart                        # 重启服务
```

## 许可证

MIT License — 详见 [LICENSE](LICENSE) 文件

Copyright (c) 2026 湖北八界智能技术有限公司
