/**
 * AI编辑器多轮对话 — V2.9.28 A-2
 */
(function() {
    'use strict';

    const AiConversation = {
        sessionId: '',
        messages: [],

        init: function() {
            this.sidebar = document.getElementById('ai_conversation_sidebar');
            if (!this.sidebar) return;
            this.bindEvents();
            this.loadHistory();
        },

        bindEvents: function() {
            const sendBtn = document.getElementById('ai_chat_send');
            const input = document.getElementById('ai_chat_input');
            if (sendBtn) sendBtn.addEventListener('click', () => this.sendMessage());
            if (input) {
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMessage(); }
                });
            }
        },

        sendMessage: function() {
            const input = document.getElementById('ai_chat_input');
            const message = input.value.trim();
            if (!message) return;

            this.addMessage('user', message);
            input.value = '';
            this.showTyping();

            fetch('/admin/ai_content/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    session_id: this.sessionId,
                    message: message,
                    content_id: this.getContentId()
                })
            })
            .then(r => r.json())
            .then(res => {
                this.hideTyping();
                if (res.code === 0) {
                    this.sessionId = res.data.session_id;
                    this.addMessage('assistant', res.data.reply);
                    this.updateTokenBar(res.data.session_token_total, res.data.max_token);
                } else {
                    this.addMessage('assistant', '⚠️ ' + res.msg);
                }
            })
            .catch(() => { this.hideTyping(); this.addMessage('assistant', '⚠️ 网络错误'); });
        },

        addMessage: function(role, content) {
            this.messages.push({ role, content });
            const container = document.getElementById('ai_chat_messages');
            if (!container) return;
            const div = document.createElement('div');
            div.className = 'ai-chat-msg ai-chat-' + role;
            div.innerHTML = `<div class="ai-chat-bubble">${this.escape(content)}</div>`;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        },

        showTyping: function() {
            const container = document.getElementById('ai_chat_messages');
            const div = document.createElement('div');
            div.id = 'ai_typing';
            div.className = 'ai-chat-msg ai-chat-assistant';
            div.innerHTML = '<div class="ai-chat-bubble"><span class="typing-dots">...</span></div>';
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        },

        hideTyping: function() {
            const el = document.getElementById('ai_typing');
            if (el) el.remove();
        },

        updateTokenBar: function(used, max) {
            const bar = document.getElementById('ai_token_bar');
            if (bar) {
                const pct = Math.min(100, used / max * 100);
                bar.style.width = pct + '%';
                bar.textContent = used + '/' + max;
                bar.className = 'progress-bar ' + (pct > 80 ? 'bg-danger' : pct > 50 ? 'bg-warning' : 'bg-success');
            }
        },

        loadHistory: function() {
            // 从服务端加载历史对话（需要session_id）
        },

        exportChat: function(format) {
            if (!this.sessionId) { alert('暂无对话记录'); return; }
            window.open('/admin/ai_content/exportChat?session_id=' + this.sessionId + '&format=' + format);
        },

        getContentId: function() {
            return document.querySelector('input[name="id"]')?.value || 0;
        },

        escape: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        toggle: function() {
            if (this.sidebar) {
                this.sidebar.classList.toggle('show');
            }
        }
    };

    window.AiConversation = AiConversation;
    document.addEventListener('DOMContentLoaded', () => AiConversation.init());
})();
