/**
 * I8J Tabs 组件 - V3.0 Phase 3
 * 功能：4种类型(top/left/right/bottom) + 懒加载 + URL同步 + 切换动画
 */
class I8JTabs extends I8JComponent {
    static tag = 'i8j-tabs';

    get defaults() {
        return {
            type: 'top',        // top | left | right | bottom
            lazy: false,        // 懒加载
            syncUrl: false,     // URL hash 同步
            animate: true,      // 切换动画
            active: 0,          // 默认激活索引
        };
    }

    init() {
        this.panels = [];
        this.headers = [];
        this.activeIndex = this.options.active;
        this.render();
        this.bindEvents();
        if (this.options.syncUrl) this.syncFromUrl();
        this.activate(this.activeIndex);
    }

    render() {
        const children = Array.from(this.el.children);
        const wrapper = document.createElement('div');
        wrapper.className = `i8j-tabs i8j-tabs--${this.options.type}`;

        const nav = document.createElement('div');
        nav.className = 'i8j-tabs__nav';

        const content = document.createElement('div');
        content.className = 'i8j-tabs__content';

        children.forEach((child, index) => {
            const label = child.dataset.label || `Tab ${index + 1}`;
            const tabId = child.dataset.tab || `tab-${index}`;
            const disabled = child.dataset.disabled === 'true';

            const header = document.createElement('button');
            header.className = 'i8j-tabs__tab';
            header.type = 'button';
            header.textContent = label;
            header.dataset.index = index;
            header.dataset.tab = tabId;
            if (disabled) {
                header.disabled = true;
                header.classList.add('is-disabled');
            }

            const panel = document.createElement('div');
            panel.className = 'i8j-tabs__panel';
            panel.dataset.index = index;
            panel.dataset.tab = tabId;
            panel.innerHTML = this.options.lazy ? '' : child.innerHTML;
            panel._originalHtml = child.innerHTML;

            nav.appendChild(header);
            content.appendChild(panel);
            this.headers.push(header);
            this.panels.push(panel);
        });

        wrapper.appendChild(nav);
        wrapper.appendChild(content);
        this.el.innerHTML = '';
        this.el.appendChild(wrapper);
    }

    bindEvents() {
        this.el.addEventListener('click', (e) => {
            const tab = e.target.closest('.i8j-tabs__tab');
            if (!tab || tab.disabled) return;
            this.activate(parseInt(tab.dataset.index, 10));
        });
    }

    activate(index) {
        if (index < 0 || index >= this.headers.length) return;
        if (this.headers[index].disabled) return;

        this.activeIndex = index;

        this.headers.forEach((h, i) => {
            h.classList.toggle('is-active', i === index);
        });
        this.panels.forEach((p, i) => {
            const isActive = i === index;
            p.classList.toggle('is-active', isActive);

            // 懒加载
            if (isActive && this.options.lazy && !p._loaded) {
                p.innerHTML = p._originalHtml;
                p._loaded = true;
            }

            // 动画
            if (this.options.animate && isActive) {
                p.style.opacity = '0';
                requestAnimationFrame(() => {
                    p.style.transition = 'opacity .2s ease';
                    p.style.opacity = '1';
                });
            }
        });

        if (this.options.syncUrl) {
            const tabId = this.headers[index].dataset.tab;
            location.hash = tabId;
        }

        this.emit('change', { index, tab: this.headers[index].dataset.tab });
    }

    syncFromUrl() {
        const hash = location.hash.slice(1);
        if (!hash) return;
        const idx = this.headers.findIndex(h => h.dataset.tab === hash);
        if (idx !== -1) this.activate(idx);
    }

    get activeTab() {
        return { index: this.activeIndex, tab: this.headers[this.activeIndex]?.dataset.tab };
    }
}

I8JRegistry.register(I8JTabs);
