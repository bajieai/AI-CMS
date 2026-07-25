/**
 * V2.9.24 H-3: 移动端搜索增强
 * 搜索联想（防抖200ms）+ 搜索历史 + 热门搜索
 * 复用现有 /api/search/suggest 和 /api/search/hot 接口
 */
(function (window) {
    'use strict';

    var SearchEnhance = {
        input: null,
        suggestBox: null,
        historyKey: 'mobile_search_history',
        maxHistory: 10,

        init: function (inputSelector) {
            this.input = typeof inputSelector === 'string'
                ? document.querySelector(inputSelector)
                : inputSelector;
            if (!this.input) return;

            this.createSuggestBox();
            this.bindEvents();
            this.showHistoryAndHot();
        },

        createSuggestBox: function () {
            var parent = this.input.closest('.m-search-box') || this.input.parentElement;
            this.suggestBox = document.createElement('div');
            this.suggestBox.className = 'search-suggest-box';
            this.suggestBox.style.display = 'none';
            parent.appendChild(this.suggestBox);
        },

        bindEvents: function () {
            var self = this;
            var debounceTimer = null;

            // 输入联想（防抖200ms）
            this.input.addEventListener('input', function () {
                var keyword = this.value.trim();
                clearTimeout(debounceTimer);
                if (keyword.length < 2) {
                    self.showHistoryAndHot();
                    return;
                }
                debounceTimer = setTimeout(function () {
                    self.fetchSuggest(keyword);
                }, 200);
            });

            // 聚焦时显示历史/热门
            this.input.addEventListener('focus', function () {
                if (this.value.trim().length < 2) {
                    self.showHistoryAndHot();
                }
            });

            // 点击外部关闭
            document.addEventListener('click', function (e) {
                if (!self.input.contains(e.target) && !self.suggestBox.contains(e.target)) {
                    self.suggestBox.style.display = 'none';
                }
            });

            // 回车搜索时保存历史
            var form = this.input.closest('form');
            if (form) {
                form.addEventListener('submit', function () {
                    self.saveHistory(self.input.value.trim());
                });
            }
        },

        fetchSuggest: function (keyword) {
            var self = this;
            fetch('/api/search/suggest?keyword=' + encodeURIComponent(keyword))
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.code === 0 && res.data && res.data.length > 0) {
                        self.renderSuggest(res.data, keyword);
                    } else {
                        self.suggestBox.style.display = 'none';
                    }
                })
                .catch(function () {
                    self.suggestBox.style.display = 'none';
                });
        },

        renderSuggest: function (items, keyword) {
            var html = '<div class="suggest-header">搜索建议</div>';
            for (var i = 0; i < items.length; i++) {
                html += '<a href="/search?keyword=' + encodeURIComponent(items[i]) + '" class="suggest-item">' +
                    '<i class="bi bi-search"></i>' +
                    '<span>' + this.highlight(items[i], keyword) + '</span>' +
                    '</a>';
            }
            this.suggestBox.innerHTML = html;
            this.suggestBox.style.display = 'block';
        },

        showHistoryAndHot: function () {
            var history = this.getHistory();
            var html = '';

            if (history.length > 0) {
                html += '<div class="suggest-header">搜索历史' +
                    '<span class="suggest-clear" onclick="SearchEnhance.clearHistory()">清除</span></div>';
                for (var i = 0; i < history.length; i++) {
                    html += '<a href="/search?keyword=' + encodeURIComponent(history[i]) + '" class="suggest-item">' +
                        '<i class="bi bi-clock-history"></i>' +
                        '<span>' + this.escapeHtml(history[i]) + '</span>' +
                        '</a>';
                }
            }

            // 异步加载热门搜索
            var self = this;
            fetch('/api/search/hot')
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.code === 0 && res.data && res.data.length > 0) {
                        html += '<div class="suggest-header">热门搜索</div>';
                        for (var i = 0; i < res.data.length; i++) {
                            var kw = typeof res.data[i] === 'string' ? res.data[i] : (res.data[i].keyword || res.data[i].title);
                            html += '<a href="/search?keyword=' + encodeURIComponent(kw) + '" class="suggest-item">' +
                                '<i class="bi bi-fire" style="color:#dc3545"></i>' +
                                '<span>' + self.escapeHtml(kw) + '</span>' +
                                '</a>';
                        }
                    }
                    if (html) {
                        self.suggestBox.innerHTML = html;
                        self.suggestBox.style.display = 'block';
                    }
                })
                .catch(function () {
                    if (html) {
                        self.suggestBox.innerHTML = html;
                        self.suggestBox.style.display = 'block';
                    }
                });

            if (html) {
                this.suggestBox.innerHTML = html;
                this.suggestBox.style.display = 'block';
            }
        },

        getHistory: function () {
            try {
                return JSON.parse(localStorage.getItem(this.historyKey) || '[]');
            } catch (e) {
                return [];
            }
        },

        saveHistory: function (keyword) {
            if (!keyword) return;
            var history = this.getHistory();
            // 去重
            var idx = history.indexOf(keyword);
            if (idx > -1) history.splice(idx, 1);
            history.unshift(keyword);
            if (history.length > this.maxHistory) history = history.slice(0, this.maxHistory);
            try {
                localStorage.setItem(this.historyKey, JSON.stringify(history));
            } catch (e) {}
        },

        clearHistory: function () {
            try {
                localStorage.removeItem(this.historyKey);
            } catch (e) {}
            this.suggestBox.style.display = 'none';
            this.input.focus();
        },

        highlight: function (text, keyword) {
            var reg = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return this.escapeHtml(text).replace(reg, '<mark>$1</mark>');
        },

        escapeHtml: function (str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    window.SearchEnhance = SearchEnhance;
})(window);
