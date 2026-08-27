# 八界AI-CMS V2.9.48

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.9.48-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.9.48 是基于 ThinkPHP 8.1 多应用模式构建的企业智能内容管理系统，集成 DeepSeek / OpenAI / Qwen / GLM / ERNIE 多模型AI接口，为内容创作提供智能辅助。

## 新增特性

### V2.9.48 — 保存卡死根治·版本号权威化·安装升级稳健性修复
- **后台按钮"保存中"卡死全面修复** — ajaxPost 业务失败/网络错误时也回调，任何未选必填、保存校验失败都不再卡按钮
- **内容编辑器特殊字符卡死根治** — 改用原生 DOM 提取编辑器内容，彻底规避 TinyMCE getContent/triggerSave 对 `#`/连续大写英文的正则卡死
- **系统版本号权威化** — 版本号仅由 `config/app.php` 决定，后台不可手动编辑；系统设置项改为中文且升级项改纯内存缓存
- **安装/升级稳健性修复** — install.sql config 表连续逗号修复、SQL注入检测正则收紧不再误判 `#` 内容、摘要为空时列表/详情页 500 修复、草稿自动保存补全 CSRF token

### V2.9.47 — 分类列表类型标识配色恢复·模型字段命名统一·信息模型字段优化
- **分类列表类型标识恢复彩色** — 类型徽章从统一灰色恢复为按类型着色（信息=绿/单页=深/产品=蓝/案例=青/下载=黄/招聘=灰/图集=红/视频=青），颜色映射集中到 ContentTypeMap
- **模型扩展字段命名统一** — install.sql 种子数据旧前缀命名（product_price/info_author 等）统一为无前缀新命名（price/author/source 等），修正类型与字段错位
- **信息模型字段优化** — 扩展字段"作者"改为"发布者"，新增"联系方式"字段，前台详情页同步显示
- **系统版本号权威化** — 版本号仅由 `config/app.php` 决定，后台不可手动编辑；系统设置项改为中文且升级项改纯内存缓存
- **后台按钮"保存中"卡死全面修复** — ajaxPost 业务失败/网络错误时也回调，任何未选必填、保存校验失败都不再卡按钮
- **内容编辑器特殊字符卡死根治** — 改用原生 DOM 提取编辑器内容，彻底规避 TinyMCE getContent/triggerSave 对 `#`/连续大写英文的正则卡死
- **安装/升级稳健性修复** — install.sql config 表连续逗号修复、SQL注入检测正则收紧不再误判 `#` 内容、摘要为空时列表/详情页 500 修复、草稿自动保存补全 CSRF token

### V2.9.45 — 在线升级系统完善·URL体系统一·内容模型优化
- **后台一键在线升级** — 通过 Gitee Releases API 检测新版本，下载增量升级包，自动执行数据库迁移和文件更新
- **Dashboard 版本提醒** — 首页顶部显示 NEW 徽章和升级入口，仅读取缓存不阻塞页面加载
- **升级配置中心** — 后台可配置是否开启检查、升级通道（stable/beta）、Gitee Token
- **升级安全锁** — `upgrade.lock` 防止并发升级，超时自动失效
- **环境检查增强** — 检查 PHP 版本、扩展、目录写权限、磁盘空间、`disable_functions`
- **manifest 增强** — 支持 `deleted_files` 删除清单、`run_after` 后置命令、`run_after_suffix` 按后缀批量执行脚本
- **SQL 不自动回滚** — 升级失败仅回滚文件，数据库备份保留供管理员手动恢复，避免数据丢失
- **升级包生成 CLI** — `php think upgrade:package [from] [to] [--diff]` 自动生成含 manifest.json 的 ZIP 升级包
- **30 分钟版本缓存** — 减少 Gitee API 调用频率
- **二次确认对话框** — Bootstrap Modal 替代原生 confirm，显示版本信息和风险提示
- **升级历史与回滚** — 记录每次升级日志，支持手动回滚到任意历史版本
- **URL 体系统一** — 参考 eyoucms/织梦模式，列表页 `/{seo_url}` + 详情页 `/{seo_url}/{id}`，旧 URL 301 重定向到规范 URL
- **内容模型 ID 调整** — 信息资讯(id=1)和单页介绍(id=2)调到最前面，sort 排序统一，删除冗余 sort_order 字段
- **分类类型动态化** — 分类编辑页"内容模型"下拉从 content_model 表动态读取，用户新建模型自动出现在分类类型中
- **Banner 轮播按钮** — 半透明圆形背景确保箭头在白色背景上可见

### Sprint H5-MOBILE — 移动端完善
- **H5用户中心完善** — 用户配置API(H5UserConfigService)+评论API(H5CommentService)+分享API，JWT认证+频率限制
- **H5内容详情增强** — RichTextRenderer+ImageGallery+VideoPlayer+AttachmentList+ReadMode+RelatedContent组件
- **H5评论互动** — CommentList+CommentForm+CommentItem+EmojiPicker，点赞+回复+表情
- **H5分享扩散** — ShareSheet+SharePoster+useShare，链接分享+海报生成+追踪统计
- **H5性能优化** — Vite分包+路由懒加载+骨架屏+图片懒加载

### Sprint AI-DEEP2 — AI深化
- **AI批量生成增强** — 队列异步批量生成+CSV去重+任务进度追踪
- **AI内容质量控制** — 8维度评分(语义/事实/语法/可读性/SEO/AI检测/原创/综合)+批量检查+质量统计看板
- **AI推荐引擎** — 协同过滤(40%)+内容相似度(30%)+热门(20%)+新鲜度(10%)混合策略，5分钟缓存，配置界面
- **AI知识库管理** — RAG架构：MySQL全文索引+TF-IDF混合排序(70:30)，文档分块(500字)，精确匹配+模糊匹配，AI对话集成

### Sprint DATA-DEEP2 — 数据深化
- **数据大屏交互增强** — SortableJS拖拽布局+分享链接(密码保护+有效期)+模板管理(预置5行业模板+克隆)
- **报表导出增强** — PDF/Excel/CSV多格式导出+自定义列选择+批量导出
- **数据报告订阅** — 日报/周报/月报+4推送通道(邮件/站内/微信/短信)+定时调度
- **数据预警引擎** — 3种预警(阈值/趋势/异常检测)+3级别(info/warning/critical)+4通道通知+冷却期防重复

### Sprint I18N-V3 — 国际化增强
- **翻译记忆增强** — SHA256精确匹配+Jaccard模糊匹配+术语表一致性校验+AI辅助补全
- **多语言路由** — 3种URL策略(子目录/子域名/参数)+LanguageDetectMiddleware(Cookie→Accept-Language→默认)+路由别名映射
- **i18n内容管理** — 多语言内容关联组+同步更新通知+审核流程+AI辅助翻译
- **多语言标签** — 标签映射+AI批量翻译+三级回退+一致性校验

### Sprint COMPLIANCE2 — 合规安全深化
- **审计日志查询** — 高级搜索(时间/类型/用户/IP/风险)+统计分布+导出(CSV/JSON)+风险事件统计
- **合规报告** — GDPR合规报告+数据安全报告+审计统计报告+评分体系
- **数据脱敏策略配置** — 8类数据×8场景脱敏规则(基于V2.9.37 DataMaskEngineService)
- **数据分级分类** — 4级分类(public/internal/confidential/restricted)+自动识别(6种模式)+手动标记

### Sprint SYS-ROBUST2 — 系统健壮性深化
- **监控告警增强** — 规则引擎(阈值/趋势/异常)+4通知通道+冷却期+日志记录
- **日志管理** — 高级搜索+归档+清理+统计+导出
- **性能诊断** — CPU/内存/磁盘/慢查询实时监控+历史趋势+告警阈值
- **自动扩缩容** — 策略配置+触发条件+扩容动作+回滚机制

### Sprint DEV-ECO2 — 开发者生态深化
- **插件开发工具链** — plugin:build(代码扫描+配置校验+ZIP打包+签名)+plugin:publish(校验+上传+审核通知)
- **市场接入优化** — AI预审(代码质量)+快速通道(安全≥90+AI通过+作者信用≥80)+7步审核增强
- **开发者文档** — 7种代码模板生成(Controller/Service/Model/Middleware/Command/Config/Hook)+调试沙箱+状态检查+测试运行

## 版本历史

| 版本 | 时间 | 核心功能 |
|------|------|----------|
| **V2.9.48** | 2026-08 | **保存卡死根治·版本号权威化·安装升级稳健性修复**: ajaxPost业务失败/网络错误不回调导致按钮卡"保存中"全面修复+内容编辑器特殊字符(#/连续大写英文)卡死根治(改用原生DOM提取编辑器内容规避TinyMCE getContent/triggerSave正则卡死)+系统版本号仅由config/app.php决定(后台不可编辑)+系统设置项改中文+upgrade_*改纯内存缓存+ConfigService description→remark bug修复+install.sql config表连续逗号修复+SQL注入检测正则收紧不再误判#内容+摘要为空时列表/详情页msubstr 500修复+草稿自动保存补全CSRF token |
| **V2.9.47** | 2026-08 | **分类列表类型标识配色恢复·模型字段命名统一·信息模型字段优化**: 分类列表类型徽章恢复按类型着色(颜色映射集中ContentTypeMap)+install.sql模型扩展字段旧前缀命名统一为无前缀新命名(price/author/source等)+修正类型与字段错位+信息模型扩展字段"作者"改"发布者"+"联系方式"新增+前台详情页同步+系统设置页PJAX主题列表加载/红字报错/主题卡片样式恢复+分类模板按模型过滤+切换模型实时联动+单页模板修正+前台详情页上一页/下一页翻页 |
| **V2.9.45** | 2026-08 | **在线升级系统完善·URL体系统一·内容模型优化**: Dashboard版本提醒+NEW徽章+升级配置中心（开关/通道/Token）+升级锁+disable_functions检查+manifest增强（deleted_files/run_after/run_after_suffix）+SQL不自动回滚+升级包生成CLI+30分钟缓存+二次确认对话框+双皮肤后台模板+权限配置+URL体系统一(列表/{seo_url}+详情/{seo_url}/{id}+旧URL 301重定向)+内容模型ID调整(信息id=1/单页id=2)+sort_order冗余字段清理+分类类型动态化(从content_model读取)+Banner轮播按钮可见性修复 |
| **V2.9.42** | 2026-08 | **前台导航动态化·注册CSRF修复·付费开关修复·GBK/PUA乱码根除·评价区去重·卡片标题颜色优化**: 导航从硬编码改为数据库动态读取(4套layout)+注册表单CSRF token注入修复+付费阅读checkbox关闭无效修复(ContentService补零+隐藏域)+GBK双重编码乱码全量根除(1256模板0残留)+PUA字符全量清理(18文件)+详情页移除重复评价区仅保留评论+后台卡片标题恢复默认样式+SEO卡片移除黄色边框+JS语法修复(引号缺失+星号符号)+删除重复CommentAdminController+install.sql编码清理合并 |
| **V2.9.41** | 2026-07 | **表前缀自定义·Model类型修复·数据报表修复·无用文件清理**: install.sql{prefix}动态替换+Db::name规范化+Model删$table+$cast→$type回退+ReportEngineService容错+jsonToTags()badge显示+无用文件清理 |
| **V2.9.40** | 2026-07 | **移动端H5完善·AI深化·数据深化·国际化增强·合规安全·系统健壮性·开发者生态**: 28功能点(Sprint H5-MOBILE/AI-DEEP2/DATA-DEEP2/I18N-V3/COMPLIANCE2/SYS-ROBUST2/DEV-ECO2)/8新表+6ALTER(15字段+4索引)/H5用户中心完善+内容详情增强(富文本/画廊/视频/附件/阅读模式)+评论互动+分享扩散+性能优化/AI批量生成增强(队列异步)+内容质量控制(8维度评分)+推荐引擎(协同过滤40%+内容30%+热门20%+新鲜10%)+知识库RAG/数据大屏交互增强+报表导出(PDF/Excel/CSV)+报告订阅推送+预警引擎/翻译工作流优化(记忆库+术语库)+多语言URL路由+i18n内容管理+模板标签/审计日志查询+合规报告导出+脱敏策略配置+数据安全分级/监控告警+日志管理+性能诊断+自动扩缩容/插件开发工具链(CLI)+市场接入优化+开发者文档 |
| **V2.9.39** | 2026-07 | **移动端H5构建·AI深化·数据分析深化·国际化增强·合规安全·系统健壮性·开发者生态**: 28功能点(Sprint H5-FRONT/AI-DEEP/DATA-DEEP/I18N-V2/COMPLIANCE/SYS-ROBUST/DEV-ECO)/9新表+7次ALTER(21字段)/Vue3+Vite H5独立前端+API网关+PWA+模板适配+支付闭环/AI对话增强(多轮+记忆+历史)+辅助写作(续写/改写/扩写)+工作流4新节点+模型配置中心/数据大屏(ECharts)+智能报表(AI分析)+趋势预测(线性回归)+数据钻取/翻译工作流+i18n全覆盖+多语言SEO+RTL布局/GDPR合规(Cookie同意+数据权利)+审计日志增强+数据脱敏+安全中心/备份恢复+灰度发布+配置中心+健康检查/开发者社区+插件市场闭环+CI/CD集成 |
| **V2.9.38** | 2026-07 | **AI能力深化·开放平台·系统集成·运营工具深化·性能优化二期**: 25功能点(Sprint AI-PLUS/OPEN-PLAT/SYS-INTEG/OPS-DEEP/PERF-II/QA)/5新表+6次ALTER(20字段)/AI工作流引擎(SortableJS+4预设)+批量管线(CSV+去重+质量阈值)+智能体引擎(三层架构+5预设)+内容增强V2(知识图谱D3.js)+智能体市场/开发者门户+Python/Node.js SDK+Swagger UI+应用市场/第三方登录4种+支付宝/银联支付+短信3适配器+邮件V2 3适配器+统一通知中心(7场景×4通道)/A/B测试(Z检验)+用户分群5规则+运营自动化(When-If-Then)+质量监控5维看板/读写分离+think-queue 6队列+Redis 6高级应用+静态资源优化 |
| **V2.9.37** | 2026-07 | **小程序完整版·国际化多语言前台·AI助手增强·插件市场生态闭环·SEO优化**: 28功能点(Sprint MINI-FULL/I18N/AI-HELPER/PLUG-ECO/SEO/QA)/9新表+6次ALTER(23字段)/小程序SDK(8模块)+H5 Vue I18n+可视化配置+22组件+统计+消息/多语言切换+语言包AI翻译+版本快照+翻译记忆/AI推荐(5策略)+智能问答+报表解读+模板增强+质检升级/开发者审核+版本管理+插件统计+API开放平台/结构化数据15+Schema+Sitemap增强+性能SEO+GEO+SEO报告 |
| **V2.9.36** | 2026-07 | **内容模型增强·插件商店完善·小程序基础框架·任务系统增强**: 25功能点(Sprint CM/PLUG-SHOP/MINI/TASK/QA)/8新表+4次ALTER(23字段)/28种自定义字段+内容模型CRUD+6种关系+20种验证规则+10种布局表单设计器+导入导出增强/插件商店前端+交易流程+付费下载+评价评分+商店后台/小程序API(JWT+200次/min)+HTML转WXML+微信登录+模板适配+管理后台/多人分配+甘特图+催办通知+任务统计+任务模板 |
| **V2.9.35** | 2026-07 | **安全增强·性能优化·插件市场框架**: 20功能点(Sprint SEC/PERF/PLUG/QA)/6新表+5组ALTER(18字段)/XSS输入过滤+CSRF增强+HTMLPurifier+SQL注入检测18正则+AES-256-CBC加密+密钥轮换+密码策略+数据脱敏+文件上传二次渲染+SVG过滤+SHA256+资源级权限+权限审计+异步安全日志+日周月报+合规检查/缓存穿透雪崩击穿三重防护+慢查询+索引检测+CDN压缩+WebP+静态化+性能监控ECharts/插件管理+钩子系统+商店对接+沙箱安全检测+CLI脚手架 |
| **V2.9.34** | 2026-07 | **多语言内容管理完善·内容分发增强·会员体系与内容付费·数据报表增强·内容运营中台**: 26功能点(Sprint ML/CD/MEM/DR/OPS2)/8新表+5ALTER/多语言站点+SEO+URL路由+同步状态机+翻译工作台/微信+头条+知乎+微博适配器+分发日志+定时分发/会员等级5级+积分防刷+付费内容+VIP订阅/报表引擎+数据大屏+导出中心+内容分析4维度+10预置报表/运营工作台+生命周期9阶段+任务系统+AI运营助手 |
| V2.9.33 | 2026-07 | **AI内容质量闭环·模板商店运营增强·开发者赋能**: 22功能点(Sprint AI5/T5/DEV/CUS3/OPS)/6新表+5ALTER/5维评分引擎+AI修复管线+标签优化/5策略推荐+促销引擎+二级分类/CLI打包+API开放平台(11接口)/白名单100+50+响应式+引导页+组件库/SEO归因+图片审核+健康监控+版本引导 |
| V2.9.32 | 2026-07 | **遗留修复·内容智能化·模板生态·性能稳定性**: 24功能点(Sprint FIX/AI4/T4/CUS2/PERF2)/3新表+6ALTER/ALTER补全+配色22种7维增强+AI独立Service+Swiper完整版/AI配图风格匹配+自动触发+行业prompt 90种+SEO批量修复+趋势分析/模板评分评论5维+版本管理+排行+数据看板/自定义CSS/JS+样式导出导入+版本历史/Vite构建链(方案A)+多级缓存+移动端配置+安全审计更新 |
| V2.9.31 | 2026-07 | **模板生态深化·内容智能化续进·性能与稳定性**: 28功能点(Sprint T3/AI3/CUS/UX2/SEC/PERF)/6新表+4ALTER/模板推广活动+安装日志+前台商店/AI SEO诊断+Prompt模板+CLI命令/布局预设5种+配色6种/移动端中间件+空状态+通知中心+移动模板/安全审计+错误页+加密CLI+审计表/懒加载+CDN+缓存预热+性能CLI/双皮肤同步69文件5342行 |
| V2.9.30 | 2026-07 | **修复完善·质量增强·小功能增量**: 26功能点(Sprint R/Q/T2/AI2/UX/DOC)/6新表+3ALTER/38个commit修复正式纳版/功能看板+26种子数据/PHPUnit验收框架+31回归脚本/Git pre-push hook/4检测器扩充(33条规则)+评分体系/我的模板+收藏夹分类+批量管理+搜索增强+标签管理/AI批量改写(4模式+回滚)+SEO预览(评分+Google模拟)+AI配图(模板配图库)+多风格写作(6风格)/移动端适配+全局搜索(Ctrl+K)+快捷键+错误页美化/18个配置+API+部署文档 |
| V2.9.29 | 2026-06 | **内容模型差异化·V2.9.28修复完善·开发者生态启动·模板生态进阶·内容智能增强**: 31功能点(Sprint C/F/D/T/I)/Fallback链渲染引擎/5预置模型/pc+mobile双版40模板/Hook 3别名/用户画像/拼音搜索/开发者注册认证/模板打包ZIP/Webhook(HMAC+3s/5s超时)/API开放平台(双认证+IP白名单)/AI协同过滤推荐/模板自动审核/vis.js关系图谱/内容推荐引擎/定时发布/质量诊断/评论收藏点赞/字段级审计+回滚/订阅摘要推送 |
| V2.9.28 | 2026-06 | **模板商店后台完善·AI编辑器增强·插件市场在线安装·Hook事件扩展·移动端体验微调**: 34功能点(Sprint M/A/P/H/MO)/订单退款/发票管理/评价管理/统计看板/审核工作流/结算管理/段落优化/多轮对话/格式保留/选段翻译/20+模板库/版本快照/在线安装(SSE)/安全沙箱/19个新Hook事件/移动端导航/PWA增强 |
| V2.9.27 | 2026-06 | **内容模型差异化·SSE实时推送·模板商店商业化·基础设施完善**: 28功能点(Sprint S/T/U/V)/FieldTypeRegistry(13种)/动态表单/5张扩展表/模型专属分类/内容关系/5个前台专属模板/SSE DB持久化队列/Last-Event-Id断线重连/4种定价模式/AlipayPaymentChannel/GitHubOauthProvider/RSS Feed/系统健康检查 |
| V2.9.26 | 2026-06 | **模板商店增强·AI翻译增强·AI编辑器增强·移动端体验优化**: 21功能点(Sprint O/P/Q/R)/模板审核流/定价方案/质量标签/版本管理/分类SEO/AI翻译记忆+术语库/写作风格/主题CSS/SEO建议/前台响应式/分享按钮/懒加载 |
| V2.9.25 | 2026-06 | **插件生态开放·Hook事件扩展·模板商店数据运营**: 24功能点(Sprint K/L/M/N)/Hook事件系统(29个事件)/Hook调试面板/插件包管理/模板商店浏览/模板日统计/推荐系统 |

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

- PHP 8.2+（需启用扩展：pdo_mysql、mbstring、openssl、json、fileinfo、redis）
- MySQL 8.0+（utf8mb4）
- Redis 6.0+（可选，不安装则使用文件缓存）
- Web 服务器：Nginx / Apache（推荐 Nginx）

### 方式一：傻瓜式安装（推荐）

适用于宝塔面板、小皮面板等已配置好服务器环境的用户：

1. **下载完整安装包** — 从 [Gitee Releases](https://gitee.com/bajieai/ai-cms/releases) 下载 `AI-CMS-V2.9.46-full.zip`（含所有依赖，无需 Composer）
2. **创建网站** — 在宝塔/小皮面板中创建网站，将域名解析到服务器
3. **上传解压** — 将 ZIP 包上传到网站根目录并解压
4. **设置运行目录** — 网站运行目录设为 `public`
5. **添加伪静态** — 根据Web服务器类型配置伪静态规则（见下方）
6. **访问安装向导** — 浏览器访问 `http://你的域名/install.php`，按向导完成安装

> **提示**：完整安装包已包含 vendor 依赖，无需执行 `composer install`

### 方式二：Git 源码安装（开发者）

适用于熟悉 Git 和 Composer 的开发者：

```bash
# 1. 克隆仓库
git clone https://gitee.com/bajieai/ai-cms.git
cd ai-cms

# 2. 安装依赖
composer install --optimize-autoloader

# 3. 访问安装向导
# 浏览器访问 http://你的域名/install.php
```

### 伪静态配置

**Apache**：项目已内置 `.htaccess`（ThinkPHP 标准规则），无需额外配置。确保 Apache 开启了 `mod_rewrite` 模块。

**Nginx**：项目已内置 `public/nginx.htaccess` 伪静态规则（小皮面板自动加载）。手动配置时在 `location /` 块中添加：

```nginx
location / {
    rewrite ^/admin/?$ /admin.php last;
    rewrite ^/admin/(.*)$ /admin.php/$1 last;
    rewrite ^/install/(.*)$ /install.php/$1 last;
    rewrite ^/api/(.*)$ /api.php/$1 last;
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php/$1 last;
    }
}
```

> 宝塔面板用户：网站设置 → 伪静态 → 选择 `thinkphp` 即可。
> 小皮面板用户：项目 `public/nginx.htaccess` 已包含完整规则，小皮面板会自动加载。

**Apache**：项目已内置 `public/.htaccess`，包含 admin/index 多入口路由规则，无需额外配置。

> ThinkPHP 标准伪静态规则仅路由前台页面到 index.php，不支持后台 `/admin` 路径。

### Docker 安装（可选）

```bash
docker-compose up -d
# 访问 http://localhost/install.php
```

## 默认账户

| 角色 | 用户名 | 密码 |
|------|------|------|
| 超级管理员 | admin | admin123 |

> **安全提示**: 首次登录后请立即修改默认密码！

## 常用命令

```bash
# 数据库迁移
docker exec -i aicms_mysql mysql -u root -p < database/install.sql

# PHP 命令
php think plugin list          # 列出插件
php think plugin install xxx   # 安装插件
composer dump-autoload          # 刷新自动加载

# CLI 命令
php think seo:diagnose             # SEO诊断
php think seo:diagnose 123         # 诊断指定内容ID
php think seo:diagnose --all       # 批量诊断所有内容
php think perf:warmup --all        # 全量缓存预热
php think perf:warmup --stats      # 查看缓存命中率
php think upgrade:package 2.9.45 2.9.46  # 生成升级包
php think upgrade:package 2.9.45 2.9.46 --diff  # 基于git diff生成

# Docker 操作
docker-compose ps                             # 查看服务状态
docker-compose logs -f php                    # 查看PHP日志
docker-compose restart                        # 重启服务
```

## 许可证

MIT License — 详见 [LICENSE](LICENSE) 文件

Copyright (c) 2026 湖北八界智能技术有限公司
