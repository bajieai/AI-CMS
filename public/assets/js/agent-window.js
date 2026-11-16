/**
 * AI 助手对话悬浮窗（ACP-15 · V2.9.63 S3-T7）
 *
 * 依据 S-19 / 动作层规格 v1.2 §4.9：
 *  - R-6 白名单永远第一：本脚本只渲染，不做任何 LLM 调用与本地兜底推测
 *  - S-8：请求仅携带 utterance / current_path / CSRF，不携带 Cookie 之外的凭证；
 *    current_path 仅 pathname + search（不含任何会话信息）
 *  - S-1：结构化卡片带 data_origin 水印（mock/real 必须可辨识）
 *  - PJAX 兼容：DOM 由 PjaxMiddleware 回挂，本脚本一次性加载（pjax.js 按 src 去重），
 *    事件全部委托到 document（挂载/替换后无需重新绑定）
 *
 * 注意：本文件以分号收尾的 IIFE 结构，满足 PJAX 内联脚本拼接的终止符要求。
 */
(function () {
    'use strict';

    // PJAX 切页重复执行守卫（若被内联拼接，isLayoutGlobalScript 亦会以本标记跳过）
    if (window.AI_ASSISTANT_WINDOW_BOOTED) {
        return;
    }
    window.AI_ASSISTANT_WINDOW_BOOTED = true;
    window.AIAssistantWindow = { booted: true };

    var CFG = {
        askUrl: '/admin/ai_assistant/ask',
        csrfUrl: '/admin/ai_assistant/csrf',
        historyUrl: '/admin/ai_assistant/history',
        quickAsks: ['有多少文章', '系统是什么版本', '最近 AI 调用日志', '数据概览']
    };

    var csrfCache = '';

    function $(id) {
        return document.getElementById(id);
    }

    function rootEl() {
        return $('ai-assistant-root');
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(text)));
        return div.innerHTML;
    }

    // ==================== 渲染 ====================

    function appendUserMsg(text) {
        var body = $('ai-assistant-msgs');
        if (!body) {
            return;
        }
        var div = document.createElement('div');
        div.className = 'ai-assistant-msg user';
        div.textContent = text;
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }

    function appendBotHtml(html) {
        var body = $('ai-assistant-msgs');
        if (!body) {
            return;
        }
        var div = document.createElement('div');
        div.className = 'ai-assistant-msg bot';
        div.innerHTML = html;
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
        return div;
    }

    function appendBotText(text) {
        return appendBotHtml(escapeHtml(text));
    }

    /** data_origin 水印（S-1：mock/real 必须可辨识） */
    function watermark(meta) {
        meta = meta || {};
        var origin = meta.data_origin === 'mock' ? 'mock（模拟数据）' : (meta.data_origin || 'real') + '（真实数据）';
        var parts = ['数据来源：' + origin];
        if (meta.source_table) {
            parts.push('表 ' + meta.source_table);
        }
        if (meta.queried_at) {
            parts.push('查询时间 ' + meta.queried_at);
        }
        if (meta.dry_run) {
            parts.push('dry-run 未落库');
        }
        return '<div class="ai-assistant-wm">' + escapeHtml(parts.join(' · ')) + '</div>';
    }

    /** 通用 key-value 渲染（跳过数组/对象/空值） */
    function kvList(data) {
        var html = '<dl class="ai-card-kv">';
        Object.keys(data).forEach(function (key) {
            var value = data[key];
            if (value === null || value === undefined || typeof value === 'object') {
                return;
            }
            html += '<dt>' + escapeHtml(key) + '</dt><dd>' + escapeHtml(value) + '</dd>';
        });
        html += '</dl>';
        return html;
    }

    /** 行表渲染（≤5 列 × ≤5 行，超出截断说明） */
    function rowsTable(rows) {
        if (!rows || !rows.length) {
            return '';
        }
        var columns = [];
        rows.slice(0, 5).forEach(function (row) {
            Object.keys(row).forEach(function (key) {
                var value = row[key];
                if (value !== null && value !== undefined && typeof value !== 'object' && columns.indexOf(key) < 0) {
                    columns.push(key);
                }
            });
        });
        columns = columns.slice(0, 5);
        if (!columns.length) {
            return '';
        }

        var html = '<table><thead><tr>';
        columns.forEach(function (col) {
            html += '<th>' + escapeHtml(col) + '</th>';
        });
        html += '</tr></thead><tbody>';
        rows.slice(0, 5).forEach(function (row) {
            html += '<tr>';
            columns.forEach(function (col) {
                html += '<td>' + escapeHtml(row[col] === null || row[col] === undefined ? '-' : row[col]) + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        if (rows.length > 5) {
            html += '<div style="color:#a2aabd;font-size:11px;margin-top:4px">仅展示前 5 条，共 ' + rows.length + ' 条</div>';
        }
        return html;
    }

    /** 结构化卡片（S3-T7：总数字段优先，其次行表，最后 KV 兜底） */
    function cardHtml(card) {
        var data = (card && card.data) || {};
        var html = '<div class="ai-assistant-card">';

        if (data.total_count !== undefined && data.total_count !== null) {
            html += '<div class="ai-assistant-total">' + escapeHtml(data.total_count) + '</div>';
            delete data.total_count;
        }

        if (data.rows && data.rows.length) {
            html += rowsTable(data.rows);
        }

        var rest = {};
        Object.keys(data).forEach(function (key) {
            if (key !== 'rows' && key !== 'diff_summary') {
                rest[key] = data[key];
            }
        });

        var scalarCount = Object.keys(rest).filter(function (key) {
            return rest[key] === null || typeof rest[key] !== 'object';
        }).length;
        if (scalarCount > 0 && !(data.rows && data.rows.length)) {
            html += kvList(rest);
        }

        if (card.meta && card.meta.diff_summary) {
            html += '<div style="color:#8891a8;font-size:12px">' + escapeHtml(card.meta.diff_summary) + '</div>';
        }

        html += watermark(card.meta);
        html += '</div>';
        return html;
    }

    // ==================== 请求 ====================

    /**
     * CSRF Token 动态获取：页面级 token() 多次渲染会覆盖 session 值，
     * 片段内静态值不可靠 —— 发送前实时取用（GET 免 CSRF），失败重试时刷新。
     */
    function fetchCsrf() {
        return fetch(CFG.csrfUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (resp) {
            return resp.json();
        }).then(function (res) {
            if (res && res.code === 0 && res.data && res.data.token) {
                csrfCache = res.data.token;
            }
            return csrfCache;
        }).catch(function () {
            return csrfCache;
        });
    }

    function setBusy(busy) {
        var btn = $('ai-assistant-send');
        var input = $('ai-assistant-input');
        if (btn) {
            btn.disabled = busy;
        }
        if (input) {
            input.disabled = busy;
        }
    }

    function ask(utterance) {
        utterance = (utterance || '').trim();
        if (!utterance) {
            return;
        }
        appendUserMsg(utterance);
        setBusy(true);
        var pending = appendBotHtml('<span class="ai-assistant-loading">查询中</span>');

        return fetchCsrf().then(function () {
            return sendAsk(utterance);
        }).then(function (res) {
            // 403 = CSRF 失效（会话过期/被覆盖），刷新 token 重试一次
            if (res && res.code === 403 && /csrf/i.test(res.msg || '')) {
                csrfCache = '';
                return fetchCsrf().then(function () {
                    return sendAsk(utterance);
                });
            }
            return res;
        }).then(function (res) {
            if (pending && pending.parentNode) {
                pending.parentNode.removeChild(pending);
            }
            if (!res || res.code !== 0) {
                appendBotText((res && res.msg) || '请求失败，请重试');
                return;
            }
            renderAnswer(res.data || {});
        }).catch(function () {
            if (pending && pending.parentNode) {
                pending.parentNode.removeChild(pending);
            }
            appendBotText('网络异常，请稍后重试');
        }).then(function () {
            setBusy(false);
            var input = $('ai-assistant-input');
            if (input) {
                input.focus();
            }
        });
    }

    function sendAsk(utterance) {
        return fetch(CFG.askUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfCache,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                utterance: utterance,
                // S-8：仅上送路径与查询串，不上送任何会话/凭证信息
                current_path: window.location.pathname + window.location.search
            })
        }).then(function (resp) {
            return resp.json();
        });
    }

    /** 按 answer_type 渲染（R-6：answer_type/q_id 每条必须可见；仅命中类渲染数据卡片与水印） */
    function renderAnswer(answer) {
        var prefix = answer.q_id
            ? '<div style="color:#a2aabd;font-size:11px;margin-bottom:4px">' + escapeHtml(answer.q_id) + ' · ' + escapeHtml(answer.answer_type) + '</div>'
            : '<div style="color:#a2aabd;font-size:11px;margin-bottom:4px">' + escapeHtml(answer.answer_type) + '</div>';

        var hasCard = answer.card && answer.card.action_id;
        var body = hasCard
            ? cardHtml(answer.card) + escapeHtml(answer.message || '')
            : escapeHtml(answer.message || '');

        appendBotHtml(prefix + body);
    }

    // ==================== 事件（全部委托，PJAX 重挂后无需重绑） ====================

    document.addEventListener('click', function (event) {
        var root = rootEl();
        if (!root || !root.contains(event.target)) {
            return;
        }

        var chip = event.target.closest('.ai-assistant-chip');
        if (chip) {
            ask(chip.getAttribute('data-q') || chip.textContent);
            return;
        }

        if (event.target.id === 'ai-assistant-fab') {
            var win = $('ai-assistant-window');
            if (win) {
                win.hidden = !win.hidden;
            }
            return;
        }

        if (event.target.id === 'ai-assistant-close') {
            var winToClose = $('ai-assistant-window');
            if (winToClose) {
                winToClose.hidden = true;
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.target && event.target.id === 'ai-assistant-input' && event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            var input = event.target;
            ask(input.value);
            input.value = '';
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target.id !== 'ai-assistant-send') {
            return;
        }
        var input = $('ai-assistant-input');
        if (input) {
            ask(input.value);
            input.value = '';
        }
    });

    // ==================== PJAX 幂等去重（S3-T5：切页后悬浮窗不重复、不消失） ====================

    /**
     * PjaxMiddleware 把悬浮窗片段回挂进容器内容，与 layout 常驻挂载形成副本；
     * 此处保留容器外的常驻挂载，清理容器内注入的副本与重复样式。
     */
    function dedupeAssistant() {
        var roots = document.querySelectorAll('#ai-assistant-root');
        if (roots.length > 1) {
            var container = document.getElementById('pjax-container');
            var kept = null;
            Array.prototype.forEach.call(roots, function (el) {
                var insideContainer = !!(container && container.contains(el));
                if (!insideContainer && !kept) {
                    kept = el;
                    return;
                }
                el.parentNode && el.parentNode.removeChild(el);
            });
            // 兜底：全部副本都在容器内时，保留一个并移出容器
            if (!kept && roots.length) {
                kept = roots[0];
                document.body.appendChild(kept);
                Array.prototype.forEach.call(roots, function (el) {
                    if (el !== kept) {
                        el.parentNode && el.parentNode.removeChild(el);
                    }
                });
            }
        }

        var styles = document.querySelectorAll('#ai-assistant-style');
        if (styles.length > 1) {
            Array.prototype.forEach.call(styles, function (el, index) {
                if (index > 0) {
                    el.parentNode && el.parentNode.removeChild(el);
                }
            });
        }
    }

    if (window.jQuery) {
        window.jQuery(document).on('pjax:complete', dedupeAssistant);
    }
    document.addEventListener('DOMContentLoaded', dedupeAssistant);
    dedupeAssistant();
})();
