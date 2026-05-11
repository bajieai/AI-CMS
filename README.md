# 八界AI-CMS V2.9.3

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.9.3-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.9.3 是基于 ThinkPHP 8.1 多应用模式构建的企业信息管理系统，集成 DeepSeek / Qwen / GLM / ERNIE / OpenAI兼容 多模型AI接口，为内容创作提供智能辅助。V2.9.3 定位"生态闭环 × 多渠道分发 × 运营深化"，聚焦数据备份恢复增强（生存功能）、多渠道分发稳定性提升、插件商店首页升级、会员权益补全四大方向，补全V2.9.2产品需求缺口，强化已有模块的生产就绪度。

### 核心特性

- **多模型AI引擎** - DeepSeek / Qwen / GLM / ERNIE / OpenAI兼容 5大AI引擎，工厂模式+熔断降级
- **AI智能写作** - 续写/改写/扩写/摘要 4种AI写作模式，支持AI批量生成
- **多语言AI深度翻译** - AI翻译引擎+自动翻译钩子+翻译记忆缓存+批量翻译，翻译内容为独立Content记录
- **SEO分发增强** - Sitemap索引拆分+Schema.org JSON-LD+Open Graph+多语言hreflang+搜索引擎Ping
- **插件市场框架** - 在线浏览+一键安装+本地上传ZIP+更新检测，双轨安装架构，商店首页分类导航+推荐位+搜索
- **会员等级权益深化** - 权益配置后台+签到积分倍率+购买折扣+VIP标识+手动降级+升降通知+自动降级CLI+7天缓冲期预警
- **PWA离线支持** - manifest.json+Service Worker(StaleWhileRevalidate策略)+安装提示+iOS降级
- **H5移动端优化** - 骨架屏+无限滚动+手势滑动+下拉刷新+图片查看器(2套mobile主题)
- **数据导出增强** - 高级筛选+自定义字段+CSV/XLSX双格式+流式导出
- **系统性能监控** - CPU/内存/磁盘/PHP/MySQL监控+慢日志降级+缓存分析
- **AI模板定制化** - 字段映射+质量检测+配图发布+参考示例，5大Tab深度配置
- **FLUX/DALL-E配图** - 多模型AI配图引擎，通义万相/FLUX.1/DALL-E 3三Provider+自动降级+异步轮询
- **AI数据分析报告** - 日报/周报/月报AI生成，异常检测+关键发现+建议+Markdown导出
- **AI配色推荐** - 行业/风格偏好+AI大模型配色+HSL降级，实时预览+一键应用
- **优惠券系统** - 满减/折扣/免邮3种券，新人券自动发放，前台会员券中心+小程序领券
- **评价评分系统** - 5星评分+文字评价+媒体上传+回复管理+匿名+点赞+审核
- **全阶段邀请奖励** - 注册/签到/付费三阶段递进奖励，邀请排行+明细
- **小程序完善** - 11页面全覆盖+完整设计体系(487行全局样式)
- **性能优化** - FluxProvider异步化(DB任务表+CLI轮询)+图片懒加载(IntersectionObserver)+CDN系统化(模板精准+str_replace兜底)
- **CSS变量化** - 4套style.css硬编码→完整CSS变量体系，双主题独立设计令牌
- **批量内容管理** - 后台全选/多选+批量审核/删除/移动分类/推荐，确认弹窗防误操作
- **API文档自动生成** - PHP Reflection+DocBlock解析，后台Swagger风格展示+Markdown导出
- **6种内容类型** - 产品/案例/新闻/下载/招聘/单页，支持扩展字段
- **简化RBAC** - 3级角色（超管/管理员/编辑），配置文件权限控制
- **I8j标签引擎** - 自定义模板标签 `{i8j:infolist}`/`{i8j:catelist}`/`{i8j:bannerlist}`/`{i8j:linklist}`/`{i8j:medialist}`/`{i8j:commentlist}`，灵活调用数据
- **CSS静态资源分离** - 内联样式提取为独立CSS文件，`public/skin/` 目录统一管理
- **PJAX无刷新** - 后台PJAX切换，51个模板script迁移至js block，体验流畅
- **双主题后台** - default(经典) / corporate(企业) 两套后台主题自由切换
- **安装向导** - Web端5步安装，自动建表、创建管理员
- **富文本编辑** - TinyMCE 6+ 编辑器，支持媒体库选择和AI辅助

### V2.9.3 新增特性

- **数据备份恢复增强(M26, P0)** - BackupService重写：分块流式导出(chunk 1000)修复OOM+gzip压缩(gzopen流式写入)+文件备份(ZipArchive)+恢复安全保护(自动快照+二次确认+多行注释感知SQL分割)+BackupCommand CLI(crontab调度)+清理旧备份
- **多渠道分发稳定性增强(M28, P0)** - ContentService自动同步钩子(publish后自动推送到已启用平台)+微信公众号formatContent增强(图片自适应+视频/表格转提示+标签清理)+头条号formatContent增强(结构化富文本适配)+refreshAllTokens()批量Token刷新+编辑页"同步到平台"按钮
- **插件商店首页升级(M25续, P0)** - 横向分类标签导航(Pills)+推荐位卡片(前4个)+搜索栏增强+plugin-store.js前端组件(安装/上传/搜索防抖)+详情页(截图轮播+版本历史+系统要求+安装统计)+已安装卡片跳转管理页+双主题模板升级
- **会员权益补全(M20续, P1)** - 会员等级进度页(4套前台模板default/corporate×pc/mobile)+AutoDowngradeCommand CLI(crontab每日触发)+7天缓冲期降级预警+isInGracePeriod()独立缓冲期判断+降级确认通知+grace_end_time字段
- **V2.9.2遗留补全** - AiTranslationService.retryFailed()翻译失败重试+ZhihuPlatform注册到bootAdapters()+构造函数统一化
- **Bug修复与体验优化** - GLOB_BRACE Alpine兼容+nginx /admin重写修复+会员头像上传(后台+前台)+图标选择器+下拉溢出修复+默认头像+PWA提示7天冷却+logo尺寸统一+登录页动态logo

### V2.9.2 新增特性

- **多语言AI深度翻译(M19a, P0)** - AiTranslationService翻译引擎，支持批量翻译+翻译记忆缓存+SEO字段翻译+防递归机制，翻译内容创建为独立Content记录(lang+translation_of)
- **Sitemap+结构化数据基础(M19b-core, P0)** - SeoService增强：Sitemap索引拆分(>50000条自动拆分)+增量缓存+robots.txt动态生成；SchemaService新增：Article/Product JSON-LD+BreadcrumbList+WebSite SearchAction
- **多语言Sitemap(M19b-hreflang, P0)** - 多语言hreflang标签生成+各语言独立Sitemap(/sitemap/{lang}.xml)+搜索引擎自动Ping(Google/Bing)
- **插件市场框架(M25, P0)** - PluginMarketService远程浏览+一键安装+ZIP本地上传+更新检测，双轨安装架构，后台市场浏览页+上传区
- **会员等级权益深化(M20, P1)** - 权益配置后台(折扣倍率/签到倍率/AI配额/VIP标识/专享内容)+签到积分×points_rate+购买折扣兼容(百分比/倍率双语义)+VIP标识展示+手动降级+升降通知
- **PWA离线支持(M22a, P1)** - manifest.json+Service Worker(StaleWhileRevalidate/Cache First/Network Only分层策略)+PWA安装提示+iOS Safari降级
- **H5移动端深度优化(M22b, P1)** - 骨架屏+无限滚动+手势滑动(左右翻页)+下拉刷新+底部Sheet图片查看器(2套mobile主题覆盖)
- **数据导出增强(M23, P1)** - ExportService.advancedExport()+CSV流式导出(UTF-8 BOM)+XLSX分块导出+后台高级筛选对话框
- **知乎专栏适配器(M20b, P1)** - 复用PublishPlatformInterface策略模式，ZhihuPlatform OAuth2+内容API发布
- **系统性能监控面板(M24, P2)** - MonitorService指标采集(CPU/内存/磁盘/负载/PHP/MySQL/缓存)+MySQL慢日志降级+MonitorController+双主题监控面板
- **Open Graph标签增强** - og:type/og:title/og:description/og:image/og:url/og:locale/og:locale:alternate，4套layout.html注入
- **V2.9.2收尾** - v2.9.2.sql(3字段ALTER+12配置项)+menu.php新增5组菜单+permission.php新增5组权限+route.php新增4条Sitemap路由

### V2.9.1 新增特性

- **FluxProvider异步化(M14a)** - DB任务表+CLI轮询替代sleep(2)阻塞，前端AJAX进度轮询(4种状态+进度%)，30次/90秒超时+3次错误重试
- **图片懒加载系统化(M14b)** - IntersectionObserver统一组件+4套layout引入+8+模板img→data-src替换+富文本自动拦截
- **CDN URL替换系统化(M14c)** - 模板层精准替换(cdn_enabled/cdn_domain变量)+响应层str_replace兜底(仅src/href)+后台开关可控
- **CSS变量化深度改造(M14d)** - 4套style.css硬编码→完整CSS变量体系(default+corporate各pc/mobile)，独立设计令牌
- **AI数据分析报告(M9)** - Model层数据采集+AI分析引擎(日报/周报/月报)+异常检测+关键发现+建议+邮件推送+Markdown导出
- **API文档自动生成(M10)** - PHP Reflection+DocBlock解析+路由匹配，后台分组展示+Swagger风格+Markdown导出
- **评价媒体上传前端(M15a)** - 多图预览/进度/拖拽/删除组件，复用/api/upload/image接口，4套detail.html集成
- **评价回复独立表(M15b)** - i8j_rating_reply表+管理员回复+会员追问，前台回复展示+后台回复管理
- **免邮券对接物流模块(M16a)** - ShippingService运费计算+免邮阈值+免邮券识别+CouponTemplate.free_shipping支持
- **aiSuggest接入AI大模型(M16b)** - AiService.colorSuggest()AI配色+行业/风格偏好选择器+HSL降级预设
- **小程序样式补全(M16c)** - app.wxss完整设计体系(487行/10大模块:变量/布局/间距/卡片/按钮/表单/列表/图文/状态/工具类)
- **AI配图URL本地化(M17)** - ImagePollCommand下载远程配图到本地StorageService+Content.cover自动回写
- **批量内容管理增强(M18)** - 后台全选/多选+批量审核/删除/移动分类/推荐/取消推荐，确认弹窗防误操作
- **V2.9.1收尾** - menu.php新增report/apidoc菜单、permission.php新增4组权限、v2.9.1.sql(3表+CDN配置+免邮配置+语言切换器)

### V2.9 新增特性

- **AI模板高度定制化** - 5大Tab页(基本信息/生成规则/字段映射/配图发布/参考示例)，自定义字段动态增删，6种转换规则，质量检测配置(评分阈值/自动重试/低质处理)
- **AI模板表单联动** - 生成模式(NLP/参考示例)切换联动，字段映射动态交互，结构化JSON采集(fields_json/field_mapping_json/quality_config_json)
- **FLUX/DALL-E Provider** - ImageProviderFactory工厂+3Provider(通义万相/FLUX.1/DALL-E)，Provider自动降级，5种FLUX风格+3种DALL-E尺寸
- **小程序100%完善** - 11个页面(index/detail/search/login/mine/category/payment/signin/coupon/invite/order)，优惠券双Tab+邀请三阶段奖励+7日签到里程碑+订单管理
- **全阶段邀请奖励** - InviteRewardService三阶段(register→signin→pay)，事件驱动挂接，邀请排行+明细+邀请码唯一索引
- **优惠券系统** - CouponService完整CRUD，3种券类型(满减/折扣/免邮)，库存扣减+每人限领+唯一券码，新人券自动发放，5种状态流转
- **评价评分系统** - RatingService评分+文字评价+匿名+审核，Redis防重复点赞，PC/Mobile/小程序三端评价区块，星级分布统计
- **前台模板可视化预研** - TemplateDesignController+后台CSS变量编辑器，HSL色彩推导AI配色，:root CSS变量动态注入(4套layout.html)，i8j_theme_config持久化
- **多语言翻译完善** - AI批量翻译(AiService::translateBatch)，前台4套模板语言切换器，LanguageController字段名统一(is_enabled)
- **V2.9收尾** - menu.php+permission.php+module注册3项同步更新，v2.9.sql完整迁移(5张新表+字段补全+配置项)

### V2.8 新增特性

- **AI配图生成** - 通义万相Provider+ImageProviderFactory工厂模式，编辑器一键AI生成封面图
- **AI内容质量检测** - 5维度评分(可读性/SEO/原创性/结构/吸引力)+改进建议，编辑器实时提示
- **AI SEO优化助手** - 一键优化SEO标题/关键词/描述，搜索引擎结果预览卡片
- **运营数据报表中心** - Dashboard ECharts可视化图表，内容/PV/收入/趋势多维分析
- **流量分析看板** - 来源分析/设备分布/24小时时段/受访页面排行，ECharts交互图表
- **AI生成统计** - 生成趋势/Provider消耗占比/任务类型/质量分布，量化AI投入产出
- **VIP免费阅读范围** - 后台配置VIP会员免费阅读模式(0=不免费/1=全部免费)
- **社交分享** - 微信/微博/QQ分享按钮+OGP Meta+分享统计埋点
- **邀请返积分** - 邀请码+注册奖励+IP防刷(限3次)+邀请排行/明细，会员增长闭环
- **权限配置完善** - 新增traffic/ai_stat/invite三组权限映射，非超管角色可正常访问

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

### V2.6 改进

- **CSS静态资源分离** - 后台/前台/登录页内联CSS提取为外部文件，`public/skin/` 目录管理
- **PJAX核心修复** - 51个模板`<script>`从content block迁移至js block，解决PJAX切换脚本丢失
- **数据导入修复** - ImportController分类查询+ImportService CSV导入+权限映射修正
- **模板变量注入** - `$skin_admin`(后台) / `$skin`(前台) 自动注入CSS路径变量
- **Nginx配置更新** - deploy/nginx + docker/nginx 添加 `/skin/` 路径支持
- **调试文件清理** - 移除调试临时文件，.gitignore增强忽略规则

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

### 更早版本特性

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
| 数据库 | MySQL 8.0 | 49+张数据表，前缀 i8j_ |
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
│   ├── v2.7.sql                #   V2.7增量更新
│   └── v2.8.sql                #   V2.8增量更新
│   └── v2.9.sql                #   V2.9增量更新
│   └── v2.9.1.sql              #   V2.9.1增量更新
│   └── v2.9.2.sql              #   V2.9.2增量更新
│   └── v2.9.3.sql              #   V2.9.3增量更新
├── miniprogram/                # 微信小程序(V2.9: 11页面)
│   ├── pages/                  #   页面(index/detail/search/login/mine/coupon/invite/signin/order/payment/category)
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
| i8j_invite_relation | 邀请关系表 | id,inviter_id,invitee_id,invite_code,invitee_ip,reward_points,reward_stage |
| i8j_coupon_template | 优惠券模板表 | id,coupon_name,coupon_type,condition_amount,reduce_amount,total_stock,remain_stock,per_user_limit,start_time,end_time,scope_type,scope_value,status |
| i8j_user_coupon | 用户优惠券表 | id,member_id,template_id,code,coupon_type,condition_amount,reduce_amount,status,used_at,expire_at |
| i8j_content_rating | 内容评价评分表 | id,content_id,member_id,rating,title,content,has_media,media_urls,is_anonymous,reply_count,like_count,status |
| i8j_rating_reply | 评价回复表 | id,rating_id,user_id,member_id,content,create_time |
| i8j_image_task | 配图异步任务表 | id,task_id,provider,poll_url,status,prompt,result,attempts,max_attempts,related_type,related_id,error_msg,retry_count,local_path |
| i8j_ai_report | AI分析报告表 | id,type,title,period_start,period_end,raw_data,summary,findings,anomalies,recommendations,sections,status |
| i8j_theme_config | 主题配置表 | id,theme,scope,scope_id,config_key,config_value,config_type,label,description,sort |
| i8j_publish_platform | 发布平台表 | id,name,code,type,config_json,status,access_token,refresh_token,token_expire_time |
| i8j_points_exchange | 积分兑换表 | id,user_id,product_id,points_cost,status,create_time |
| i8j_member_level | 会员等级表 | id,name,min_points,max_points,icon,discount_rate,points_rate,daily_ai_quota,is_default |
| i8j_member_benefit | 会员权益表 | id,level_id,benefit_type,benefit_key,benefit_value,description |

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
| GET | /api/coupon/list | 优惠券列表 |
| GET | /api/coupon/my | 我的优惠券 |
| POST | /api/coupon/receive | 领取优惠券 |
| POST | /api/coupon/newbie | 新人券领取 |
| GET | /api/rating/list | 内容评价列表 |
| POST | /api/rating/submit | 提交评价 |
| POST | /api/rating/like | 评价点赞 |
| GET | /api/image/status | 配图任务状态查询(V2.9.1) |
| POST | /api/image/batch_status | 配图任务批量查询(V2.9.1) |
| GET | /api/invite/info | 邀请信息 |
| GET | /api/invite/records | 邀请记录 |
| GET | /api/language/index | 语言列表 |
| POST | /api/language/switch | 切换语言 |
| GET | /api/language/current | 当前语言 |

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
| V2.9.3 | 2026-05 | 数据备份恢复增强(分块流式+gzip+文件备份+CLI+恢复安全保护)/多渠道分发增强(自动同步+formatContent+Token刷新)/插件商店首页(分类导航+推荐位+搜索+详情页+卡片跳转)/会员权益补全(等级进度页+自动降级CLI+isInGracePeriod+7天缓冲期) |
| V2.9.2 | 2026-05 | AI深度翻译/SEO分发增强(Sitemap拆分+JSON-LD+hreflang)/插件市场/会员权益深化/PWA/H5移动端优化/数据导出增强/系统监控 |
| V2.9.1 | 2026-05 | FluxProvider异步化/懒加载/CDN/CSS变量化/AI报告/API文档/评价媒体+回复/免邮券/AI配色/配图本地化/批量管理 |
| V2.9.0 | 2026-05 | AI模板定制化/FLUX+DALL-E配图/优惠券系统/评价评分/邀请奖励/小程序完善/模板可视化预研 |
| V2.8.0 | 2026-Q2 | AI配图/质量检测/SEO优化/运营报表/流量分析/AI统计/社交分享/邀请返积分 |
| V2.7.0 | 2026-Q1 | API安全加固/付费章节/积分签到/表单编辑器/搜索增强/CDN集成 |
| V2.6.0 | 2025-Q4 | CSS静态资源分离/PJAX核心修复/数据导入修复 |
| V2.5.1 | 2025-Q3 | 微信支付V3/AI批量生成/多AI模型/采集/多平台发布/邮件/Redis缓存 |
| V2.4.0 | 2025-Q2 | 多语言支持/模板市场/插件系统/搜索增强 |
| V2.3.0 | 2025-Q1 | 定时发布/SEO管理/会员系统/评论/广告/数据导入导出/API |
| V2.2.0 | 2025-Q1 | 回收站/版本历史/富文本增强/操作日志 |
| V2.1.0 | 2024-Q4 | AI智能写作(DeepSeek)/审核工作流/媒体资源库 |
| V2.0.0 | 2024-Q4 | 基础CMS：内容管理/分类/标签/媒体/轮播图/友情链接 |

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
