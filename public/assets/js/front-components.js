/**
 * V2.9.5 前台通用组件
 * - 空状态占位 (EmptyState)
 * - 加载状态 (LoadingSpinner, Skeleton)
 * - 404引导
 */
(function (window) {
    'use strict';

    const EmptyState = {
        render(message, subMessage, iconSvg) {
            const icon = iconSvg || '<svg width="64" height="64" fill="#cbd5e1" viewBox="0 0 16 16"><path d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 2 5.166 4.579C14.758 4.804 16 6.137 16 7.773 16 9.569 14.502 11 12.687 11H10a.5.5 0 0 1 0-1h2.688C13.979 10 15 8.988 15 7.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 2.825 10.328 1 8 1a4.53 4.53 0 0 0-2.941 1.1c-.757.652-1.153 1.438-1.153 2.055v.448l-.445.049C2.064 4.805 1 5.952 1 7.318 1 8.785 2.23 10 3.781 10H6a.5.5 0 0 1 0 1H3.781C1.708 11 0 9.366 0 7.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383z"/><path d="M7.646 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V5.5a.5.5 0 0 0-1 0v8.793l-2.146-2.147a.5.5 0 0 0-.708.708l3 3z"/></svg>';
            return '<div class="i8j-empty-state text-center py-5">' +
                '<div class="mb-3">' + icon + '</div>' +
                '<p class="text-secondary mb-1">' + (message || '暂无数据') + '</p>' +
                (subMessage ? '<p class="small text-muted">' + subMessage + '</p>' : '') +
                '</div>';
        }
    };

    const LoadingSpinner = {
        render(size = 40) {
            return '<div class="i8j-loading text-center py-4">' +
                '<div class="spinner-border text-primary" role="status" style="width:' + size + 'px;height:' + size + 'px;">' +
                '<span class="visually-hidden">Loading...</span></div>' +
                '<p class="small text-muted mt-2">加载中...</p></div>';
        }
    };

    const Skeleton = {
        render(rows, cols) {
            let html = '<div class="i8j-skeleton">';
            for (let r = 0; r < (rows || 3); r++) {
                html += '<div class="d-flex gap-2 mb-2">';
                for (let c = 0; c < (cols || 1); c++) {
                    html += '<div class="flex-fill" style="height:20px;background:#e2e8f0;border-radius:4px;animation: i8j-skeleton-pulse 1.5s infinite;"></div>';
                }
                html += '</div>';
            }
            html += '</div>';
            return html;
        }
    };

    const NotFound = {
        render(homeUrl) {
            homeUrl = homeUrl || '/';
            return '<div class="i8j-404 text-center py-5">' +
                '<h1 class="display-1 text-muted mb-2">404</h1>' +
                '<p class="text-secondary mb-3">抱歉，您访问的页面不存在或已被移除</p>' +
                '<a href="' + homeUrl + '" class="btn btn-primary"><svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align:-2px;margin-right:4px;"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/></svg>返回首页</a>' +
                '</div>';
        }
    };

    // 注入骨架屏动画样式
    if (!document.getElementById('i8j-components-style')) {
        const style = document.createElement('style');
        style.id = 'i8j-components-style';
        style.textContent = '@keyframes i8j-skeleton-pulse{0%{opacity:1}50%{opacity:.4}100%{opacity:1}}';
        document.head.appendChild(style);
    }

    window.I8JComponents = { EmptyState, LoadingSpinner, Skeleton, NotFound };
})(window);
