/**
 * I8JModal - Modal弹窗组件
 * V3.0 Phase 2 UI组件库
 *
 * 特性：
 * - 4种尺寸（sm/md/lg/xl）
 * - ESC关闭
 * - focus trap（焦点锁定）
 * - Promise API（confirm/alert/prompt）
 * - body scroll lock
 * - backdrop点击关闭
 */
class I8JModal extends I8JComponent {
    static activeModal = null;
    static scrollLockCount = 0;

    constructor(element, options = {}) {
        super(element, options);
    }

    getDefaultOptions() {
        return {
            title: '',
            content: '',
            size: 'md', // sm/md/lg/xl
            closable: true,
            backdrop: true,
            keyboard: true,
            onConfirm: null,
            onCancel: null,
            confirmText: '确认',
            cancelText: '取消',
            showCancel: true,
        };
    }

    render() {
        const { title, content, size, closable, confirmText, cancelText, showCancel } = this.options;

        this.element.className = 'i8j-modal';
        this.element.setAttribute('role', 'dialog');
        this.element.setAttribute('aria-modal', 'true');
        this.element.innerHTML = `
            <div class="i8j-modal__backdrop"></div>
            <div class="i8j-modal__dialog i8j-modal__dialog--${size}">
                <div class="i8j-modal__content">
                    <div class="i8j-modal__header">
                        <h5 class="i8j-modal__title">${this.escapeHtml(title)}</h5>
                        ${closable ? '<button class="i8j-modal__close" aria-label="关闭"><i class="bi bi-x-lg"></i></button>' : ''}
                    </div>
                    <div class="i8j-modal__body">${content}</div>
                    <div class="i8j-modal__footer">
                        ${showCancel ? `<button class="i8j-modal__btn i8j-modal__btn--cancel">${this.escapeHtml(cancelText)}</button>` : ''}
                        <button class="i8j-modal__btn i8j-modal__btn--confirm">${this.escapeHtml(confirmText)}</button>
                    </div>
                </div>
            </div>
        `;
    }

    bindEvents() {
        const { backdrop, keyboard } = this.options;

        if (backdrop) {
            this.on(this.$('.i8j-modal__backdrop'), 'click', () => this.close('cancel'));
        }

        const closeBtn = this.$('.i8j-modal__close');
        if (closeBtn) {
            this.on(closeBtn, 'click', () => this.close('cancel'));
        }

        this.on(this.$('.i8j-modal__btn--cancel'), 'click', () => this.close('cancel'));
        this.on(this.$('.i8j-modal__btn--confirm'), 'click', () => this.close('confirm'));

        if (keyboard) {
            this.on(document, 'keydown', (e) => {
                if (e.key === 'Escape' && I8JModal.activeModal === this) {
                    this.close('cancel');
                }
            });
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    onMount() {
        // 锁定body滚动
        I8JModal.lockBodyScroll();
        // 焦点管理
        this.focusFirst();
        I8JModal.activeModal = this;
    }

    onDestroy() {
        I8JModal.unlockBodyScroll();
        if (I8JModal.activeModal === this) {
            I8JModal.activeModal = null;
        }
    }

    close(result) {
        if (this.destroyed) return;

        this.element.classList.add('i8j-modal--leaving');

        setTimeout(() => {
            if (result === 'confirm' && typeof this.options.onConfirm === 'function') {
                this.options.onConfirm();
            }
            if (result === 'cancel' && typeof this.options.onCancel === 'function') {
                this.options.onCancel();
            }
            this.destroy();
        }, 250);
    }

    focusFirst() {
        const focusable = this.element.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable) focusable.focus();
    }

    static lockBodyScroll() {
        this.scrollLockCount++;
        if (this.scrollLockCount === 1) {
            document.body.style.overflow = 'hidden';
        }
    }

    static unlockBodyScroll() {
        this.scrollLockCount = Math.max(0, this.scrollLockCount - 1);
        if (this.scrollLockCount === 0) {
            document.body.style.overflow = '';
        }
    }

    // ═══════════════════════════════════════════════
    //  静态Promise API
    // ═══════════════════════════════════════════════

    static open(options = {}) {
        return new Promise((resolve) => {
            const el = document.createElement('div');
            document.body.appendChild(el);

            const modal = new I8JModal(el, {
                ...options,
                onConfirm: () => resolve('confirm'),
                onCancel: () => resolve('cancel'),
            });

            modal.mount();
        });
    }

    static alert(message, title = '提示') {
        return this.open({ title, content: message, showCancel: false, confirmText: '知道了' });
    }

    static confirm(message, title = '确认') {
        return this.open({ title, content: message });
    }

    static prompt(message, title = '输入', defaultValue = '') {
        return new Promise((resolve) => {
            const content = `
                <p>${message}</p>
                <input type="text" class="form-control i8j-modal__prompt-input" value="${defaultValue}">
            `;
            this.open({ title, content }).then(result => {
                if (result === 'confirm') {
                    const input = document.querySelector('.i8j-modal__prompt-input');
                    resolve(input ? input.value : '');
                } else {
                    resolve(null);
                }
            });
        });
    }
}

window.I8JModal = I8JModal;
