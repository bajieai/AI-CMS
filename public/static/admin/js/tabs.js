/**
 * AI-CMS V2.9.10 通用Tab标签页组件
 * 提供无依赖的Tab切换能力，兼容Bootstrap 5风格
 */
(function(window) {
    'use strict';

    var Tabs = {
        /**
         * 初始化指定容器内的Tab
         * @param {string|HTMLElement} container Tab导航容器
         * @param {Object} options 配置项
         */
        init: function(container, options) {
            options = options || {};
            var nav = typeof container === 'string' ? document.querySelector(container) : container;
            if (!nav) return;

            var panes = options.panes || (nav.dataset.target ? document.querySelectorAll(nav.dataset.target) : []);
            var activeClass = options.activeClass || 'active';
            var paneClass = options.paneClass || 'active';
            var trigger = options.trigger || 'click';
            var callback = options.onSwitch || null;

            var links = nav.querySelectorAll('[data-tab]');
            links.forEach(function(link) {
                link.addEventListener(trigger, function(e) {
                    e.preventDefault();
                    var targetId = this.dataset.tab;

                    // 切换导航激活态
                    links.forEach(function(l) { l.classList.remove(activeClass); });
                    this.classList.add(activeClass);

                    // 切换内容面板
                    if (panes.length > 0) {
                        panes.forEach(function(p) { p.classList.remove(paneClass); });
                    } else {
                        document.querySelectorAll('.tab-pane').forEach(function(p) { p.classList.remove(paneClass); });
                    }
                    var targetPane = document.getElementById(targetId);
                    if (targetPane) targetPane.classList.add(paneClass);

                    // 触发回调
                    if (typeof callback === 'function') callback(targetId, this);

                    // 同步URL hash（可选）
                    if (options.syncHash !== false) {
                        history.replaceState(null, null, '#' + targetId);
                    }
                });
            });

            // 自动激活URL hash对应的Tab
            if (options.syncHash !== false && location.hash) {
                var hashTab = nav.querySelector('[data-tab="' + location.hash.substring(1) + '"]');
                if (hashTab) hashTab.click();
            }
        },

        /**
         * 切换到指定Tab
         * @param {string|HTMLElement} container Tab导航容器
         * @param {string} tabId 目标Tab ID
         */
        switchTo: function(container, tabId) {
            var nav = typeof container === 'string' ? document.querySelector(container) : container;
            if (!nav) return;
            var link = nav.querySelector('[data-tab="' + tabId + '"]');
            if (link) link.click();
        },

        /**
         * 获取当前激活的Tab ID
         * @param {string|HTMLElement} container Tab导航容器
         * @returns {string|null}
         */
        getActive: function(container) {
            var nav = typeof container === 'string' ? document.querySelector(container) : container;
            if (!nav) return null;
            var active = nav.querySelector('.active[data-tab]');
            return active ? active.dataset.tab : null;
        }
    };

    // 自动初始化带有 data-tabs="auto" 的元素
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-tabs="auto"]').forEach(function(nav) {
            Tabs.init(nav, {
                syncHash: nav.dataset.syncHash !== 'false'
            });
        });
    });

    window.AITabs = Tabs;
})(window);
