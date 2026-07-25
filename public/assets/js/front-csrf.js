/**
 * V2.9.5 前台CSRF自动注入
 * 1. 为所有POST表单自动注入 __token__ 隐藏字段
 * 2. 为所有AJAX请求自动添加 X-CSRF-TOKEN Header
 * 3. 读取 <meta name="csrf-token"> 中的Token
 */
(function () {
    'use strict';

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // 1. 表单自动注入
    function injectFormTokens() {
        var token = getCsrfToken();
        if (!token) return;
        document.querySelectorAll('form[method="post"], form:not([method])').forEach(function (form) {
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') return;
            // 避免重复注入
            if (form.querySelector('input[name="__token__"]')) return;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '__token__';
            input.value = token;
            form.appendChild(input);
        });
    }

    // 页面加载时注入
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectFormTokens);
    } else {
        injectFormTokens();
    }

    // 动态表单注入（MutationObserver）
    if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            var shouldInject = false;
            mutations.forEach(function (m) {
                if (m.addedNodes.length) shouldInject = true;
            });
            if (shouldInject) injectFormTokens();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // 2. jQuery AJAX自动添加Header
    if (window.jQuery) {
        jQuery.ajaxSetup({
            beforeSend: function (xhr) {
                var token = getCsrfToken();
                if (token) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', token);
                }
            }
        });
    }

    // 3. fetch API 拦截
    var originalFetch = window.fetch;
    window.fetch = function (url, options) {
        options = options || {};
        var token = getCsrfToken();
        if (token) {
            options.headers = options.headers || {};
            if (typeof options.headers === 'object' && !(options.headers instanceof Headers)) {
                options.headers['X-CSRF-TOKEN'] = token;
            } else if (options.headers instanceof Headers) {
                options.headers.set('X-CSRF-TOKEN', token);
            }
        }
        return originalFetch.call(window, url, options);
    };

    // 4. XMLHttpRequest 拦截
    var originalOpen = XMLHttpRequest.prototype.open;
    var originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function () {
        this._method = arguments[0];
        return originalOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
        var token = getCsrfToken();
        if (token && this._method && this._method.toLowerCase() !== 'get') {
            this.setRequestHeader('X-CSRF-TOKEN', token);
        }
        return originalSend.apply(this, arguments);
    };
})();
