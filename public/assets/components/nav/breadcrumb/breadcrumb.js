/**
 * I8J Breadcrumb 组件 - V3.0 Phase 3
 * 功能：折叠 + 自定义分隔符
 */
class I8JBreadcrumb extends I8JComponent {
    static tag = 'i8j-breadcrumb';

    get defaults() {
        return {
            separator: '/',
            maxItems: 0, // 0=不折叠
        };
    }

    init() {
        this.render();
    }

    render() {
        this.el.classList.add('i8j-breadcrumb');
        const items = Array.from(this.el.children);
        const total = items.length;
        const max = this.options.maxItems;

        this.el.innerHTML = '';

        let visibleItems = items;
        let hasCollapse = false;

        if (max > 0 && total > max) {
            hasCollapse = true;
            visibleItems = [items[0], items[total - 1]];
        }

        visibleItems.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'i8j-breadcrumb__item';
            if (item.dataset.href) {
                const a = document.createElement('a');
                a.href = item.dataset.href;
                a.textContent = item.textContent;
                li.appendChild(a);
            } else {
                li.textContent = item.textContent;
                li.classList.add('is-active');
            }

            if (hasCollapse && index === 0) {
                const collapse = document.createElement('li');
                collapse.className = 'i8j-breadcrumb__item i8j-breadcrumb__collapse';
                collapse.innerHTML = '<span class="i8j-breadcrumb__ellipsis" title="点击展开">...</span>';
                collapse.querySelector('.i8j-breadcrumb__ellipsis').addEventListener('click', () => {
                    this.options.maxItems = 0;
                    this.render();
                });
                this.el.appendChild(li);
                this.el.appendChild(this.createSeparator());
                this.el.appendChild(collapse);
                this.el.appendChild(this.createSeparator());
            } else {
                this.el.appendChild(li);
                if (index < visibleItems.length - 1) {
                    this.el.appendChild(this.createSeparator());
                }
            }
        });
    }

    createSeparator() {
        const sep = document.createElement('li');
        sep.className = 'i8j-breadcrumb__separator';
        sep.innerHTML = this.options.separator;
        return sep;
    }
}

I8JRegistry.register(I8JBreadcrumb);
