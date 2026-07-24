/**
 * I8JToast - Toast通知组件
 * V3.0 Phase 2 UI组件库
 *
 * 特性：
 * - 4种类型（success/error/warning/info）
 * - 自动消失+进度条
 * - 队列管理（避免重叠）
 * - 位置选项（top-center/top-right/bottom-center/bottom-right）
 * - 旧API兼容（I8JToast.success/error/warning/info）
 */
class I8JToast extends I8JComponent {
    static container = null;
    static queue = [];
    static maxVisible = 5;
    static defaultDuration = 3000;

    constructor(element, options = {}) {
        super(element, options);
    }

    getDefaultOptions() {
        return {
            type: 'info',
            message: '',
            duration: I8JToast.defaultDuration,
            position: 'top-center',
            closable: true,
        };
    }

    render() {
        const { type, message, closable } = this.options;
        const icons = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill',
        };
        const icon = icons[type] || icons.info;

        this.element.className = `i8j-toast i8j-toast--${type}`;
        this.element.innerHTML = `
            <div class="i8j-toast__icon"><i class="bi ${icon}"></i></div>
            <div class="i8j-toast__message">${this.escapeHtml(message)}</div>
            ${closable ? '<button class="i8j-toast__close"><i class="bi bi-x-lg"></i></button>' : ''}
            <div class="i8j-toast__progress"></div>
        `;
    }

    bindEvents() {
        const closeBtn = this.$('.i8j-toast__close');
        if (closeBtn) {
            this.on(closeBtn, 'click', () => this.dismiss());
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    dismiss() {
        if (this.destroyed) return;
        this.element.classList.add('i8j-toast--leaving');
        setTimeout(() => this.destroy(), 300);
    }

    onMount() {
        // 启动自动消失计时器
        const { duration } = this.options;
        const progressEl = this.$('.i8j-toast__progress');
        if (progressEl) {
            progressEl.style.animationDuration = `${duration}ms`;
        }

        setTimeout(() => this.dismiss(), duration);
    }

    // ═══════════════════════════════════════════════
    //  静态方法（工厂模式）
    // ═══════════════════════════════════════════════

    static initContainer(position) {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = `i8j-toast-container i8j-toast-container--${position}`;
            document.body.appendChild(this.container);
        }
        return this.container;
    }

    static show(message, type = 'info', options = {}) {
        const position = options.position || 'top-center';
        const container = this.initContainer(position);

        const el = document.createElement('div');
        container.appendChild(el);

        const toast = new I8JToast(el, {
            type,
            message,
            ...options,
        });

        toast.mount();

        // 限制同时显示的Toast数量
        const visible = container.querySelectorAll('.i8j-toast:not(.i8j-toast--leaving)');
        if (visible.length > this.maxVisible) {
            visible[0].classList.add('i8j-toast--leaving');
            setTimeout(() => {
                const t = window.I8JRegistry.findByElement(visible[0]);
                if (t) t.destroy();
            }, 300);
        }

        return toast;
    }

    static success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    static error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    static warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    static info(message, options = {}) {
        return this.show(message, 'info', options);
    }
}

// 旧API兼容层
if (window.I8JToast && window.I8JToast.success) {
    // 已有旧版I8JToast，保留并代理到新API
    const oldToast = window.I8JToast;
    window.I8JToast = I8JToast;
    window.I8JToast._legacy = oldToast;
    console.warn('[I8JToast] 旧版API已弃用，请迁移到新版I8JToast.success()等静态方法');
} else {
    window.I8JToast = I8JToast;
}
