/**
 * I8J Progress 组件 - V3.0 Phase 3
 * 功能：进度条 + 类型 + 条纹 + 动画
 */
class I8JProgress extends I8JComponent {
    static tag = 'i8j-progress';

    get defaults() {
        return {
            value: 0,       // 0-100
            max: 100,
            type: 'default', // default | success | warning | danger
            striped: false,
            animated: false,
            showLabel: true,
            height: 8,
        };
    }

    init() {
        this.render();
        this.setValue(this.options.value);
    }

    render() {
        this.el.classList.add('i8j-progress');
        this.el.style.setProperty('--progress-height', this.options.height + 'px');

        this.trackEl = document.createElement('div');
        this.trackEl.className = 'i8j-progress__track';

        this.barEl = document.createElement('div');
        this.barEl.className = `i8j-progress__bar i8j-progress__bar--${this.options.type}`;
        if (this.options.striped) this.barEl.classList.add('is-striped');
        if (this.options.animated) this.barEl.classList.add('is-animated');

        this.labelEl = document.createElement('span');
        this.labelEl.className = 'i8j-progress__label';

        this.trackEl.appendChild(this.barEl);
        this.el.innerHTML = '';
        this.el.appendChild(this.trackEl);
        if (this.options.showLabel) this.el.appendChild(this.labelEl);
    }

    setValue(value) {
        this.options.value = Math.max(0, Math.min(value, this.options.max));
        const pct = this.options.max > 0 ? (this.options.value / this.options.max * 100) : 0;
        this.barEl.style.width = pct + '%';
        if (this.labelEl) {
            this.labelEl.textContent = Math.round(pct) + '%';
        }
        this.emit('change', { value: this.options.value, percent: pct });
    }

    setType(type) {
        this.barEl.className = this.barEl.className.replace(/i8j-progress__bar--\w+/, `i8j-progress__bar--${type}`);
    }
}

I8JRegistry.register(I8JProgress);
