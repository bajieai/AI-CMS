/**
 * I8J Dropdown 组件 - V3.0 Phase 3
 * 功能：点击/悬停触发 + 多级菜单 + 外部关闭 + 键盘导航
 */
class I8JDropdown extends I8JComponent {
    static tag = 'i8j-dropdown';

    get defaults() {
        return {
            trigger: 'click',   // click | hover
            placement: 'bottom-start', // bottom-start | bottom-end | top-start | top-end
            closeOnClick: true,
            closeOnOutside: true,
            disabled: false,
        };
    }

    init() {
        this.isOpen = false;
        this.render();
        this.bindEvents();
    }

    render() {
        const triggerSlot = this.el.querySelector('[slot="trigger"]') || this.el.children[0];
        const menuSlot = this.el.querySelector('[slot="menu"]') || this.el.children[1];

        this.el.classList.add('i8j-dropdown');

        this.triggerEl = document.createElement('div');
        this.triggerEl.className = 'i8j-dropdown__trigger';
        if (triggerSlot) this.triggerEl.appendChild(triggerSlot);

        this.menuEl = document.createElement('div');
        this.menuEl.className = `i8j-dropdown__menu i8j-dropdown__menu--${this.options.placement}`;
        if (menuSlot) this.menuEl.appendChild(menuSlot);

        this.el.innerHTML = '';
        this.el.appendChild(this.triggerEl);
        this.el.appendChild(this.menuEl);
    }

    bindEvents() {
        if (this.options.trigger === 'hover') {
            this.el.addEventListener('mouseenter', () => this.open());
            this.el.addEventListener('mouseleave', () => this.close());
        } else {
            this.triggerEl.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggle();
            });
        }

        if (this.options.closeOnClick) {
            this.menuEl.addEventListener('click', (e) => {
                const item = e.target.closest('.i8j-dropdown__item');
                if (item && !item.classList.contains('is-disabled')) {
                    this.emit('select', { value: item.dataset.value, el: item });
                    if (!item.dataset.keepOpen) this.close();
                }
            });
        }

        if (this.options.closeOnOutside) {
            document.addEventListener('click', (e) => {
                if (this.isOpen && !this.el.contains(e.target)) this.close();
            });
        }

        // 键盘导航
        this.el.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;
            const items = Array.from(this.menuEl.querySelectorAll('.i8j-dropdown__item:not(.is-disabled)'));
            const current = this.menuEl.querySelector('.i8j-dropdown__item.is-focus');
            let idx = items.indexOf(current);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                idx = (idx + 1) % items.length;
                this.focusItem(items[idx]);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                idx = idx <= 0 ? items.length - 1 : idx - 1;
                this.focusItem(items[idx]);
            } else if (e.key === 'Enter' && current) {
                e.preventDefault();
                current.click();
            } else if (e.key === 'Escape') {
                this.close();
            }
        });
    }

    focusItem(item) {
        this.menuEl.querySelectorAll('.i8j-dropdown__item.is-focus').forEach(el => el.classList.remove('is-focus'));
        if (item) item.classList.add('is-focus');
    }

    open() {
        if (this.options.disabled) return;
        this.isOpen = true;
        this.el.classList.add('is-open');
        this.emit('open');
    }

    close() {
        this.isOpen = false;
        this.el.classList.remove('is-open');
        this.emit('close');
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }
}

I8JRegistry.register(I8JDropdown);
