/**
 * AI助手前端交互 - V3.0 Phase 3
 *
 * 功能：
 * - 对话消息发送/接收
 * - 文件树交互（点击文件可选择在对话中引用）
 * - 版本历史查询/回退/差异对比
 * - 消息渲染（用户/AI/系统）
 */
(function () {
    'use strict';

    const AiAssistant = {
        recordId: 0,
        isProcessing: false,
        messages: [],

        init(options) {
            this.recordId = options.recordId || 0;
            this.bindEvents();
            this.loadChatHistory();
        },

        bindEvents() {
            const input = document.getElementById('ai-chat-input');
            const sendBtn = document.getElementById('btn-send-chat');
            const toggleBtn = document.getElementById('btn-toggle-panel');
            const historyBtn = document.getElementById('btn-version-history');

            if (input) {
                input.addEventListener('input', () => {
                    sendBtn.disabled = !input.value.trim() || this.isProcessing;
                });
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
            }

            if (sendBtn) {
                sendBtn.addEventListener('click', () => this.sendMessage());
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => this.togglePanel());
            }

            if (historyBtn) {
                historyBtn.addEventListener('click', () => this.showVersionHistory());
            }

            // 全局委托：版本回退/对比按钮
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;

                const action = btn.dataset.action;
                if (action === 'rollback') {
                    this.rollbackVersion(btn.dataset.identifier);
                } else if (action === 'diff') {
                    this.showVersionDiff(btn.dataset.from, btn.dataset.to);
                }
            });
        },

        togglePanel() {
            const body = document.getElementById('ai-assistant-body');
            const icon = document.getElementById('panel-toggle-icon');
            if (body.classList.contains('collapsed')) {
                body.classList.remove('collapsed');
                icon.className = 'bi bi-chevron-down';
            } else {
                body.classList.add('collapsed');
                icon.className = 'bi bi-chevron-up';
            }
        },

        async sendMessage() {
            const input = document.getElementById('ai-chat-input');
            const sendBtn = document.getElementById('btn-send-chat');
            const instruction = input.value.trim();

            if (!instruction || this.isProcessing) return;

            this.isProcessing = true;
            sendBtn.disabled = true;
            this.setStatus('生成中...', 'text-primary');

            // 添加用户消息到界面
            this.appendMessage('user', instruction);
            input.value = '';

            try {
                const response = await fetch('/admin/ai_theme/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        id: this.recordId,
                        instruction: instruction,
                    }),
                });

                const data = await response.json();

                if (data.code === 1) {
                    const changedFiles = data.data?.changed_files || [];
                    const version = data.data?.version || 0;
                    const validateErrors = data.data?.validate_errors || [];

                    let aiContent = '修改完成';
                    if (changedFiles.length > 0) {
                        aiContent += '\n\n变更文件：\n' + changedFiles.map(f => '- ' + f).join('\n');
                    }
                    if (validateErrors.length > 0) {
                        aiContent += '\n\n校验警告：\n' + validateErrors.map(e => '- ' + e).join('\n');
                    }
                    aiContent += '\n\n当前版本：v' + version;

                    this.appendMessage('ai', aiContent);
                    this.setStatus('就绪', 'text-muted');

                    // 刷新文件树和预览
                    this.refreshFileTree();
                } else {
                    this.appendMessage('system', '错误：' + (data.msg || '请求失败'));
                    this.setStatus('错误', 'text-danger');
                }
            } catch (err) {
                this.appendMessage('system', '网络错误：' + err.message);
                this.setStatus('错误', 'text-danger');
            } finally {
                this.isProcessing = false;
                sendBtn.disabled = !input.value.trim();
            }
        },

        async regenerateFile(filePath) {
            if (this.isProcessing) return;

            const instruction = prompt('请输入对「' + filePath + '」的修改指令：');
            if (!instruction) return;

            this.isProcessing = true;
            this.setStatus('生成中...', 'text-primary');

            try {
                const response = await fetch('/admin/ai_theme/regenerateFile', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        id: this.recordId,
                        file_path: filePath,
                        instruction: instruction,
                    }),
                });

                const data = await response.json();

                if (data.code === 1) {
                    this.appendMessage('ai', '文件「' + filePath + '」已修改\n版本：v' + (data.data?.version || 0));
                    this.refreshFileTree();
                    this.setStatus('就绪', 'text-muted');
                } else {
                    this.appendMessage('system', '错误：' + (data.msg || '请求失败'));
                    this.setStatus('错误', 'text-danger');
                }
            } catch (err) {
                this.appendMessage('system', '网络错误：' + err.message);
                this.setStatus('错误', 'text-danger');
            } finally {
                this.isProcessing = false;
            }
        },

        appendMessage(role, content) {
            const container = document.getElementById('ai-chat-messages');
            if (!container) return;

            const msgEl = document.createElement('div');
            msgEl.className = 'ai-chat-message ai-chat-' + role;

            const avatar = role === 'user' ? '<i class="bi bi-person"></i>' :
                role === 'ai' ? '<i class="bi bi-robot"></i>' :
                    '<i class="bi bi-exclamation-triangle"></i>';

            const name = role === 'user' ? '我' :
                role === 'ai' ? 'AI' : '系统';

            // 简单Markdown处理
            let htmlContent = this.escapeHtml(content)
                .replace(/\n/g, '<br>')
                .replace(/- (.*?)(<br>|$)/g, '<li>$1</li>');

            msgEl.innerHTML = `
                <div class="ai-chat-avatar">${avatar}</div>
                <div class="ai-chat-content">
                    <div class="ai-chat-name">${name}</div>
                    <div class="ai-chat-text">${htmlContent}</div>
                </div>
            `;

            container.appendChild(msgEl);
            container.scrollTop = container.scrollHeight;

            this.messages.push({ role, content, time: new Date().toISOString() });
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        setStatus(text, className) {
            const el = document.getElementById('ai-status-text');
            if (el) {
                el.textContent = text;
                el.className = 'small ' + (className || 'text-muted');
            }
        },

        async loadChatHistory() {
            // 页面加载时可以从服务端加载历史对话
            // 当前版本：留空，由用户输入开始新对话
        },

        async showVersionHistory() {
            const modal = new bootstrap.Modal(document.getElementById('versionHistoryModal'));
            const listEl = document.getElementById('version-history-list');
            listEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> 加载中...</div>';
            modal.show();

            try {
                const response = await fetch('/admin/ai_theme/versionHistory/' + this.recordId);
                const data = await response.json();

                if (data.code === 1) {
                    const versions = data.data?.versions || [];
                    const currentVersion = data.data?.current_version || 0;

                    if (versions.length === 0) {
                        listEl.innerHTML = '<div class="text-center text-muted py-4">暂无版本历史</div>';
                        return;
                    }

                    let html = '<div class="list-group">';
                    versions.forEach((v, index) => {
                        const isCurrent = index === 0;
                        const badge = isCurrent ? '<span class="badge bg-primary">当前</span>' : '';
                        html += `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">${v.subject} ${badge}</div>
                                    <div class="small text-muted">${v.date} · ${v.author}</div>
                                    <div class="small text-muted font-monospace">${v.hash.substring(0, 8)}</div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    ${!isCurrent ? `<button class="btn btn-outline-warning" data-action="rollback" data-identifier="${v.hash}">回退</button>` : ''}
                                    ${index < versions.length - 1 ? `<button class="btn btn-outline-info" data-action="diff" data-from="${versions[index + 1].hash}" data-to="${v.hash}">对比</button>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    listEl.innerHTML = html;
                } else {
                    listEl.innerHTML = '<div class="text-center text-danger py-4">加载失败：' + (data.msg || '未知错误') + '</div>';
                }
            } catch (err) {
                listEl.innerHTML = '<div class="text-center text-danger py-4">网络错误</div>';
            }
        },

        async rollbackVersion(identifier) {
            if (!confirm('确定要回退到这个版本吗？当前修改将丢失。')) return;

            try {
                const response = await fetch('/admin/ai_theme/rollback', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        id: this.recordId,
                        identifier: identifier,
                    }),
                });

                const data = await response.json();
                if (data.code === 1) {
                    alert('回退成功');
                    this.refreshFileTree();
                } else {
                    alert('回退失败：' + (data.msg || '未知错误'));
                }
            } catch (err) {
                alert('网络错误：' + err.message);
            }
        },

        async showVersionDiff(fromHash, toHash) {
            const modal = new bootstrap.Modal(document.getElementById('versionDiffModal'));
            const contentEl = document.getElementById('version-diff-content');
            contentEl.textContent = '加载中...';
            modal.show();

            try {
                const response = await fetch('/admin/ai_theme/versionDiff?id=' + this.recordId + '&from=' + encodeURIComponent(fromHash) + '&to=' + encodeURIComponent(toHash));
                const data = await response.json();

                if (data.code === 1) {
                    contentEl.textContent = data.data?.diff || '无差异';
                } else {
                    contentEl.textContent = '加载失败：' + (data.msg || '未知错误');
                }
            } catch (err) {
                contentEl.textContent = '网络错误：' + err.message;
            }
        },

        refreshFileTree() {
            // 触发文件树刷新（由页面其他JS处理）
            const event = new CustomEvent('ai-theme-refresh');
            document.dispatchEvent(event);
        },
    };

    // 暴露到全局
    window.AiAssistant = AiAssistant;
})();
