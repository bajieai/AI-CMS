/**
 * I8JComponents - 旧版兼容层
 * V3.0 Phase 2
 *
 * ⚠️ 已弃用（deprecated）：请迁移到新版组件库
 *    新版路径：/assets/components/bundle/i8j-components.min.js
 *
 * 本文件保留向后兼容，内部代理到新版API。
 */
(function() {
    'use strict';

    let warned = false;
    function warn() {
        if (warned) return;
        warned = true;
        console.warn('[front-components.js] 旧版I8JComponents API已弃用，请迁移到新版组件库');
    }

    // 旧版EmptyState代理
    const LegacyComponents = {
        EmptyState: class {
            constructor(options) {
                warn();
                this.options = options || {};
            }
            render() {
                const msg = this.options.message || '暂无数据';
                const el = document.createElement('div');
                el.className = 'text-center text-muted py-5';
                el.innerHTML = `<i class="bi bi-inbox fs-1 mb-2 d-block"></i>${msg}`;
                return el;
            }
        },

        NotFound: class {
            constructor(options) {
                warn();
                this.options = options || {};
            }
            render() {
                const msg = this.options.message || '页面未找到';
                const el = document.createElement('div');
                el.className = 'text-center text-muted py-5';
                el.innerHTML = `<i class="bi bi-search fs-1 mb-2 d-block"></i>${msg}`;
                return el;
            }
        },

        Skeleton: class {
            constructor(options) {
                warn();
                this.options = options || {};
            }
            render() {
                const rows = this.options.rows || 3;
                const el = document.createElement('div');
                el.className = 'i8j-skeleton';
                let html = '';
                for (let i = 0; i < rows; i++) {
                    html += `<div class="i8j-skeleton__row"></div>`;
                }
                el.innerHTML = html;
                return el;
            }
        },

        LoadingSpinner: class {
            constructor(options) {
                warn();
                this.options = options || {};
            }
            render() {
                const size = this.options.size || 'md';
                const el = document.createElement('div');
                el.className = `spinner-border spinner-border-${size}`;
                el.setAttribute('role', 'status');
                el.innerHTML = '<span class="visually-hidden">Loading...</span>';
                return el;
            }
        },
    };

    // 如果新版已加载，保留新版组件
    if (window.I8JComponent) {
        // 新版已存在，旧版作为_legacy保留
        window.I8JComponents = Object.assign({}, LegacyComponents, { _legacy: true });
    } else {
        window.I8JComponents = LegacyComponents;
    }
})();
