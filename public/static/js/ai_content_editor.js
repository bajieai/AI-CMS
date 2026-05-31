/**
 * AI-CMS V2.9.13 AI内容编辑器增强
 * 功能：AI配图、AI SEO对比、写作风格选择
 * 挂载点：content_edit.html 中的 TinyMCE 编辑器
 */
(function () {
    'use strict';

    // ============================================================
    // 全局配置
    // ============================================================
    const CONFIG = {
        pollInterval: 3000,      // 配图轮询间隔 3s
        pollTimeout: 45000,      // 配图轮询超时 45s
        maxCandidates: 3,        // 配图候选数量
    };

    // ============================================================
    // 工具函数
    // ============================================================
    function apiPost(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
        }).then(r => r.json());
    }

    function apiGet(url) {
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then(r => r.json());
    }

    function showToast(msg, type) {
        if (window.toastr) {
            toastr[type || 'success'](msg);
        } else {
            alert(msg);
        }
    }

    // ============================================================
    // V2.9.13 F-2: AI配图
    // ============================================================
    const AiImage = {
        contentId: 0,
        pollTimer: null,
        pollStart: 0,

        init(contentId) {
            this.contentId = contentId;
        },

        /** 触发配图生成 */
        generate() {
            const modal = document.getElementById('ai-image-modal');
            if (modal) modal.classList.add('show');
            this.setStatus('正在生成配图候选，请稍候...');

            apiPost(`/admin/content/aiImageGenerate/${this.contentId}`, {})
                .then(res => {
                    if (res.success) {
                        this.renderCandidates(res.data.candidates || []);
                        // 如果有异步任务未完成，启动轮询
                        if (res.data.candidates.some(c => !c.url && c.task_id)) {
                            this.startPoll();
                        }
                    } else {
                        this.setStatus(res.msg || '生成失败', 'error');
                    }
                })
                .catch(err => this.setStatus('网络错误: ' + err.message, 'error'));
        },

        /** 重新生成 */
        regenerate() {
            this.clearCandidates();
            this.generate();
        },

        /** 轮询配图状态 */
        startPoll() {
            this.pollStart = Date.now();
            if (this.pollTimer) clearInterval(this.pollTimer);

            this.pollTimer = setInterval(() => {
                if (Date.now() - this.pollStart > CONFIG.pollTimeout) {
                    clearInterval(this.pollTimer);
                    this.setStatus('配图生成超时，请尝试重新生成', 'warning');
                    return;
                }

                apiGet(`/admin/content/aiImagePoll/${this.contentId}`)
                    .then(res => {
                        if (res.success && res.data.pending === 0) {
                            clearInterval(this.pollTimer);
                            this.renderCandidates(res.data.candidates || []);
                        }
                    })
                    .catch(() => {}); // 轮询忽略网络错误
            }, CONFIG.pollInterval);
        },

        /** 渲染候选图 */
        renderCandidates(candidates) {
            const container = document.getElementById('ai-image-candidates');
            if (!container) return;

            container.innerHTML = candidates.map((c, i) => `
                <div class="ai-image-card" data-index="${i}">
                    <img src="${c.url || '/static/images/placeholder.png'}" alt="候选图${i + 1}" onerror="this.src='/static/images/placeholder.png'">
                    <div class="ai-image-overlay">
                        <button type="button" class="btn btn-sm btn-primary" onclick="AiImage.confirm(${i})">使用此图</button>
                    </div>
                    ${!c.url ? '<div class="ai-image-loading"><span class="spinner-border spinner-border-sm"></span>生成中...</div>' : ''}
                </div>
            `).join('');

            this.setStatus(candidates.length > 0 ? '请选择一张配图' : '暂无候选图');
        },

        clearCandidates() {
            const container = document.getElementById('ai-image-candidates');
            if (container) container.innerHTML = '';
            if (this.pollTimer) clearInterval(this.pollTimer);
        },

        /** 确认配图 */
        confirm(index) {
            apiPost(`/admin/content/aiImageConfirm/${this.contentId}`, { index: index })
                .then(res => {
                    if (res.success) {
                        showToast('配图已应用到文章');
                        // 关闭弹窗
                        const modal = document.getElementById('ai-image-modal');
                        if (modal) modal.classList.remove('show');
                        // 刷新编辑器中的配图预览（如有）
                        const imgPreview = document.getElementById('feature-img-preview');
                        if (imgPreview && res.data.url) imgPreview.src = res.data.url;
                    } else {
                        showToast(res.msg || '应用失败', 'error');
                    }
                });
        },

        setStatus(msg, type) {
            const el = document.getElementById('ai-image-status');
            if (el) el.textContent = msg;
        },
    };

    // ============================================================
    // V2.9.13 F-3: AI SEO对比
    // ============================================================
    const AiSeo = {
        contentId: 0,
        diffData: null,

        init(contentId) {
            this.contentId = contentId;
        },

        /** 打开SEO对比弹窗 */
        open() {
            const modal = document.getElementById('ai-seo-modal');
            if (modal) modal.classList.add('show');
            this.setStatus('正在分析SEO优化方案...');

            apiPost(`/admin/content/aiSeoOptimize/${this.contentId}`, {})
                .then(res => {
                    if (res.success) {
                        this.diffData = res.data;
                        this.renderDiff();
                    } else {
                        this.setStatus(res.msg || '优化失败', 'error');
                    }
                })
                .catch(err => this.setStatus('网络错误: ' + err.message, 'error'));
        },

        renderDiff() {
            const container = document.getElementById('ai-seo-diff');
            if (!container || !this.diffData) return;

            const fields = [
                { key: 'seo_title', label: 'SEO标题' },
                { key: 'seo_description', label: 'SEO描述' },
                { key: 'seo_keywords', label: 'SEO关键词' },
            ];

            container.innerHTML = fields.map(f => {
                const before = this.diffData.before[f.key] || '';
                const after = this.diffData.after[f.key] || '';
                return `
                    <div class="ai-seo-field">
                        <div class="ai-seo-label">${f.label}</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="ai-seo-box ai-seo-old">
                                    <small class="text-muted">当前值</small>
                                    <div>${before || '<span class="text-muted">（空）</span>'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ai-seo-box ai-seo-new">
                                    <small class="text-muted">AI建议</small>
                                    <div>${after}</div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="AiSeo.applyField('${f.key}', \`${after.replace(/`/g, '\\`')}\`)">仅应用${f.label}</button>
                    </div>
                `;
            }).join('');

            this.setStatus('');
        },

        /** 应用单个字段 */
        applyField(field, value) {
            apiPost(`/admin/content/aiSeoApply/${this.contentId}`, { field: field, value: value })
                .then(res => {
                    if (res.success) {
                        showToast('已应用');
                        // 更新弹窗中当前值显示
                        if (this.diffData) this.diffData.before[field] = value;
                        this.renderDiff();
                    } else {
                        showToast(res.msg || '应用失败', 'error');
                    }
                });
        },

        /** 应用全部字段 */
        applyAll() {
            apiPost(`/admin/content/aiSeoApply/${this.contentId}`, { field: '', value: '' })
                .then(res => {
                    if (res.success) {
                        showToast('全部SEO优化已应用');
                        const modal = document.getElementById('ai-seo-modal');
                        if (modal) modal.classList.remove('show');
                    } else {
                        showToast(res.msg || '应用失败', 'error');
                    }
                });
        },

        setStatus(msg, type) {
            const el = document.getElementById('ai-seo-status');
            if (el) el.textContent = msg;
        },
    };

    // ============================================================
    // V2.9.13 F-4: 写作风格选择器
    // ============================================================
    const AiStyle = {
        contentId: 0,
        styles: [],

        init(contentId) {
            this.contentId = contentId;
            this.loadStyles();
        },

        loadStyles() {
            apiGet('/admin/content/getWritingStyles')
                .then(res => {
                    if (res.success) {
                        this.styles = res.data || [];
                        this.renderSelector();
                    }
                });
        },

        renderSelector() {
            const container = document.getElementById('ai-style-selector');
            if (!container) return;

            container.innerHTML = this.styles.map(s => `
                <div class="ai-style-card" data-key="${s.key}" onclick="AiStyle.select('${s.key}')">
                    <div class="ai-style-name">${s.name}</div>
                    <div class="ai-style-desc">${s.desc}</div>
                    <div class="ai-style-example">${s.example || ''}</div>
                </div>
            `).join('');
        },

        select(key) {
            // 高亮选中
            document.querySelectorAll('.ai-style-card').forEach(el => el.classList.remove('active'));
            const card = document.querySelector(`.ai-style-card[data-key="${key}"]`);
            if (card) card.classList.add('active');
        },

        generate() {
            const active = document.querySelector('.ai-style-card.active');
            if (!active) {
                showToast('请先选择一种写作风格', 'warning');
                return;
            }
            const style = active.dataset.key;
            const topic = document.querySelector('input[name="title"]')?.value || '';

            apiPost(`/admin/content/generateByStyle/${this.contentId}`, { style: style, topic: topic })
                .then(res => {
                    if (res.success) {
                        showToast('内容已生成');
                        // 将生成内容填充到编辑器（由模板层调用tinymce.setContent）
                        if (window.tinymce && window.tinymce.activeEditor) {
                            const editor = window.tinymce.activeEditor;
                            const current = editor.getContent();
                            editor.setContent(current + '<hr><h3>' + (res.data.title || '') + '</h3>' + (res.data.content || ''));
                        }
                    } else {
                        showToast(res.msg || '生成失败', 'error');
                    }
                });
        },
    };

    // ============================================================
    // 全局暴露（供模板 onclick 调用）
    // ============================================================
    window.AiImage = AiImage;
    window.AiSeo = AiSeo;
    window.AiStyle = AiStyle;

    /** 初始化入口（由 content_edit.html 调用） */
    window.initAiContentEditor = function (contentId) {
        AiImage.init(contentId);
        AiSeo.init(contentId);
        AiStyle.init(contentId);
    };
})();
