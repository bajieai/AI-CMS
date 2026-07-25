/**
 * V2.9.24 H-2: 移动端底部导航动态渲染
 * 从API加载导航Tab配置，动态渲染底部导航栏
 */
(function (window) {
    'use strict';

    var BottomNav = {
        container: null,
        tabs: [],
        currentPath: '',

        init: function (containerSelector) {
            this.container = typeof containerSelector === 'string'
                ? document.querySelector(containerSelector)
                : containerSelector;
            if (!this.container) return;

            this.currentPath = window.location.pathname;
            this.loadTabs();
        },

        loadTabs: function () {
            var self = this;
            // 优先使用 sessionStorage 缓存
            var cached = null;
            try {
                cached = sessionStorage.getItem('mobile_nav_tabs');
            } catch (e) {}

            if (cached) {
                try {
                    self.tabs = JSON.parse(cached);
                    self.render();
                    return;
                } catch (e) {}
            }

            // 从API加载
            fetch('/api/mobile/navTabs')
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.code === 0 && res.data) {
                        self.tabs = res.data;
                        try {
                            sessionStorage.setItem('mobile_nav_tabs', JSON.stringify(self.tabs));
                        } catch (e) {}
                        self.render();
                    }
                })
                .catch(function () {
                    // 降级：使用容器内已有的静态导航
                });
        },

        render: function () {
            if (!this.tabs.length || !this.container) return;

            var html = '';
            for (var i = 0; i < this.tabs.length; i++) {
                var tab = this.tabs[i];
                var isActive = this.isActive(tab);
                var iconClass = isActive && tab.icon_active ? tab.icon_active : tab.icon;
                var badge = '';

                // 消息Tab的未读数角标
                if (tab.show_badge && window._memberUnreadCount > 0) {
                    badge = '<span class="nav-badge">' + window._memberUnreadCount + '</span>';
                }

                html += '<a href="' + tab.url + '" class="m-tabitem' + (isActive ? ' active' : '') + '" data-type="' + tab.tab_type + '">' +
                    '<i class="' + iconClass + '"></i>' +
                    '<span>' + tab.name + '</span>' +
                    badge +
                '</a>';
            }

            this.container.innerHTML = html;
        },

        isActive: function (tab) {
            if (tab.tab_type === 'home' && (this.currentPath === '/' || this.currentPath === '')) {
                return true;
            }
            return this.currentPath === tab.url || this.currentPath.indexOf(tab.url + '/') === 0;
        },

        // 清除缓存（后台修改导航后调用）
        clearCache: function () {
            try {
                sessionStorage.removeItem('mobile_nav_tabs');
            } catch (e) {}
        }
    };

    window.BottomNav = BottomNav;
})(window);
