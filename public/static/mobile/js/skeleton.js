/**
 * V2.9.24 H-5: 骨架屏加载工具
 * 配合 pull_to_refresh.js 管理加载状态
 */
(function (window) {
    'use strict';

    var Skeleton = {
        listTemplate: function (count) {
            count = count || 5;
            var html = '<div class="skeleton-list show">';
            for (var i = 0; i < count; i++) {
                html += '<div class="skeleton-card"><div class="skeleton skeleton-card-img"></div><div class="skeleton-card-body"><div class="skeleton skeleton-line title"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line short"></div></div></div>';
            }
            html += '</div>';
            return html;
        },

        detailTemplate: function () {
            return '<div class="skeleton-detail show"><div class="skeleton skeleton-detail-title"></div><div class="skeleton-detail-meta"><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div></div><div class="skeleton skeleton-detail-cover"></div><div class="skeleton-detail-body"><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line short"></div></div></div>';
        },

        showList: function (container, count) {
            container = typeof container === 'string' ? document.querySelector(container) : container;
            if (!container) return;
            container.setAttribute('data-original-html', container.innerHTML);
            container.innerHTML = this.listTemplate(count);
        },

        showDetail: function (container) {
            container = typeof container === 'string' ? document.querySelector(container) : container;
            if (!container) return;
            container.setAttribute('data-original-html', container.innerHTML);
            container.innerHTML = this.detailTemplate();
        },

        hide: function (container) {
            container = typeof container === 'string' ? document.querySelector(container) : container;
            if (!container) return;
            var original = container.getAttribute('data-original-html');
            if (original !== null) {
                container.innerHTML = original;
                container.removeAttribute('data-original-html');
            } else {
                var skeletons = container.querySelectorAll('.skeleton-list, .skeleton-detail');
                skeletons.forEach(function (s) { s.remove(); });
            }
        }
    };

    window.Skeleton = Skeleton;
})(window);
