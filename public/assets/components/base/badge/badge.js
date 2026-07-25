/**
 * I8J Badge 组件 - V3.0 Phase 3
 * 功能：数值/圆点/位置/类型
 */
class I8JBadge extends I8JComponent {
    static tag = 'i8j-badge';

    get defaults() {
        return {
            value: '',
            max: 99,
            type: 'default', // default | primary | success | warning | danger
            dot: false,
            position: 'top-right', // top-right | top-left | bottom-right | bottom-left
        };
    }

    init() {
        this.render();
    }

    render() {
        this.el.classList.add('i8j-badge');

        this.badgeEl = document.createElement('span');
        this.badgeEl.className = `i8j-badge__inner i8j-badge__inner--${this.options.type} i8j-badge__inner--${this.options.position}`;

        if (this.options.dot) {
            this.badgeEl.classList.add('is-dot');
        } else {
            const val = parseInt(this.options.value, 10);
            const text = isNaN(val) ? this.options.value : (val > this.options.max ? `${this.options.max}+` : String(val));
            this.badgeEl.textContent = text;
        }

        this.el.appendChild(this.badgeEl);
    }

    setValue(value) {
        this.options.value = value;
        if (this.options.dot) return;
        const val = parseInt(value, 10);
        const text = isNaN(val) ? value : (val > this.options.max ? `${this.options.max}+` : String(val));
        this.badgeEl.textContent = text;
        this.badgeEl.classList.toggle('is-hidden', !value && value !== 0);
    }
}

I8JRegistry.register(I8JBadge);
