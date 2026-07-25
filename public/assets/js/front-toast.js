/**
 * I8JToast - 旧版兼容层
 * V3.0 Phase 2
 *
 * ⚠️ 已弃用（deprecated）：请迁移到新版 I8JToast
 *    新版路径：/assets/components/bundle/i8j-components.min.js
 *
 * 本文件保留向后兼容，内部代理到新版API。
 */
(function() {
    'use strict';

    // 检测新版是否已加载
    function ensureNewToast() {
        if (typeof window.I8JToast === 'undefined' || !window.I8JToast.show) {
            console.error('[front-toast.js] 新版I8JToast未加载，请先引入 /assets/components/bundle/i8j-components.min.js');
            return false;
        }
        return true;
    }

    // 旧版API兼容对象
    const LegacyToast = {
        _warned: false,

        _warn() {
            if (this._warned) return;
            this._warned = true;
            console.warn('[front-toast.js] 旧版I8JToast API已弃用，请迁移到新版：I8JToast.success(msg, options)');
        },

        success(message, duration) {
            this._warn();
            if (!ensureNewToast()) return;
            window.I8JToast.success(message, { duration: duration || 3000 });
        },

        error(message, duration) {
            this._warn();
            if (!ensureNewToast()) return;
            window.I8JToast.error(message, { duration: duration || 3000 });
        },

        warning(message, duration) {
            this._warn();
            if (!ensureNewToast()) return;
            window.I8JToast.warning(message, { duration: duration || 3000 });
        },

        info(message, duration) {
            this._warn();
            if (!ensureNewToast()) return;
            window.I8JToast.info(message, { duration: duration || 3000 });
        },
    };

    // 如果全局已有I8JToast（新版优先），保留新版并添加代理
    if (window.I8JToast && window.I8JToast.show) {
        window.I8JToast._legacy = LegacyToast;
    } else {
        // 新版未加载时，旧版API先占位
        window.I8JToast = LegacyToast;
    }
})();
