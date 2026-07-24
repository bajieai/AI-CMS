/**
 * I8JPagination - 分页组件
 * V3.0 Phase 2 UI组件库
 *
 * 特性：
 * - 页码渲染（含省略缩略）
 * - 跳转输入
 * - AJAX模式
 * - 自定义每页数量
 */
class I8JPagination extends I8JComponent {
    constructor(element, options = {}) {
        super(element, options);
    }

    getDefaultOptions() {
        return {
            current: 1,
            total: 0,
            pageSize: 15,
            pageSizeOptions: [10, 15, 20, 50],
            showJumper: true,
            showSizeChanger: true,
            showTotal: true,
            onChange: null,
            onSizeChange: null,
            siblingCount: 1,
        };
    }

    render() {
        const { current, total, pageSize } = this.options;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));

        this.element.className = 'i8j-pagination';
        this.element.innerHTML = '';

        // 总记录数
        if (this.options.showTotal) {
            const totalEl = document.createElement('span');
            totalEl.className = 'i8j-pagination__total';
            totalEl.textContent = `共 ${total} 条`;
            this.element.appendChild(totalEl);
        }

        // 页码列表
        const pages = this.getPageList(current, totalPages);
        const ul = document.createElement('ul');
        ul.className = 'i8j-pagination__list';

        // 上一页
        ul.appendChild(this.createPageItem('prev', current > 1, current - 1));

        pages.forEach(p => {
            if (p === '...') {
                const li = document.createElement('li');
                li.className = 'i8j-pagination__item i8j-pagination__item--ellipsis';
                li.textContent = '...';
                ul.appendChild(li);
            } else {
                ul.appendChild(this.createPageItem(p, true, p, p === current));
            }
        });

        // 下一页
        ul.appendChild(this.createPageItem('next', current < totalPages, current + 1));

        this.element.appendChild(ul);

        // 每页数量选择
        if (this.options.showSizeChanger) {
            const sizeWrap = document.createElement('div');
            sizeWrap.className = 'i8j-pagination__size';
            const select = document.createElement('select');
            select.className = 'form-select form-select-sm';
            this.options.pageSizeOptions.forEach(size => {
                const opt = document.createElement('option');
                opt.value = size;
                opt.textContent = `${size}条/页`;
                if (size === pageSize) opt.selected = true;
                select.appendChild(opt);
            });
            sizeWrap.appendChild(select);
            this.element.appendChild(sizeWrap);
        }

        // 跳转
        if (this.options.showJumper) {
            const jumper = document.createElement('div');
            jumper.className = 'i8j-pagination__jumper';
            jumper.innerHTML = `
                <span>跳至</span>
                <input type="number" class="form-control form-control-sm" min="1" max="${totalPages}" value="${current}">
                <span>页</span>
            `;
            this.element.appendChild(jumper);
        }
    }

    createPageItem(label, enabled, page, active = false) {
        const li = document.createElement('li');
        li.className = 'i8j-pagination__item';
        if (!enabled) li.classList.add('i8j-pagination__item--disabled');
        if (active) li.classList.add('i8j-pagination__item--active');

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.disabled = !enabled;
        btn.textContent = label === 'prev' ? '‹' : label === 'next' ? '›' : String(label);
        li.appendChild(btn);

        if (enabled) {
            btn.addEventListener('click', () => this.goTo(page));
        }

        return li;
    }

    getPageList(current, total) {
        const { siblingCount } = this.options;
        const pages = [];

        // 始终显示第一页
        pages.push(1);

        const left = Math.max(2, current - siblingCount);
        const right = Math.min(total - 1, current + siblingCount);

        if (left > 2) pages.push('...');
        for (let i = left; i <= right; i++) pages.push(i);
        if (right < total - 1) pages.push('...');

        // 始终显示最后一页
        if (total > 1) pages.push(total);

        return pages;
    }

    goTo(page) {
        const { total, pageSize, onChange } = this.options;
        const totalPages = Math.ceil(total / pageSize);
        if (page < 1 || page > totalPages) return;

        this.options.current = page;
        this.render();

        if (typeof onChange === 'function') {
            onChange(page, pageSize);
        }
    }

    bindEvents() {
        // 每页数量变化
        const sizeSelect = this.element.querySelector('.i8j-pagination__size select');
        if (sizeSelect) {
            this.on(sizeSelect, 'change', (e) => {
                const newSize = parseInt(e.target.value, 10);
                this.options.pageSize = newSize;
                this.options.current = 1;
                this.render();
                if (typeof this.options.onSizeChange === 'function') {
                    this.options.onSizeChange(newSize);
                }
                if (typeof this.options.onChange === 'function') {
                    this.options.onChange(1, newSize);
                }
            });
        }

        // 跳转输入
        const jumperInput = this.element.querySelector('.i8j-pagination__jumper input');
        if (jumperInput) {
            this.on(jumperInput, 'keydown', (e) => {
                if (e.key === 'Enter') {
                    const page = parseInt(e.target.value, 10);
                    if (!isNaN(page)) this.goTo(page);
                }
            });
        }
    }
}

window.I8JPagination = I8JPagination;
