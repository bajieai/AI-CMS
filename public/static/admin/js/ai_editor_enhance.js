/**
 * AI编辑器增强 — V2.9.28 A-1
 * 段落级优化：选中文本弹出AI悬浮按钮
 */
(function() {
    'use strict';

    const AiEditorEnhance = {
        init: function(editorSelector) {
            this.editor = document.querySelector(editorSelector) || document.querySelector('#content_editor');
            if (!this.editor) return;

            this.createFloatingButton();
            this.bindEvents();
        },

        createFloatingButton: function() {
            this.floatBtn = document.createElement('div');
            this.floatBtn.className = 'ai-float-btn';
            this.floatBtn.style.display = 'none';
            this.floatBtn.innerHTML = `
                <div class="ai-float-menu">
                    <button class="ai-float-item" data-action="optimize"><i class="bi bi-magic"></i> 优化</button>
                    <button class="ai-float-item" data-action="proofread"><i class="bi bi-spellcheck"></i> 校对</button>
                    <button class="ai-float-item" data-action="translate"><i class="bi bi-translate"></i> 翻译</button>
                    <button class="ai-float-item" data-action="continue"><i class="bi bi-arrow-return-right"></i> 续写</button>
                    <button class="ai-float-item" data-action="rewrite"><i class="bi bi-pencil"></i> 改写</button>
                </div>
                <button class="ai-float-trigger"><i class="bi bi-stars"></i></button>
            `;
            document.body.appendChild(this.floatBtn);

            this.floatBtn.querySelectorAll('.ai-float-item').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.processAction(btn.dataset.action);
                });
            });
        },

        bindEvents: function() {
            this.editor.addEventListener('mouseup', () => this.checkSelection());
            this.editor.addEventListener('keyup', (e) => {
                if (e.key === 'Escape') this.hideFloat();
                else this.checkSelection();
            });
            document.addEventListener('click', (e) => {
                if (!this.floatBtn.contains(e.target)) this.hideFloat();
            });
        },

        checkSelection: function() {
            const sel = window.getSelection();
            if (sel.rangeCount > 0) {
                const text = sel.toString().trim();
                if (text.length >= 2) {
                    this.showFloat(sel);
                    this.selectedText = text;
                } else {
                    this.hideFloat();
                }
            }
        },

        showFloat: function(sel) {
            const range = sel.getRangeAt(0);
            const rect = range.getBoundingClientRect();
            this.floatBtn.style.display = 'block';
            this.floatBtn.style.top = (window.scrollY + rect.top - 50) + 'px';
            this.floatBtn.style.left = (window.scrollX + rect.left) + 'px';
        },

        hideFloat: function() {
            this.floatBtn.style.display = 'none';
        },

        processAction: function(action) {
            if (!this.selectedText) return;
            this.showLoading();

            let url = '/admin/ai_content/';
            let data = { text: this.selectedText, content_id: this.getContentId() };

            switch (action) {
                case 'optimize':
                    url += 'optimizeParagraph';
                    data.mode = 'all';
                    break;
                case 'proofread':
                    url += 'optimizeParagraph';
                    data.mode = 'proofread';
                    break;
                case 'translate':
                    this.showTranslateDialog(this.selectedText);
                    this.hideLoading();
                    return;
                case 'continue':
                    url += 'continue';
                    break;
                case 'rewrite':
                    url += 'rewrite';
                    break;
            }

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            })
            .then(r => r.json())
            .then(res => {
                this.hideLoading();
                if (res.code === 0) {
                    this.showResult(res.data.text, action);
                } else {
                    alert(res.msg || '处理失败');
                }
            })
            .catch(() => { this.hideLoading(); alert('请求失败'); });
        },

        showTranslateDialog: function(text) {
            const langs = { en:'英文', ja:'日文', ko:'韩文', fr:'法文', de:'德文', es:'西班牙文', ru:'俄文', ar:'阿拉伯文', pt:'葡萄牙文', zh:'中文' };
            const langOptions = Object.entries(langs).map(([code, name]) => `<option value="${code}">${name}</option>`).join('');
            const html = `
                <div class="modal fade" id="translateModal" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">选段翻译</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-2"><label>目标语言</label><select id="tr_lang" class="form-select">${langOptions}</select></div>
                                <div class="mb-2"><label>模式</label><select id="tr_mode" class="form-select"><option value="replace">替换原文</option><option value="insert">追加在原文下方</option><option value="compare">原文译文并列</option></select></div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button><button type="button" class="btn btn-primary" id="tr_confirm">翻译</button></div>
                        </div>
                    </div>
                </div>`;
            const div = document.createElement('div');
            div.innerHTML = html;
            document.body.appendChild(div);
            const modal = new bootstrap.Modal(div.querySelector('#translateModal'));
            modal.show();
            div.querySelector('#tr_confirm').addEventListener('click', () => {
                const lang = div.querySelector('#tr_lang').value;
                const mode = div.querySelector('#tr_mode').value;
                modal.hide();
                this.showLoading();
                fetch('/admin/ai_content/translate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ text: text, target_lang: lang, mode: mode, content_id: this.getContentId() })
                })
                .then(r => r.json())
                .then(res => {
                    this.hideLoading();
                    if (res.code === 0) { this.showResult(res.data.result, 'translate'); }
                    else { alert(res.msg); }
                });
            });
            div.querySelector('#translateModal').addEventListener('hidden.bs.modal', () => div.remove());
        },

        showResult: function(text, action) {
            const html = `
                <div class="modal fade" id="aiResultModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header"><h5 class="modal-title">AI处理结果</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body"><div class="form-floating"><textarea class="form-control" id="ai_result_text" style="height:300px">${text}</textarea><label>处理结果</label></div></div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button><button type="button" class="btn btn-primary" id="ai_replace_btn"><i class="bi bi-check-lg"></i> 一键替换</button></div>
                        </div>
                    </div>
                </div>`;
            const div = document.createElement('div');
            div.innerHTML = html;
            document.body.appendChild(div);
            const modal = new bootstrap.Modal(div.querySelector('#aiResultModal'));
            modal.show();
            div.querySelector('#ai_replace_btn').addEventListener('click', () => {
                const newText = div.querySelector('#ai_result_text').value;
                this.replaceSelection(newText);
                modal.hide();
            });
            div.querySelector('#aiResultModal').addEventListener('hidden.bs.modal', () => div.remove());
        },

        replaceSelection: function(newText) {
            const sel = window.getSelection();
            if (sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                range.deleteContents();
                range.insertNode(document.createTextNode(newText));
            }
        },

        showLoading: function() {
            if (!this.loadingEl) {
                this.loadingEl = document.createElement('div');
                this.loadingEl.className = 'ai-loading-overlay';
                this.loadingEl.innerHTML = '<div class="spinner-border text-primary"></div><p class="mt-2">AI处理中...</p>';
                document.body.appendChild(this.loadingEl);
            }
            this.loadingEl.style.display = 'flex';
        },

        hideLoading: function() {
            if (this.loadingEl) this.loadingEl.style.display = 'none';
        },

        getContentId: function() {
            return document.querySelector('input[name="id"]')?.value || 0;
        }
    };

    window.AiEditorEnhance = AiEditorEnhance;
    document.addEventListener('DOMContentLoaded', () => AiEditorEnhance.init());
})();
