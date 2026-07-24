/**
 * V2.9.17: AI翻译编辑器前端组件（轮询可配置化增强）
 *
 * T-4: 轮询间隔从后端配置读取，支持动态加速和超时保护
 */
(function(window) {
    'use strict';

    var TranslateEditor = {
        contentId: 0,
        // V2.9.17: 16种语言兜底列表（从config/ai.php同步）
        langs: {zh:'中文', en:'英语', ja:'日语', ko:'韩语', fr:'法语', de:'德语', es:'西班牙语', it:'意大利语', pt:'葡萄牙语', ru:'俄语', ar:'阿拉伯语', hi:'印地语', th:'泰语', vi:'越南语', id:'印尼语', tr:'土耳其语'},
        colors: {0:'secondary', 1:'primary', 2:'success', 3:'danger'},
        labels: {0:'待翻译', 1:'翻译中', 2:'已翻译', 3:'失败'},

        init: function(contentId) {
            this.contentId = contentId;
            if (!contentId) return;
            this.loadVersions();
        },

        loadVersions: function() {
            var self = this;
            $.get('/admin/translate/list/' + self.contentId, function(res) {
                if (res.code === 1) {
                    self.renderVersionPanel(res.data.list);
                }
            });
        },

        renderVersionPanel: function(list) {
            var html = '';
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var color = this.colors[item.status] || 'secondary';
                var label = this.labels[item.status] || '未知';
                var btnClass = item.status === 2 ? 'btn-outline-success' : 'btn-outline-primary';
                var btnText = item.status === 2 ? '重新翻译' : '翻译';
                var btnDisabled = item.status === 1 ? 'disabled' : '';
                var timeStr = item.update_time ? new Date(item.update_time * 1000).toLocaleString() : '-';

                html += '<div class="d-flex align-items-center justify-content-between mb-2 py-1 border-bottom translate-version-item" data-lang="' + item.lang_code + '">'
                    + '<div>'
                    + '<span class="badge bg-' + color + ' me-2">' + label + '</span>'
                    + '<strong>' + item.lang_name + '</strong>'
                    + '<small class="text-muted ms-2">' + timeStr + '</small>'
                    + '</div>'
                    + '<div class="btn-group btn-group-sm">'
                    + '<button class="btn ' + btnClass + '" onclick="TranslateEditor.startTranslate(\'' + item.lang_code + '\')" ' + btnDisabled + '>' + btnText + '</button>'
                    + '<button class="btn btn-outline-secondary" onclick="TranslateEditor.deleteVersion(\'' + item.lang_code + '\')">删除</button>'
                    + '</div>'
                    + '</div>';
            }

            var panel = document.getElementById('translateVersionPanel');
            if (panel) {
                panel.innerHTML = html || '<div class="text-muted text-center py-2">暂无翻译版本</div>';
            }
            this.renderTopBadge(list);
        },

        renderTopBadge: function(list) {
            var container = document.getElementById('translateTopBadge');
            if (!container) return;
            var html = '';
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var color = this.colors[item.status] || 'secondary';
                html += '<span class="badge bg-' + color + ' me-1" title="' + item.lang_name + ': ' + this.labels[item.status] + '">' + item.lang_code.toUpperCase() + '</span>';
            }
            container.innerHTML = html || '<span class="text-muted small">未翻译</span>';
        },

        startTranslate: function(lang) {
            var self = this;
            var langName = this.langs[lang] || lang;

            if (!confirm('确认翻译为 ' + langName + ' 吗？翻译将覆盖该语言现有版本。')) {
                return;
            }

            self.showProgress(lang, 10, '提交翻译任务...');

            $.post('/admin/translate/do/' + this.contentId, {lang: lang}, function(res) {
                if (res.code === 1) {
                    // V2.9.17 E-2: 优先SSE，失败降级轮询
                    if (typeof EventSource !== 'undefined' && res.data && res.data.record_id) {
                        self.startSSE(lang, res.data.record_id);
                    } else if (res.data && res.data.task_id) {
                        self.pollTranslationStatus(lang, res.data.task_id);
                    } else {
                        self.showProgress(lang, 100, '翻译完成');
                        setTimeout(function() { self.hideProgress(lang); self.loadVersions(); }, 800);
                    }
                } else {
                    self.hideProgress(lang);
                    alert('翻译失败: ' + res.msg);
                }
            }).fail(function() {
                self.hideProgress(lang);
                alert('翻译请求失败，请检查网络');
            });
        },

        /**
         * V2.9.17 E-2: SSE实时流监听，失败自动降级到轮询
         */
        startSSE: function(lang, recordId) {
            var self = this;
            var src = new EventSource('/admin/ai_translate/stream/' + recordId);
            var fallbackStarted = false;

            var startPollingFallback = function() {
                if (fallbackStarted) return;
                fallbackStarted = true;
                src.close();
                self.pollTranslationStatus(lang, recordId);
            };

            src.addEventListener('progress', function(e) {
                var d = JSON.parse(e.data);
                self.showProgress(lang, d.progress || 10, d.message || '翻译中...');
            });

            src.addEventListener('complete', function(e) {
                src.close();
                self.showProgress(lang, 100, '翻译完成');
                setTimeout(function() { self.hideProgress(lang); self.loadVersions(); }, 800);
            });

            src.addEventListener('error', function(e) { src.close(); startPollingFallback(); });
            src.addEventListener('timeout', function(e) { src.close(); startPollingFallback(); });
            src.onerror = function() { src.close(); startPollingFallback(); };

            // 30秒无消息自动降级
            setTimeout(function() { if (src.readyState !== 2) startPollingFallback(); }, 30000);
        },

        /**
         * V2.9.17 T-4: 可配置轮询引擎
         * 从 window.AI_CMS_CONFIG 读取轮询参数
         */
        pollTranslationStatus: function(lang, taskId) {
            var self = this;
            var cfg = (window.AI_CMS_CONFIG || {});
            var interval = cfg.translate_poll_interval || 3000;
            var fastInterval = cfg.translate_poll_fast_interval || 1000;
            var maxAttempts = cfg.translate_poll_max_attempts || 60;
            var currentAttempt = 0;
            var timer = null;

            var doPoll = function() {
                currentAttempt++;
                if (currentAttempt > maxAttempts) {
                    clearInterval(timer);
                    self.hideProgress(lang);
                    alert('翻译状态查询超时，请刷新页面查看结果');
                    return;
                }

                $.get('/admin/translate/status/' + self.contentId + '/' + lang, function(res) {
                    if (res.code !== 1) {
                        clearInterval(timer);
                        self.hideProgress(lang);
                        alert('查询翻译状态失败: ' + (res.msg || '未知错误'));
                        return;
                    }

                    var statusData = res.data || {};
                    var status = statusData.translate_status || statusData.status || 0;
                    var progress = statusData.progress || 0;
                    var message = statusData.message || '';

                    if (status === 2) { // completed
                        clearInterval(timer);
                        self.showProgress(lang, 100, '翻译完成');
                        setTimeout(function() { self.hideProgress(lang); self.loadVersions(); }, 800);
                    } else if (status === 3) { // failed
                        clearInterval(timer);
                        self.hideProgress(lang);
                        alert('翻译失败: ' + (message || '未知错误'));
                    } else { // processing (1) or pending (0)
                        self.showProgress(lang, Math.max(progress, 10), message || '翻译中...');

                        // V2.9.17 T-4: 进度>80%时切换到快速轮询
                        if (progress >= 80 && timer) {
                            clearInterval(timer);
                            timer = setInterval(doPoll, fastInterval);
                        }
                    }
                }).fail(function() {
                    currentAttempt--;
                });
            };

            doPoll(); // 立即执行第一次
            timer = setInterval(doPoll, interval);
        },

        deleteVersion: function(lang) {
            var self = this;
            var langName = this.langs[lang] || lang;
            if (!confirm('确认删除 ' + langName + ' 翻译版本吗？')) return;

            $.post('/admin/translate/delete/' + this.contentId + '/' + lang, function(res) {
                if (res.code === 1) {
                    self.loadVersions();
                } else {
                    alert('删除失败: ' + res.msg);
                }
            });
        },

        showProgress: function(lang, percent, message) {
            var item = document.querySelector('.translate-version-item[data-lang="' + lang + '"]');
            if (!item) return;

            var progressId = 'trans-progress-' + lang;
            var existing = document.getElementById(progressId);
            if (!existing) {
                var div = document.createElement('div');
                div.id = progressId;
                div.className = 'mt-1';
                div.innerHTML = '<div class="progress" style="height:4px;"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:' + percent + '%"></div></div>'
                    + '<small class="text-muted">' + message + '</small>';
                item.appendChild(div);
            } else {
                existing.querySelector('.progress-bar').style.width = percent + '%';
                existing.querySelector('small').textContent = message;
            }
        },

        hideProgress: function(lang) {
            var progressId = 'trans-progress-' + lang;
            var el = document.getElementById(progressId);
            if (el) el.remove();
        }
    };

    window.TranslateEditor = TranslateEditor;
})(window);
