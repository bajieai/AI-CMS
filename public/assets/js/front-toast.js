/**
 * V2.9.5 前台统一Toast组件
 * 轻量级，零依赖，支持PC和Mobile
 */
(function (window) {
    'use strict';

    const Toast = {
        container: null,

        ensureContainer() {
            if (this.container) return this.container;
            const el = document.createElement('div');
            el.id = 'i8j-toast-container';
            el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
            document.body.appendChild(el);
            this.container = el;
            return el;
        },

        show(message, type = 'info', duration = 3000) {
            const container = this.ensureContainer();
            const el = document.createElement('div');

            const icons = {
                success: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>',
                error: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/></svg>',
                warning: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
                info: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>',
            };

            const colors = {
                success: '#198754',
                error: '#dc3545',
                warning: '#f59e0b',
                info: '#3b82f6',
            };

            el.style.cssText = 'pointer-events:auto;display:flex;align-items:center;gap:10px;padding:12px 16px;background:#fff;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);color:#333;min-width:200px;max-width:320px;font-size:14px;transform:translateX(120%);opacity:0;transition:all .3s ease;border-left:4px solid ' + colors[type] + ';';
            el.innerHTML = '<span style="color:' + colors[type] + ';flex-shrink:0;">' + (icons[type] || icons.info) + '</span><span style="flex:1;line-height:1.4;">' + message + '</span>';

            container.appendChild(el);

            // 动画进入
            requestAnimationFrame(() => {
                el.style.transform = 'translateX(0)';
                el.style.opacity = '1';
            });

            // 自动移除
            setTimeout(() => {
                el.style.transform = 'translateX(120%)';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }, duration);

            return el;
        },

        success(msg, duration) { return this.show(msg, 'success', duration); },
        error(msg, duration) { return this.show(msg, 'error', duration); },
        warning(msg, duration) { return this.show(msg, 'warning', duration); },
        info(msg, duration) { return this.show(msg, 'info', duration); },
    };

    window.I8JToast = Toast;
})(window);
