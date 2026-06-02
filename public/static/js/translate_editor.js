/**
 * V2.9.15: AI翻译编辑器前端组件
 *
 * 功能：编辑页翻译入口、语言版本管理、批量翻译
 */
(function(window) {
    'use strict';

    var TranslateEditor = {
        contentId: 0,
        // V2.9.16: 语言列表从后端动态获取，此处为兜底默认值
        langs: {zh:'中文', en:'英语', ja:'日语', ko:'韩语', fr:'法语', de:'德语', es:'西班牙语', it:'意大利语', pt:'葡萄牙语', ru:'俄语', ar:'阿拉伯语', hi:'印地语', th:'泰语', vi:'越南语', id:'印尼语', tr:'土耳其语'},
        colors: {0:'secondary', 1:'primary', 2:'success', 3:'danger'},
        labels: {0:'待翻译', 1:'翻译中', 2:'已翻译', 3:'失败'},

        /**
         * 初始化
         */
        init: function(contentId) {
            this.contentId = contentId;
            if (!contentId) return;
            this.loadVersions();
        },

        /**
         * 加载语言版本列表
         */
        loadVersions: function() {
            var self = this;
            $.get('/admin/translate/list/' + self.contentId, function(res) {
                if (res.code === 1) {
                    self.renderVersionPanel(res.data.list);
                }
            });
        },

        /**
         * 渲染语言版本面板
         */
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

            // 同时更新编辑页顶部的翻译快捷入口
            this.renderTopBadge(list);
        },

        /**
         * 渲染顶部翻译状态badge
         */
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

        /**
         * 打开翻译确认对话框
         */
        startTranslate: function(lang) {
            var self = this;
            var langName = this.langs[lang] || lang;

            // 确认对话框
            if (!confirm('⚠️ 翻译将覆盖该语言现有版本（如有）\n✍️ 仅翻译文本，不覆盖原始数据\n\n确认翻译为 ' + langName + ' 吗？')) {
                return;
            }

            this.showProgress(lang, 10, '准备翻译...');

            $.post('/admin/translate/do/' + this.contentId, {lang: lang}, function(res) {
                if (res.code === 1) {
                    self.showProgress(lang, 100, '翻译完成');
                    setTimeout(function() {
                        self.hideProgress(lang);
                        self.loadVersions();
                    }, 800);
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
         * 删除翻译版本
         */
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

        /**
         * 显示翻译进度
         */
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

        /**
         * 隐藏翻译进度
         */
        hideProgress: function(lang) {
            var progressId = 'trans-progress-' + lang;
            var el = document.getElementById(progressId);
            if (el) el.remove();
        }
    };

    // 暴露到全局
    window.TranslateEditor = TranslateEditor;

})(window);
