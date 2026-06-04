# 八界AI-CMS V2.9.17

> 智能内容管理系统 (AI-Powered Content Management System)

![Version](https://img.shields.io/badge/version-2.9.17-blue)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple)
![ThinkPHP](https://img.shields.io/badge/ThinkPHP-8.1-green)

## 项目简介

八界AI-CMS V2.9.17 "翻译体验精修·多语言管理闭环" 是基于 ThinkPHP 8.1 多应用模式构建的企业智能内容管理系统，集成 DeepSeek / OpenAI / Qwen / GLM / ERNIE 多模型AI接口，为内容创作提供智能辅助。

**V2.9.17 核心定位：翻译体验精修·多语言管理闭环** — 2个Sprint 4项功能点（100%完成）：
1. **M-2：后台翻译语言管理UI(P1)** — TranslateLanguageController+Service(settings持久化+排序+自定义语言) + 双皮肤管理界面 + 即时联动编辑页/前台
2. **T-4：翻译轮询可配置化(P1)** — config/ai.php polling段(interval/fast/max/timeout) + ContentController注入 + 双皮肤content_edit模板 + translate_editor.js配置驱动+动态加速
3. **M-6：前台语言切换器+RTL(P2)** — _lang_switcher组件(双皮肤+国旗+本地名) + language_switcher.css(RTL适配) + 后台翻译状态列国旗显示
4. **E-2：翻译进度SSE实时推送(P2)** — AiTranslateController::stream() + SSE优先+轮询自动降级 + 30s超时保护

**V2.9.16 核心定位：翻译引擎增强·SEO诊断引擎** — 4个Sprint 11项功能点（100%完成）：
1. **Sprint 1：翻译引擎专项改进(P0)** — OpenAI翻译Provider完整实现(GPT-4o系列/重试/分段) + DeepSeek增强(16语言/指数退避/长文本分段) + Cache-based速率限制(RPM/RPH) + 插件化自动注册Provider + 统一语言配置类(消除硬编码)
2. **Sprint 2：配置文件挂载(P0)** — config/ai.php 扩展多Provider/多账号支持 + 速率限制配置 + fallback链配置 + 语言扩展配置
3. **Sprint 3：SEO诊断引擎(P1)** — SeoDiagnoseController(4维度20+检测项) + 综合健康度评分(A-F评级) + 环形Chart.js可视化 + 改进建议输出 + 双皮肤模板同步(default+corporate)
4. **Sprint 4：前端集成+队列消费(P1)** — translate_editor.js 扩展16语言兜底列表 + AiQueueConsume 翻译任务增强(多Provider/fallback/ContentLang持久化)

**V2.9.15 核心定位：质量增强·AI能力升级** — 3个Sprint 9功能点+11项P1修正（100%完成）：
1. **J方向：技术负债清理(P0)** — Provider轮询mock→真实API + batchSeoOptimize@deprecated标记 + Nginx SSE缓冲配置 + install脚本composer自动化
2. **K方向：AI翻译引擎(P0)** — DeepSeek翻译Provider + HTML正文分段处理 + 翻译版本缓存 + 编辑页/列表页/前台三端UI + 批量翻译队列
3. **L方向：SEO增强(P1)** — Schema.org结构化标记(5种类型×3页面注入) + OG/Twitter标签增强 + og:locale联动多语言切换

**V2.9.14 核心定位：体验精修·异步升级** — 3个Sprint 10项优化（100%完成）：
1. **P0：配图生成异步化** — 同步3次循环(最坏90秒) → 异步队列提交+前端轮询逐张显示，解决Nginx 60s超时断连
2. **P0：批量SEO真进度SSE** — 模拟进度(setInterval随机递增) → SSE服务端主动推送真实进度，含当前文章标题/成功失败计数
3. **P0：批量SEO暂停/继续** — 支持缓冲期暂停（当前任务完成后完全暂停）+ 恢复 + 刷新后状态恢复
4. **P1：AI模态框组件化** — 3个弹窗从content_edit.html内联90行抽离为shared子模板，CSS独立为ai_editor.css
5. **P1：diffVersions增强** — 版本差异对比增加文件大小变化(size_diff)和格式化显示
6. **P1：安装前质量校验扩展** — 从2项扩展到7项（满分100），新增必需文件/CSS有效性/JS有效性/跨模板引用/编码检查
7. **P1：看板体验微调** — 可关闭提示条+日期区间筛选+数字增长动画+图表骨架屏+卡片hover+刷新Loading

**V2.9.13 核心定位：内容智能化·运营增强** — 4方向18项功能（100%完成）：
1. **方向F：AI内容增强补完(P0)** — 编辑器AI配图（候选选择/轮询/确认/重新生成）+ AI SEO对比弹窗（单字段应用）+ 写作风格选择器（6种风格+示例句子）+ 批量SEO进度条 + TinyMCE工具栏AI按钮
2. **方向G：运营数据与流量分析(P1)** — 运营分析看板（PV/UV基于visit_log/内容分类分布/热门Top10）+ ECharts图表（趋势+环形图自适应）+ 不可关闭估算值提示条 + 快捷日期切换
3. **方向H：开发者工具补齐(P2)** — 网站主上传入口 + ZIP路径穿越防护 + 版本差异对比（diffVersions）+ 审核通过通知推送
4. **方向I：模板商店体验优化(P2)** — 详情页版本历史Tab + 安装前质量校验（quality_on_install记录）+ 评论图片上传

**V2.9.12 核心定位：模板生态·内容智能化** — 5大方向19项功能（100%完成）：
1. **方向A：模板商店生态(P0)** — 模板商店(10张表+3张ALTER)、网站主市场(卡片列表/筛选/预览)、安装/切换/支付(复用PaymentService)、评分评论(审核机制)、前台iframe预览
2. **方向B：AI模板质量管线(P0)** — 质量校验(CSS完整性/响应式/HTML标签)+修复管线(3次循环/修复率≥80%)+AI多配色变体(AI生成5种配色)
3. **方向C：模板自定义(P1)** — 样式定制(7色CSS变量/5字体/Logo/自定义CSS)+布局配置(8板块开关+SortableJS拖拽排序)+备份还原(自动备份5上限)+恢复官方默认
4. **方向D：AI内容智能化(P1)** — AI配图(通义万相/Flux/DALL-E三Provider+故障降级)+AI SEO优化(单条+批量+对比diff)+6种写作风格(正式/轻松/专业/资讯/营销/学术)
5. **方向E：开发者工具(P2)** — ZIP打包导出(.tpkg标准)+上传导入(安全校验+manifest验证)+审核流程(待审核/通过/拒绝/需修改)+命令行打包(php think template:package)+版本管理+更新通知

**V2.9.11 核心定位：主题模板生成系统混合模式改造 + CSS变量三套命名体系统一 + 骨架模板体系** — 6大模块：
1. **双模式AI主题生成** — 保留"从零生成"（AI生成HTML+CSS，65分阈值）+ 新增"基于骨架生成"（AI只生成CSS，70分阈值），用户在生成时可选择模式
2. **2种布局变体骨架模板** — 展示型骨架（Hero+轮播+三列卡片+动效）+ 内容型骨架（紧凑+侧栏+阅读优化），各含PC 8文件+Mobile 4文件+完整CSS
3. **CSS变量三套命名体系统一** — 修复tweak页面`--i8j-`前缀断裂导致配置对前台完全无效的Bug，统一为25个无前缀变量（--primary/--bg/--text等），FrontBaseController/AiThemeController/CssComponentLibrary完全对齐
4. **行业调色板体系** — 行业色板数据持久化+10行业默认色板（企业/电商/博客/门户/医疗/教育/餐饮/金融/科技/房产），生成时按行业自动加载色板
5. **theme:clean/theme:duplicate CLI命令** — clean支持dry-run/force/all模式清理不可用AI主题；duplicate支持骨架复制+sabberworm CSS解析器颜色替换+CSS变量引用完整性扫描
6. **后台生成页改造** — 生成模式选择卡片+骨架模板选择+行业类型下拉（带色板预览）+双皮肤同步（default+corporate）

**V2.9.10 核心定位：前台用户中心增强 + 缓存清除细分 + 后台菜单数据库化** — 3项优化：
1. **前台用户中心增强** — 统一用户中心入口、侧边栏导航分组、积分商城条件显示、导航栏登录下拉改造
2. **缓存清除细分** — 后台一键清除缓存拆分为5项（全部/内容/模板/插件/浏览器），支持按类型精准清理
3. **后台菜单重新分类** — 数据库驱动菜单替代硬编码，提供可视化菜单管理后台，6大分组重组

**V2.9.9 核心定位：从好用的CMS走向聪明的CMS** — 8大模块全面升级：AI内容模板引擎、社交分享轻量版+分享追踪、AI多语言国际化、SEO深度升级、插件市场完善、审批工作流增强、AI-GEO深度优化、会员等级深化+付费阅读权限控制。

**V2.9.8 核心新功能 — 模力精修：**

*第一轮 — 缺陷修复 + AI质量深化 + 定制打磨（12/12项）：*
1. **字体本地化** — 5组中文字体子集化woff2本地嵌入+font-display:swap，离线/内网可用
2. **撤销/重做** — UndoManager 30步栈+Ctrl+Z/Ctrl+Shift+Z快捷键+保存点+未保存提示
3. **AfterGenerate钩子** — AI生成后自动同步CSS资产+从class名推导CSS骨架+透明PNG占位图
4. **自动重试增强** — 3次指数退避(1s/3s/5s)+动态temperature(0.7/0.8/0.9)+5类错误分类+每日20次上限
5. **CSS质量评分器** — 7维度加权评分(变量/过渡/阴影/媒体查询/颜色/伪类/间距)+60分及格线+低质量人工审核队列
6. **CSS质量Prompt增强** — buildPrompt()增加7项CSS质量约束+4项禁止项
7. **预设快捷应用** — 5套系统配色预设(活力橙/沉稳黑/清新绿/暖木棕/冷静蓝)+theme.json presets+一键应用+撤销联动
8. **导出预览** — 导出前确认弹框+修改字段摘要(颜色/字体/布局/Logo)

*第二轮 — AI主题视觉升级 + 面板体验打磨 + 模板能力补充（9项+6小优化）：*
9. **Prompt行业风格加强** — 4行业配置(config/theme_styles.php)+buildIndustryPrompt()+CSS组件Prompt，AI模板从"通用"→"行业专属"
10. **CSS组件模式库** — CssComponentLibrary独立类(10组件:vars/hero/card/button/nav/grid/spacing/price/article/footer)，CSS质量从60分→80分
11. **评分器9维度+双线制** — +visual_design+layout_completeness，新模板65分/历史模板60分差异化阈值
12. **预设5→12套+智能推荐** — 科技紫/玫瑰金/自然绿/深海蓝/日落橙/极夜黑/樱花粉+color_schema文本匹配推荐+推荐标签
13. **新手引导+折叠面板** — OnboardingGuide 3步引导条+CollapsibleSection分步折叠+实时预览反馈(闪烁+Toast)
14. **导出后快捷操作** — 3按钮弹框(预览新模板/浏览市场/继续编辑)
15. **模板变量扩展至10页面** — 标签页/栏目页/自定义404/关于增强+{$contactEmail}/{$contactPhone}
16. **撤销栈超时清空** — visibilitychange事件+15分钟未操作自动清空
17. **恢复默认API+安装引导+分析页时间筛选** — defaultVars()/OnboardingGuide.init()/installTrendRange(7d/30d/90d/自定义)

**Sprint 11 — AI配图增强：**
1. **批量配图** — 前端串行调用+进度条，自动构建图片Prompt，配图直接插入编辑器正文段落之间
2. **发布自动配图** — 发布时无封面图自动调AI生成，config开关控制
3. **日配额控制** — 基于Cache的每日限额(默认50次/天/用户)，防止AI额度滥用

**Sprint 12 — AI SEO增强 + 来源分析：**
4. **SEO评分算法** — 纯算法零AI成本(5维度:标题30%+关键词20%+描述20%+内容长度15%+图片ALT15%)，毫秒响应
5. **SEO前后对比面板** — 双栏Modal展示优化前后差异，一键应用
6. **批量SEO优化** — 列表页批量选择+AI填充空SEO字段，3篇并发控制+2秒间隔防限流
7. **来源分析饼图** — Dashboard新增ECharts来源分析(直接/搜索/社交/外部)，7/30天切换

**Sprint 13 — 质量检测 + 社交分享 + 写作风格：**
8. **质量评分卡片UI** — 维度评分条+改进建议一键执行+一键优化全部
9. **社交分享** — 后台编辑页分享Modal(微博/QQ/复制)+卡片预览，前台详情页分享统计埋点
10. **5种可配置写作风格** — config/ai.php配置驱动(正式/轻松/专业/幽默/简洁)，后台风格下拉选择

**Sprint 14 — 模板批量生产流水线：**
11. **BatchThemeGenerateService** — 批量编排（行业分类+变体描述+进度追踪），CLI命令`php think theme:batch`，断点续传
12. **ThemeQualityService** — 纯算法5+1维度质量自检（结构30+CSS变量25+硬编码20+页面15+简洁5+相似度5）
13. **人工审核工作流** — 待审核列表，批量通过，质量评分，生成→审核→发布闭环
14. **模板安装安全校验** — ZIP Slip防护，路径穿越检测

**Sprint 15 — 模板市场前台：**
15. **10套预埋模板** — 5行业×2套，统一Design Token，离线可安装
16. **RemoteTemplateSource** — OSS静态JSON获取+CDN下载+10min缓存+离线降级
17. **模板市场UI** — 骨架屏+分类Tab+卡片网格+搜索排序+空状态+已安装标记
18. **一键安装+回滚** — 预埋本地复制/远程下载→校验→应用→切换引导，安装前自动备份

**Sprint 16 — 模板管理增强：**
19. **ThemeRate评分收藏** — 1-5星评分+收藏+平均评分同步，uk_user_theme唯一索引
20. **ThemeUpdateService** — 本地vs远程版本比对+24h缓存+红点Badge通知
21. **详情弹窗增强** — 文件列表/大小/修改时间/版本检测/收藏按钮
22. **分类管理后台** — CRUD+颜色选择器+图标+排序
23. **日志监控** — 主题操作日志（install/rollback/update/rate/switch/uninstall）+查询页面

**Phase 3.5L — 轻量发布：**
24. **ThemeFileService安全测试** — 11用例PHPUnit（路径穿越4+扩展名白名单3+写入验证2+回滚1+文件树扫描1）
25. **SEO评分缓存回写** — 3触发点（编辑保存/AI优化后/批量优化后自动保存seo_score字段）
26. **编码根治方案** — 7层防护体系，GBK双编码乱码清零

### 核心特性

- **🆕 后台语言管理UI(V2.9.17)** - 16语言checkbox管理+批量操作+拖拽排序+自定义语言添加，settings表持久化
- **🆕 前台语言切换器(V2.9.17)** - 双皮肤组件(国旗+本地名+RTL) + language_switcher.css + 后台翻译状态列国旗
- **🆕 翻译SSE实时推送(V2.9.17)** - stream()端点 + SSE优先+轮询降级 + 30s超时 + 5%阈值推送控制
- **🆕 轮询可配置化(V2.9.17)** - config/ai.php polling段(interval/fast/max/timeout) + Controller注入 + translate_editor.js改造
- **🆕 OpenAI翻译Provider(V2.9.16)** - GPT-4o/4o-mini/4-turbo 完整实现 + 指数退避重试 + 长文本自动分段翻译 + 16种语言
- **🆕 翻译引擎增强(V2.9.16)** - Cache-based速率限制(RPM/RPH) + 多Provider链式降级(fallback_chain) + 插件化自动注册Provider
- **🆕 SEO诊断引擎(V2.9.16)** - 4维度20+检测项 + 综合健康度评分(A-F) + Chart.js环形图 + 双皮肤模板(default+corporate)
- **🆕 配图链式降级(V2.9.16)** - ImageProviderRouter 多Provider遍历降级(tongyi→flux→dalle) + 默认社交分享图
- **🆕 AI翻译引擎(V2.9.15)** - DeepSeek翻译Provider + HTML正文分段处理 + 翻译版本缓存 + 编辑页/列表页/前台三端UI + 批量翻译队列
- **🆕 Schema.org结构化标记(V2.9.15)** - 5种类型(Article/BreadcrumbList/Organization/WebSite/WebPage) + 首页/栏目页/详情页注入
- **🆕 OG/Twitter标签增强(V2.9.15)** - og:locale联动翻译语言切换 + Twitter Card + 双皮肤同步
- **🆕 Provider真实轮询(V2.9.15)** - ImageProviderInterface扩展queryTaskStatus + 三Provider实现 + 故障降级
- **🆕 命名规范+编码根治(V2.9.15)** - `article`残留全站清除(20+文件) + `\r\n`乱码三层防御(Controller/Service/JS) + 配置键名统一
- **🆕 配图生成异步化(V2.9.14)** - 同步循环→异步队列提交+前端轮询逐张显示，解决超时断连
- **🆕 批量SEO真进度SSE(V2.9.14)** - SSE服务端主动推送真实进度+暂停/继续+刷新后状态恢复
- **🆕 AI模态框组件化(V2.9.14)** - 3个弹窗抽离为shared子模板，CSS独立为ai_editor.css
- **🆕 AI编辑器增强(V2.9.13)** - 配图候选选择(轮询/确认/重新生成)+SEO对比弹窗(单字段应用)+TinyMCE工具栏AI按钮
- **🆕 运营分析看板(V2.9.13)** - PV/UV趋势+内容分类分布+热门Top10+ECharts图表+快捷日期切换+骨架屏+数字动画
- **🆕 版本差异对比(V2.9.13)** - 文件大小变化(size_diff)+审核通过通知推送
- **🆕 模板商店生态(V2.9.12)** - 模板商店(浏览/安装/切换/支付/评分/评论)+iframe预览+网站主市场(上传/审核/定价)
- **🆕 AI模板质量管线(V2.9.12)** - 质量校验(CSS完整性/响应式/HTML标签)+修复管线(3次循环)+AI多配色变体(5种配色)
- **🆕 模板自定义(V2.9.12)** - 样式定制(7色CSS变量/5字体/Logo)+布局配置(8板块拖拽排序)+备份还原+恢复默认
- **🆕 AI内容增强(V2.9.12)** - 配图(通义万相/Flux/DALL-E三Provider+故障降级)+SEO优化(单条+批量+对比)+6种写作风格
- **🆕 开发者工具(V2.9.12)** - ZIP打包导出(.tpkg标准)+上传导入(安全校验)+审核流程+CLI命令+版本管理
- **🆕 双模式AI主题生成(V2.9.11)** - 从零生成(65分)+基于骨架生成(70分)，2种布局变体(展示型/内容型各PC+Mobile)
- **🆕 CSS变量体系统一(V2.9.11)** - 三套命名体系→25个无前缀变量，行业调色板体系(10行业色板)
- **🆕 前台用户中心增强(V2.9.10)** - 统一入口+侧边栏导航分组+积分商城条件显示+导航栏登录下拉改造
- **🆕 缓存清除细分(V2.9.10)** - 5项精准清理(全部/内容/模板/插件/浏览器)+菜单驱动化
- **🆕 AI内容模板引擎(V2.9.9)** - 自然语言生成模板Schema+AI字段自动生成+内容编辑模板选择+导入导出
- **🆕 社交分享+追踪(V2.9.9)** - 轻量版分享(微博/QQ/微信复制/链接复制)+UTM追踪+分享看板(ECharts)+热门内容TOP10
- **🆕 AI多语言国际化(V2.9.9)** - AI批量翻译+多语言路由(/en/about)+hreflang标签+4套模板语言切换+Cookie持久化
- **🆕 SEO深度升级(V2.9.9)** - 图片/视频/新闻Sitemap全类型+死链检测CLI+Dashboard死链统计卡
- **🆕 插件市场完善(V2.9.9)** - 完整生命周期+钩子/事件系统+5个预置插件包(SEO/社交/会员/导出/自定义字段)
- **🆕 审批工作流增强(V2.9.9)** - 审批历史时间线+待办角标+驳回引导+步骤指示器+自动提交审批
- **🆕 AI-GEO深度优化(V2.9.9)** - 4维度AI友好度评分(段落/事实/权威/实体)+实体提取+AI搜索Sitemap+后台评分卡
- **🆕 会员等级深化(V2.9.9)** - 权益配置中心(daily_ai_quota+exclusive_content_ids)+内容等级限制+付费阅读权限控制
- **🆕 消息通知前台(V2.9.9)** - 通知铃铛+未读角标+消息中心(私信+系统通知)+12套前台消息模板
- **🆕 全文搜索增强(V2.9.9)** - Meilisearch高亮+搜索联想+分页适配+4套搜索模板修复
- **🆕 字体本地化(V2.9.8)** - 5组中文字体子集化woff2嵌入本地+font-display:swap，离线/内网可用
- **🆕 撤销/重做(V2.9.8)** - UndoManager 30步栈+Ctrl+Z/Ctrl+Shift+Z+保存点+未保存提示+预览联动
- **🆕 AfterGenerate钩子(V2.9.8)** - AI生成后自动同步CSS资产+class名推导CSS骨架+透明PNG占位图
- **🆕 自动重试增强(V2.9.8)** - 3次指数退避+动态temperature+5类错误分类+每日20次上限
- **🆕 CSS质量评分(V2.9.8)** - 7→9维度加权评分+60/65分双线制+低质量进人工审核队列+Prompt质量约束
- **🆕 预设快捷应用(V2.9.8)** - 5→12套系统配色预设+智能推荐+theme.json presets+一键应用+撤销联动+恢复默认
- **🆕 导出预览(V2.9.8)** - 导出前确认弹框+修改字段摘要(颜色/字体/布局/Logo)+导出后3按钮快捷操作
- **🆕 Pickr深色模式(V2.9.8)** - @media(prefers-color-scheme:dark)+[data-theme="dark"]双重覆盖
- **🆕 Prompt行业风格(V2.9.8第二轮)** - 4行业配置config/theme_styles.php+行业Prompt构造+CSS组件提示
- **🆕 CSS组件模式库(V2.9.8第二轮)** - CssComponentLibrary 10组件模式(vars/hero/card/button/nav/grid/spacing/price/article/footer)
- **🆕 新手引导+折叠面板(V2.9.8第二轮)** - OnboardingGuide 3步引导+CollapsibleSection折叠+实时预览反馈
- **🆕 模板变量扩展(V2.9.8第二轮)** - 5→10页面覆盖(标签/栏目/404/关于+contact字段)+自定义404路由
- **🆕 撤销栈超时(V2.9.8第二轮)** - 15分钟未操作visibilitychange自动清空
- **🆕 分析页时间筛选(V2.9.8第二轮)** - installTrendRange(7d/30d/90d/自定义)
- **🆕 AI配图增强(V2.9.9)** - 批量配图+自动插入段落+发布自动配图+日配额控制，前端串行进度条
- **🆕 AI SEO评分(V2.9.9)** - 纯算法0-100评分(5维度加权)，前后对比面板，批量SEO优化(3篇并发控制)，自动缓存回写
- **🆕 质量检测卡片(V2.9.9)** - 维度评分条+改进建议一键执行+一键优化全部
- **🆕 社交分享增强(V2.9.9)** - 后台分享Modal(微博/QQ/复制/卡片预览)+前台分享统计埋点
- **🆕 5种写作风格(V2.9.9)** - 可配置写作风格(正式/轻松/专业/幽默/简洁)，config驱动
- **🆕 来源分析饼图(V2.9.9)** - Dashboard ECharts来源分析(直接/搜索/社交/外部)，7/30天切换
- **🆕 模板批量生产(V2.9.9)** - 5行业批量生成+质量自检6维度+人工审核闭环+CLI断点续传
- **🆕 模板市场前台(V2.9.9)** - 10套预埋模板+RemoteTemplateSource(OSS/CDN/缓存/降级)+骨架屏+分类Tab+搜索排序+一键安装+回滚
- **🆕 模板管理增强(V2.9.9)** - 模板评分收藏+版本检测红点+详情弹窗(文件/大小/版本)+分类CRUD+操作日志

- **🆕 AI模板对话迭代** - 多轮对话式修改+局部重生成+版本管理(git备份/回退/差异对比)，AI助手面板
- **🆕 暗色模式全站** - 43+模板硬编码颜色→CSS变量替换，shared片段改造，scan-hardcoded-colors扫描脚本
- **🆕 13个UI组件** - I8JComponent基类+注册表+13组件(Toast/Modal/Pagination/SearchBar/ImageUpload/Tabs/Dropdown/DatePicker/Progress/Badge/Skeleton/Breadcrumb/DataTable)，ESBuild多bundle打包
- **🆕 主题生态** - ZIP导入导出+主题管理页面+21个AI主题路由+本地主题导出
- **🆕 测试基础设施** - PHPUnit单元测试+Playwright E2E框架+组件测试
- **AI主题生成** - 文字描述→LLM生成→自动校验→预览→审核发布，7状态异步任务管理
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

### V2.9.10~V2.9.15 新增特性

> 详见上方「核心特性」列表。V2.9.10 起经历 6 个大版本迭代：V2.9.10(菜单驱动化+缓存细分+用户中心)、V2.9.11(双模式AI主题+CSS变量统一+行业调色板)、V2.9.12(模板商店+AI质量管线+模板自定义+配图增强+开发工具)、V2.9.13(AI编辑器增强+运营看板+版本差异对比)、V2.9.14(配图异步化+批量SEO SSE+模态框组件化)、V2.9.15(AI翻译引擎+Schema标记+OG增强+Provider轮询+命名规范根治)。以下为更早版本的核心功能概述：

- **AI多语言国际化(P0, V2.9.9)** - AI批量翻译+多语言路由(/en/about)+hreflang标签+4套模板语言切换+Cookie持久化，翻译内容为独立Content记录
- **SEO深度升级(P0, V2.9.9)** - 图片/视频/新闻Sitemap全类型生成+死链检测CLI+Dashboard死链统计卡，SeoService统一管理
- **插件市场完善(P0, V2.9.9)** - 完整生命周期+钩子/事件系统(on/fire/getRegisteredHooks)+5个预置插件包(SEO/社交/会员/导出/自定义字段)
- **审批工作流增强(P1, V2.9.9)** - 审批历史时间线+待办角标+驳回引导+步骤指示器+自动提交审批，ContentService审批自动触发
- **AI-GEO深度优化(P1, V2.9.9)** - 4维度AI友好度评分(段落/事实/权威/实体)+实体提取+AI搜索Sitemap+后台评分卡
- **会员等级深化(P1, V2.9.9)** - 权益配置中心(daily_ai_quota+exclusive_content_ids)+内容等级限制+付费阅读权限控制，MemberController权益查询
- **消息通知前台(P1, V2.9.9)** - 通知铃铛+未读角标+消息中心(私信+系统通知)+12套前台消息模板
- **全文搜索增强(P1, V2.9.9)** - Meilisearch高亮+搜索联想+分页适配+4套搜索模板修复

**V2.9.8**

- **字体本地化(V2.9.8第一轮)** - 5组中文字体子集化woff2嵌入本地+font-display:swap，离线/内网可用，移除Google Fonts外部依赖
- **撤销/重做(V2.9.8第一轮)** - UndoManager 30步栈+Ctrl+Z/Ctrl+Shift+Z+保存点+未保存提示+预览联动
- **AfterGenerate钩子(V2.9.8第一轮)** - AI生成后自动同步CSS资产+class名推导CSS骨架+透明PNG占位图，异步任务自动触发
- **自动重试增强(V2.9.8第一轮)** - 3次指数退避+动态temperature+5类错误分类+每日20次上限
- **CSS质量评分(V2.9.8第一轮)** - 7→9维度加权评分+60/65分双线制+低质量进人工审核队列+Prompt质量约束
- **预设快捷应用(V2.9.8第一轮)** - 5→12套系统配色预设+智能推荐+theme.json presets+一键应用+撤销联动+恢复默认
- **导出预览(V2.9.8第一轮)** - 导出前确认弹框+修改字段摘要(颜色/字体/布局/Logo)+导出后3按钮快捷操作
- **Pickr深色模式(V2.9.8第一轮)** - @media(prefers-color-scheme:dark)+[data-theme="dark"]双重覆盖
- **postMessage文档(V2.9.8第一轮)** - iframe实时预览双工通信规范+跨域安全策略
- **Prompt行业风格(V2.9.8第二轮)** - 4行业配置config/theme_styles.php+行业Prompt构造+CSS组件提示
- **CSS组件模式库(V2.9.8第二轮)** - CssComponentLibrary 10组件模式(vars/hero/card/button/nav/grid/spacing/price/article/footer)
- **新手引导+折叠面板(V2.9.8第二轮)** - OnboardingGuide 3步引导+CollapsibleSection折叠+实时预览反馈
- **模板变量扩展(V2.9.8第二轮)** - 5→10页面覆盖(标签/栏目/404/关于+contact字段)+自定义404路由
- **撤销栈超时(V2.9.8第二轮)** - 15分钟未操作visibilitychange自动清空
- **分析页时间筛选(V2.9.8第二轮)** - installTrendRange(7d/30d/90d/自定义)+Anomaly Detection

**V2.9.7**

- **AI主题6缺陷根治(含V2.9.6合并)** - Prompt完善(行业上下文+CSS约束+结构规范)+验证器增强(语法+完整性+语义3层)+搜索高亮匹配
- **模板可视化定制** - 19个CSS变量(颜色/字体/间距/圆角)+Pickr颜色选择器+6组字体预设+布局选项(宽/窄/全宽)+Logo上传+iframe实时预览(postMessage双工通信)+变体管理
- **导出导入含定制数据** - 导出ZIP包含定制配置(theme.json+css_variants.json)+导入自动应用定制+冲突检测修复
- **数据分析(ECharts)** - 安装排行(柱状图)+安装趋势(折线图/GEOMap)+定制偏好(饼图)+评分分布(柱状图)+CSV导出
- **恢复默认API** - 一键重置定制项到系统预设+确认弹窗防护
- **安装引导** - 首次安装3步引导提示+折叠面板收起/展开

**V2.9.5**

- **XSS输出过滤(XssEscapeMiddleware, P0)** - CSP安全响应头(CSP-Report-Only/X-Content-Type-Options/X-Frame-Options/Referrer-Policy)+XSS载荷日志
- **前台CSRF保护(FrontCsrfMiddleware, P0)** - 前台POST/PUT/DELETE Token验证+419友好错误页+front-csrf.js自动注入(4套模板)
- **SQL注入审计(P0)** - SigninService Db::raw→inc()重构+ImageTask Db::raw→whereColumn+慢查询建议索引
- **上传安全(UploadSecurityService, P0)** - MIME魔数校验+扩展名黑白名单+UUIDv4安全文件名
- **缓存预热(CacheWarmService, P0)** - warmContentCache/warmConfigCache/warmMemberCache/warmAllCache
- **Vary优化(P0)** - FrontBaseController缓存命中快路径补Vary:Cookie+Cache-Control
- **N+1查询修复(P0)** - ContentController detail相关文章+API列表+with(['cate'])懒加载
- **JSON输出加固(P0)** - success/error输出JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE
- **付费阅读桥接(P1)** - PaidService::createOrder()桥接PaymentService::createPayment()+回调通知半自动完成+4套前台付费墙UI
- **前台Toast组件(P1)** - public/assets/js/front-toast.js零依赖Toaster(success/error/warning/info)
- **前台通用组件(P1)** - public/assets/js/front-components.js空状态/骨架屏/404统一组件
- **等级时间线(P1)** - 4套前台member_level.html升级/降级/宽限期事件+方向图标
- **消息分类(P1)** - 4套前台member_notification.html分类筛选(全部/系统/等级/评论)+未读徽标
- **内容审批(P1)** - ContentController audit/reject单条+ContentService batchOperate(audit/reject)+批量栏替换

**V2.9.4**

- **发布状态看板(M28续)** - 发布记录列表+按平台/状态筛选+手动重试+发布摘要统计(成功率/各平台统计)
- **插件评分评价(M25续)** - 已安装插件1-5星评分+评语+平均分缓存展示(5分钟TTL)
- **AI内容质量检测(M30)** - 可读性评分(中文统计模型)+SEO友好度(6维度)+敏感词过滤(Trie+内置词库)+质量评分面板
- **AI写作风格选择(M30续)** - 6种风格Prompt(默认/正式/轻松/专业/亲切/营销)+风格选择UI+栏目级预设
- **支付集成框架(M31)** - PaymentService统一支付层+微信/支付宝适配器(沙箱模式)+订单管理+回调处理+支付配置页
- **许可证管理框架(M32)** - licenses表+本地/远程双验证+离线降级24h+后台发放/激活/吊销+插件license_check钩子
- **付费阅读/打赏(M33)** - 文章编辑页付费开关+价格设置+未付费用户看摘要+打赏按钮
- **备份增强补全(M26续)** - 备份目录可配置化(template+config)+备份日志(BackupLog)+已有下载功能
- **会员降级日志(M20续)** - MemberDowngradeLog记录降级操作+通知状态
- **Bug修复与体验优化** - GLOB_BRACE Alpine兼容+nginx /admin重写修复+会员头像上传(后台+前台)+图标选择器+下拉溢出修复+默认头像+PWA提示7天冷却+logo尺寸统一+登录页动态logo

**V2.9.3**

- **V2.9.3功能** - 数据备份恢复增强(M26)+多渠道分发增强(M28)+插件商店首页升级(M25)+会员权益补全(M20)
- **发布状态看板(M28续, P0)** - 发布记录列表+按平台/状态筛选+手动重试+发布摘要统计(成功率/各平台统计)
- **插件评分评价(M25续, P0)** - 已安装插件1-5星评分+评语+平均分缓存展示(5分钟TTL)
- **AI内容质量检测(M30, P0)** - 可读性评分(中文统计模型)+SEO友好度(6维度)+敏感词过滤(Trie+内置词库)+质量评分面板(AJAX+1秒防抖)
- **AI写作风格选择(M30续, P0)** - 6种风格Prompt(默认/正式/轻松/专业/亲切/营销)+风格选择UI+栏目级预设(default_style字段)
- **支付集成框架(M31, P1)** - PaymentService统一支付层+微信/支付宝适配器(沙箱模式)+订单管理+回调处理+支付配置页
- **许可证管理框架(M32, P1)** - licenses表+本地/远程双验证+离线降级24h+后台发放/激活/吊销+插件license_check钩子
- **付费阅读/打赏(M33, P1)** - 文章编辑页付费开关+价格设置+未付费用户看摘要+打赏按钮
- **备份增强补全(M26续, P1)** - 备份目录可配置化(template+config)+备份日志(BackupLog)+已有下载功能
- **会员降级日志(M20续, P1)** - MemberDowngradeLog记录降级操作+通知状态
- **Bug修复与体验优化** - GLOB_BRACE Alpine兼容+nginx /admin重写修复+会员头像上传(后台+前台)+图标选择器+下拉溢出修复+默认头像+PWA提示7天冷却+logo尺寸统一+登录页动态logo

**V2.9.2**

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

**V2.9.1**

- **FluxProvider异步化(M14a)** - DB任务表+CLI轮询替代sleep(2)阻塞，前端AJAX进度轮询(4种状态+进度%)，30次/90秒超时+3次错误重试
- **图片懒加载系统化(M14b)** - IntersectionObserver统一组件+4套layout引入+8+模板img→data-src替换+富文本自动拦截
- **CDN URL替换系统化(M14c)** - 模板层精准替换(cdn_enabled/cdn_domain变量)+响应层str_replace兜底(仅src/href)+后台开关可控
- **CSS变量化深度改造(M14d)** - 4套style.css硬编码→完整CSS变量体系(default+corporate各pc/mobile)，独立设计令牌
- **AI数据分析报告(M9)** - Model层数据采集+AI分析引擎(日报/周报/月报)+异常检测+关键发现+建议+邮件推送+Markdown导出
- **API文档自动生成(M10)** - PHP Reflection+DocBlock解析+路由匹配，后台分组展示+Swagger风格+Markdown导出
- **评价媒体上传前端(M15a)** - 多图预览/进度/拖拽/删除组件，复用/api/upload/image接口，4套detail.html集成
- **评价回复独立表(M15b)** - 管理员回复+会员追问，前台回复展示+后台回复管理
- **免邮券对接物流模块(M16a)** - ShippingService运费计算+免邮阈值+免邮券识别+CouponTemplate.free_shipping支持
- **aiSuggest接入AI大模型(M16b)** - AiService.colorSuggest()AI配色+行业/风格偏好选择器+HSL降级预设
- **小程序样式补全(M16c)** - app.wxss完整设计体系(487行/10大模块:变量/布局/间距/卡片/按钮/表单/列表/图文/状态/工具类)
- **AI配图URL本地化(M17)** - ImagePollCommand下载远程配图到本地StorageService+Content.cover自动回写
- **批量内容管理增强(M18)** - 后台全选/多选+批量审核/删除/移动分类/推荐/取消推荐，确认弹窗防误操作
- **V2.9.1收尾** - menu.php新增report/apidoc菜单、permission.php新增4组权限、v2.9.1.sql(3表+CDN配置+免邮配置+语言切换器)

**V2.9**

- **AI模板高度定制化** - 5大Tab页(基本信息/生成规则/字段映射/配图发布/参考示例)，自定义字段动态增删，6种转换规则，质量检测配置(评分阈值/自动重试/低质处理)
- **AI模板表单联动** - 生成模式(NLP/参考示例)切换联动，字段映射动态交互，结构化JSON采集(fields_json/field_mapping_json/quality_config_json)
- **FLUX/DALL-E Provider** - ImageProviderFactory工厂+3Provider(通义万相/FLUX.1/DALL-E)，Provider自动降级，5种FLUX风格+3种DALL-E尺寸
- **小程序100%完善** - 11个页面(index/detail/search/login/mine/category/payment/signin/coupon/invite/order)，优惠券双Tab+邀请三阶段奖励+7日签到里程碑+订单管理
- **全阶段邀请奖励** - InviteRewardService三阶段(register→signin→pay)，事件驱动挂接，邀请排行+明细+邀请码唯一索引
- **优惠券系统** - CouponService完整CRUD，3种券类型(满减/折扣/免邮)，库存扣减+每人限领+唯一券码，新人券自动发放，5种状态流转
- **评价评分系统** - RatingService评分+文字评价+匿名+审核，Redis防重复点赞，PC/Mobile/小程序三端评价区块，星级分布统计
- **前台模板可视化预研** - TemplateDesignController+后台CSS变量编辑器，HSL色彩推导AI配色，:root CSS变量动态注入(4套layout.html)，模板配置持久化
- **多语言翻译完善** - AI批量翻译(AiService::translateBatch)，前台4套模板语言切换器，LanguageController字段名统一(is_enabled)
- **V2.9收尾** - menu.php+permission.php+module注册3项同步更新，v2.9.sql完整迁移(5张新表+字段补全+配置项)

**V2.8**

- **AI配图生成** - 通义万相Provider+ImageProviderFactory工厂模式，编辑器一键AI生成封面图
- **AI内容质量检测** - 5维度评分(可读性/SEO/原创性/结构/吸引力)+改进建议，编辑器实时提示
- **AI SEO优化助手** - 一键优化SEO标题/关键词/描述，搜索引擎结果预览卡片
- **运营数据报表中心** - Dashboard ECharts可视化图表，内容/PV/收入/趋势多维分析
- **流量分析看板** - 来源分析/设备分布/24小时时段/受访页面排行，ECharts交互图表
- **AI生成统计** - 生成趋势/供应商消耗占比/任务类型/质量分布，量化AI投入产出
- **VIP免费阅读范围** - 后台配置VIP会员免费阅读模式(0=不免费/1=全部免费)
- **社交分享** - 微信/微博/QQ分享按钮+OGP Meta+分享统计埋点
- **邀请返积分** - 邀请码+注册奖励+IP防刷(限3次)+邀请排行/明细，会员增长闭环
- **权限配置完善** - 新增traffic/ai_stat/invite三组权限映射，非超管角色可正常访问

**V2.7**

- **API安全加固** - ApiMemberAuth中间件注入会员ID，PaidContentGuard二级防护，杜绝付费内容绕过
- **VIP权益规范化** - is_vip字段统一标记，登录时实时过期检查，VipExpireCommand定时降级
- **付费章节体系** - UserChapter模型+章节管理UI+阅读页+试读截断，支持按章节单独售卖
- **积分签到生态** - 每日签到+连续签到奖励+消费返积分，前台签到页/积分记录页
- **积分商城前端** - PointsProductController+兑换弹窗+兑换记录+发货管理
- **头条号OAuth** - OAuth 2.0授权+Token自动刷新，发布时无感续期
- **PV统计重构** - JS异步打点+VisitService+蜘蛛过滤，不影响页面渲染
- **验证码增强** - GD库生成(干扰线/噪点/扭曲)，支持切换腾讯验证码
- **邮件队列持久化** - DB/Cache双写+EmailQueueRecoverCommand
- **表单可视化编辑器** - 12种字段类型+4预设模板+拖拽排序+实时预览
- **搜索增强** - Meilisearch集成+联想补全+热门搜索
- **CDN集成** - StorageService::getCdnUrl() + 后台配置开关
- **双栏菜单(corporate)** - L1图标55px+L2面板200px，hover/click交互
- **AI模板参考示例** - generate_mode=example，参考示例Prompt构建

**V2.6**

- **CSS静态资源分离** - 后台/前台/登录页内联CSS提取为外部文件，`public/skin/` 目录管理
- **PJAX核心修复** - 51个模板`<script>`从content block迁移至js block，解决PJAX切换脚本丢失
- **数据导入修复** - ImportController分类查询+ImportService CSV导入+权限映射修正
- **模板变量注入** - `$skin_admin`(后台) / `$skin`(前台) 自动注入CSS路径变量
- **Nginx配置更新** - deploy/nginx + docker/nginx 添加 `/skin/` 路径支持
- **调试文件清理** - 移除调试临时文件，.gitignore增强忽略规则

**V2.5**

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

**更早版本**

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
| 语言 | PHP 8.1+ | 严格类型声明 |
| 数据库 | MySQL 8.0 | 40+张数据表 |
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
│       ├── command/            #   CLI命令(ThemeGenerate/ImagePoll/VipExpire等)
│       ├── controller/         #   基类控制器(AdminBase/FrontBase)
│       ├── middleware/         #   中间件(ThemePreview/FrontCsrf等)
│       ├── model/              #   数据模型(AiThemeRecord/ImageTask等)
│       ├── service/            #   业务服务(AI/支付/采集/发布/邮件/插件/主题等)
│       │   └── theme/          #     主题服务(V2.9.6-7 + V2.9.8)
│       │       ├── AiThemeGenerateService.php  # AI主题生成+V2.9.8 AfterGenerate钩子+重试增强+Prompt质量
│       │       ├── IncrementalContextBuilder.php # 对话上下文管理(Phase 3)
│       │       ├── ThemeVersionManager.php     # 版本管理-git备份/回退(Phase 3)
│       │       ├── ThemePackageService.php     # 主题ZIP导入导出+V2.9.8冲突检测修复
│       │       ├── ThemeValidatorService.php   # 校验流水线+V2.9.8 CSS质量7维度评分器
│       │       ├── ThemeCustomService.php      # V2.9.8 预设读取getAvailablePresets()
│       │       ├── ThemePromptBuilder.php      # V2.9.11 双模式Prompt构建器(full/skeleton)
│       │       └── ThemeFileService.php        # 文件落盘服务
│       ├── taglib/             #   模板标签引擎(I8j)
│       ├── traits/             #   特性(CircuitBreakerTrait/RedisQueueTrait)
│       └── helper.php          #   全局助手函数
├── config/                     # 框架全局配置
│   ├── app.php                 #   应用配置
│   ├── csp.php                 #   CSP策略配置(V2.9.0新增)
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
│   ├── upload_security.php     #   上传安全配置
│   └── info_type_fields.php    #   扩展字段定义
├── template/                   # 模板目录
│   ├── admin/                  #   后台模板
│   │   ├── default/            #     经典主题
│   │   └── corporate/          #     企业主题
│   └── themes/                 #   前台主题
│       ├── default/            #     默认主题(pc/mobile)
│       ├── corporate/          #     企业主题(pc/mobile)
│       ├── ai-base-showcase/   #     V2.9.11 展示型骨架(Hero+轮播+三列卡片)
│       ├── ai-base-content/    #     V2.9.11 内容型骨架(紧凑+侧栏+阅读优化)
│       └── shared/             #     共享模板片段(V2.9.0新增)
│           ├── paid_badge.html #       付费标识
│           ├── reward_button.html #    打赏按钮
│           ├── empty_state.html #      空状态
│           └── loading_spinner.html #  加载动画
├── public/                     # Web根目录
│   ├── index.php               #   前台入口
│   ├── admin.php               #   后台入口
│   ├── install.php             #   安装入口
│   ├── assets/                 #   公共静态资源(Bootstrap/jQuery/TinyMCE)
│   │   ├── components/         #     UI组件库(V2.9.0: core/base/form/data/nav + 13组件)
│   │   │   ├── bundle/         #       ESBuild多bundle打包(core/data/form/full)
│   │   │   ├── base/           #       基础组件(Toast/Modal/Pagination/Tabs/Dropdown/Progress/Badge)
│   │   │   ├── form/           #       表单组件(SearchBar/ImageUpload/DatePicker)
│   │   │   ├── data/           #       数据组件(DataTable/Skeleton)
│   │   │   └── nav/            #       导航组件(Breadcrumb)
│   │   ├── css/                #     全局CSS(theme-variables.css+theme-fonts.css+theme-customizer.css)
│   │   ├── fonts/              #     V2.9.8 本地字体woff2(Noto Sans/Serif/LXGW/Ma Shan/Inter)
│   │   └── js/                 #     前台组件+定制器(theme-customizer.js含UndoManager+预设+导出预览)
│   │   └── js/                 #     前台组件+定制器(theme-customizer.js含UndoManager+预设+导出预览)
│   ├── skin/                   #   主题静态资源(V2.6新增)
│   │   ├── admin/              #     后台CSS/JS/图片/字体
│   │   └── themes/             #     前台主题CSS/JS/图片/字体
│   └── uploads/                #   上传目录
├── database/                   # 数据库SQL
│   ├── v2.4.sql ~ v2.9.5.sql   #   历史增量更新
│   ├── v3.0.sql                #   V2.9.0幂等升级脚本
│   ├── v3.0-phase2.sql         #   V2.9.6幂等升级脚本
│   ├── v3.0-phase3.sql         #   V2.9.7幂等升级脚本
│   └── v3.1.sql                #   V2.9.9幂等升级脚本(seo_score+配额+风格配置)
├── miniprogram/                # 微信小程序
│   ├── pages/                  #   页面
│   └── utils/                  #   工具(API封装)
├── tests/                      # 测试目录(V2.9.7新增)
│   ├── Unit/Service/           #   PHPUnit单元测试
│   │   ├── ThemeValidatorServiceTest.php
│   │   ├── AiThemeRecordTest.php
│   │   └── fixtures/           #     测试用模板文件
│   └── E2E/                    #   Playwright E2E测试
│       ├── playwright.config.js
│       └── component-toast.spec.js
├── plugin/                     # 插件目录
├── docker/                     # Docker配置
│   ├── php/Dockerfile          #   PHP-FPM镜像
│   ├── nginx/                  #   Nginx配置
│   └── mysql/                  #   MySQL配置
├── deploy/                     # 生产部署配置
│   └── nginx/aicms.conf        #   Nginx生产配置模板
├── .env.example                #   环境变量模板
├── .gitignore                  #   Git忽略规则
├── composer.json               #   Composer依赖
├── docker-compose.yml          #   Docker Compose编排
├── install.sh                  #   Linux/macOS安装脚本
├── install.bat                 #   Windows安装脚本
├── LICENSE                     #   Apache 2.0 许可证
└── README.md                   #   项目说明
```

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
| POST | /api/ai/batch_image | AI批量配图（V2.9.9，Session认证） |
| POST | /api/ai/seo_score | SEO评分纯算法（V2.9.9，Session认证） |
| GET | /api/ai/styles | 获取写作风格列表（V2.9.9） |
| POST | /api/ai/share | 社交分享链接生成（V2.9.9，Session认证） |
| POST | /api/upload/image | 图片上传 |
| POST | /api/cache/clear | 清除缓存（超管专用） |
| GET | /api/csrf/token | 获取CSRF Token（AJAX恢复） |
| GET | /api/content/list | 内容列表 |
| GET | /api/content/detail | 内容详情 |
| POST | /api/member/login | 会员登录 |
| POST | /api/member/register | 会员注册 |
| POST | /api/v1/visit | PV打点统计 |
| POST | /api/share/track | 分享追踪上报(V2.9.9) |
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
| V2.9.17 | 2026-06-04 | **翻译体验精修·多语言管理闭环** — 2个Sprint 4项功能：M-2后台语言管理UI(checkbox+排序+自定义语言+settings持久化) + T-4轮询可配置化(config polling段+Controller注入+JS改造+动态加速) + M-6前台语言切换器(_lang_switcher双皮肤+国旗/本地名/RTL CSS) + E-2翻译SSE实时推送(stream端点+SSE优先+轮询降级+30s超时)。0张新表，16文件+913行。 | |
| V2.9.16 | 2026-06-03 | **翻译引擎增强·SEO诊断** — 4个Sprint 12项功能点：S1 OpenAI翻译完整实现(GPT-4o/重试/分段/16语言)+DeepSeek增强+Cache限速+插件化注册+统一语言配置；S2 config/ai.php多Provider/多账号扩展；S3 SEO诊断引擎(4维度20+检测项/A-F评级/Chart.js环形图/双皮肤)；S4 前端语言扩展+队列翻译增强+配图链式降级(tongyi→flux→dalle)+默认社交分享图。 |
| V2.9.15 | 2026-06-01 | **质量增强·AI能力升级** — 3方向9功能点+11项P1修正：J技术负债清理(Provider真实轮询+@deprecated+Nginx SSE+composer自动化)+K AI翻译引擎(DeepSeek翻译+HTML分段+缓存+三端UI+批量队列)+L SEO增强(Schema.org 5类型+OG/Twitter增强+og:locale联动)。同日追加质量修复：命名规范化(20+文件)+`\r\n`乱码三层防御+模板路径修正。 |
| V2.9.14 | 2026-05-30 | **体验精修·异步升级** — 3方向10项优化：P0配图异步化(同步循环→异步队列+轮询+超时解决)+P0批量SEO真进度SSE(真实进度+暂停继续+状态恢复)+P0 AI模态框组件化(3弹窗抽离shared子模板+CSS独立)。 |
| V2.9.13 | 2026-05-28 | **内容智能化·运营增强** — 4方向18项：AI编辑器增强(配图候选选择+SEO对比弹窗+写作风格选择器+批量进度条+TinyMCE工具栏)、运营看板(PV/UV趋势+分类分布+热门Top10+ECharts)、版本差异对比(size_diff)+审核通知。 |
| V2.9.12 | 2026-05-31 | **模板生态·内容智能化** — 5方向19项功能100%完成：A模板商店(10表+浏览安装切换支付评分预览)+B AI质量管线(校验+修复+配色变体)+C模板自定义(7色/5字体/布局8板块/备份还原)+D AI内容增强(配图3Provider/SEO批量优化/6种写作风格)+E开发者工具(ZIP打包/审核流程/CLI命令/版本管理)。12个Service+5个Controller+10个Model+16个双皮肤模板+2个Config+1个Command+1个Middleware+1个Listener。 |
| V2.9.11 | 2026-05-23 | **主题进化** — 15任务(14完成)：双模式AI主题生成(full骨架生成HTML+CSS 65分阈值/skeleton骨架AI只生成CSS 70分阈值)+2种布局骨架(展示型ai-base-showcase/内容型ai-base-content各PC8+Mobile4文件)+CSS变量三套命名体系修复(--i8j-前缀断裂Bug→25个无前缀统一变量)+行业调色板体系(10行业palette表/Sabberworm CSS解析器)+theme:clean/duplicate CLI命令+后台generate/tweak页面双皮肤改造+3个新增PHP文件(ThemePromptBuilder/ThemeCleanCommand/ThemeDuplicateCommand)+10个修改文件。11套AI废主题已清理。 |
| V2.9.10 | 2026-05-22 | **体验进化** — 3项核心优化：前台用户中心增强（统一入口+301重定向+ucenter.js独立脚本+4套模板条件注入）+缓存清除细分（5项下拉+CacheController按类型清理）+后台菜单数据库化（i8j_menu_group/item双表+MenuBridge回退+双皮肤管理界面+SortableJS跨组拖拽+本地化CSP兼容+syncFromConfig事务保护）。AI配置中心（3Tab：模型/配图/写作）+tabs.js通用组件。17个修改文件+13个新增文件，7项关键缺陷全部修复。 |
| V2.9.9 | 2026-05-18 | **从好用到聪明** — P0x5(AI模板引擎+社交分享+多语言+SEO深度+插件市场)+P1x5(审批工作流+AI-GEO+会员等级+消息通知+搜索增强)=10模块全面升级。24新增文件+40+修改文件。AI内容模板自然语言生成+社交分享追踪看板+AI批量翻译多语言路由+图片视频新闻Sitemap+插件钩子事件系统+审批时间线角标+AI友好度4维评分+权益配置中心+通知铃铛消息中心+Meilisearch高亮联想 |
| V2.9.8 | 2026-05-15 | **模力精修** — 两轮交付：第一轮(12/12): 字体本地化(5组woff2)/撤销重做(UndoManager 30步栈)/AfterGenerate钩子/自动重试增强(3次退避+动态temperature)/CSS质量7维度评分器(60分及格)/CSS质量Prompt增强/预设快捷应用(5套)/导出预览/Pickr深色模式/postMessage文档。第二轮(9+6项): Prompt行业风格加强(4行业配置)/CSS组件模式库(CssComponentLibrary 10组件)/评分器9维度+双线制(新65/历史60)/预设5→12套+智能推荐/3步新手引导+折叠面板/导出后3按钮快捷操作/模板变量5→10页面/撤销栈15分钟清空/恢复默认API+安装引导+分析页时间筛选 |
| V2.9.7 | 2026-05-15 | **模力定制**: AI主题6缺陷根治(Prompt完善+验证器+搜索高亮)/模板可视化定制(19个CSS变量+Pickr颜色选择器+6组字体预设+布局选项+Logo上传+iframe实时预览+postMessage+变体管理)/导出导入含定制数据/数据分析(安装排行+趋势+定制偏好+评分分布+ECharts+CSV导出) |
| V2.9.5 | 2026-05 | 安全加固(XSS输出过滤+前台CSRF+SQL审计+上传MIME校验)/性能优化(Vary头+缓存预热+N+1修复+JSON加固)/付费阅读桥接(PaidService↔PaymentService双订单)/UI一致性(Toast+空状态+时间线+消息分类)/内容审批(单条+批量) |
| V2.9.4 | 2026-05 | 优化完善×商业化准备: 发布看板+评分评价/AI质量检测+写作风格/支付框架+许可证+付费阅读/备份日志+降级日志 |
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

MIT License — 详见 [LICENSE](LICENSE) 文件

Copyright (c) 2026 湖北八界智能技术有限公司
