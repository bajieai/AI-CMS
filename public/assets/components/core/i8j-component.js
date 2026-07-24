/**
 * I8JComponent - 组件基类
 * V3.0 Phase 2 UI组件库
 *
 * 职责：
 * - 生命周期管理（mount/update/destroy）
 * - CSS变量读取
 * - 事件绑定/解绑
 * - 注册表关联
 */
class I8JComponent {
    /**
     * @param {HTMLElement} element - 组件挂载的根元素
     * @param {Object} options - 组件配置
     */
    constructor(element, options = {}) {
        if (!element || !(element instanceof HTMLElement)) {
            throw new TypeError('I8JComponent requires a valid HTMLElement');
        }

        this.element = element;
        this.options = Object.assign({}, this.getDefaultOptions(), options);
        this.mounted = false;
        this.destroyed = false;
        this.eventListeners = [];
        this._animationFrame = null;

        // 注册到全局注册表
        if (window.I8JRegistry) {
            window.I8JRegistry.register(this);
        }
    }

    /**
     * 获取默认配置（子类覆盖）
     */
    getDefaultOptions() {
        return {};
    }

    /**
     * 挂载组件
     */
    mount() {
        if (this.mounted || this.destroyed) return this;
        this.mounted = true;
        this.render();
        this.bindEvents();
        this.onMount();
        return this;
    }

    /**
     * 渲染组件（子类必须覆盖）
     */
    render() {
        throw new Error('render() must be implemented by subclass');
    }

    /**
     * 绑定事件（子类可覆盖）
     */
    bindEvents() {
        // 子类实现
    }

    /**
     * 解绑所有事件
     */
    unbindEvents() {
        this.eventListeners.forEach(({ element, type, handler, options }) => {
            element.removeEventListener(type, handler, options);
        });
        this.eventListeners = [];
    }

    /**
     * 注册事件监听器（自动追踪以便销毁时清理）
     */
    on(element, type, handler, options = false) {
        element.addEventListener(type, handler, options);
        this.eventListeners.push({ element, type, handler, options });
        return this;
    }

    /**
     * 生命周期：挂载完成
     */
    onMount() {
        // 子类可覆盖
    }

    /**
     * 更新组件（触发重新渲染）
     */
    update(options = {}) {
        if (!this.mounted || this.destroyed) return this;
        Object.assign(this.options, options);
        this.render();
        this.onUpdate();
        return this;
    }

    /**
     * 生命周期：更新完成
     */
    onUpdate() {
        // 子类可覆盖
    }

    /**
     * 销毁组件
     */
    destroy() {
        if (this.destroyed) return;
        this.destroyed = true;
        this.mounted = false;

        this.unbindEvents();
        this.onDestroy();

        if (this._animationFrame) {
            cancelAnimationFrame(this._animationFrame);
        }

        // 从注册表移除
        if (window.I8JRegistry) {
            window.I8JRegistry.unregister(this);
        }

        // 清理DOM引用
        this.element = null;
    }

    /**
     * 生命周期：销毁前
     */
    onDestroy() {
        // 子类可覆盖
    }

    /**
     * 读取CSS变量值
     */
    getCssVar(name, fallback = '') {
        if (!this.element) return fallback;
        const value = getComputedStyle(this.element).getPropertyValue(name).trim();
        return value || fallback;
    }

    /**
     * 设置CSS变量值
     */
    setCssVar(name, value) {
        if (this.element) {
            this.element.style.setProperty(name, value);
        }
        return this;
    }

    /**
     * 查找组件内的元素
     */
    $(selector) {
        return this.element.querySelector(selector);
    }

    /**
     * 查找组件内的所有元素
     */
    $$(selector) {
        return Array.from(this.element.querySelectorAll(selector));
    }

    /**
     * 生成唯一ID
     */
    static uid(prefix = 'i8j') {
        return prefix + '-' + Math.random().toString(36).slice(2, 9);
    }
}

// 暴露到全局
window.I8JComponent = I8JComponent;
