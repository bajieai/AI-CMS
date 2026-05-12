/**
 * I8J DataTable 组件 - V3.0 Phase 3
 * 功能：服务端分页 + 排序 + 筛选 + 搜索 + 选中
 */
class I8JDataTable extends I8JComponent {
    static tag = 'i8j-data-table';

    get defaults() {
        return {
            columns: [],        // [{key, label, sortable, filterable, width}]
            data: [],
            total: 0,
            page: 1,
            pageSize: 20,
            pageSizeOptions: [10, 20, 50, 100],
            selectable: false,
            search: false,
            searchPlaceholder: '搜索...',
            loading: false,
            emptyText: '暂无数据',
            rowKey: 'id',
            onRequest: null,    // function(params) => Promise<{data, total}>
        };
    }

    init() {
        this.sortKey = '';
        this.sortOrder = ''; // asc | desc
        this.searchValue = '';
        this.selectedKeys = new Set();
        this.filters = {};
        this.render();
        this.bindEvents();
        if (this.options.onRequest) this.requestData();
    }

    render() {
        this.el.classList.add('i8j-data-table');

        // 工具栏
        let toolbarHtml = '';
        if (this.options.search) {
            toolbarHtml += `<div class="i8j-data-table__search">
                <input type="text" class="i8j-data-table__search-input" placeholder="${this.options.searchPlaceholder}" value="${this.escapeHtml(this.searchValue)}">
            </div>`;
        }

        // 表头
        let headerHtml = '<thead><tr>';
        if (this.options.selectable) {
            const checked = this.isAllSelected() ? 'checked' : '';
            headerHtml += `<th class="i8j-data-table__col-select"><input type="checkbox" class="i8j-data-table__select-all" ${checked}></th>`;
        }
        this.options.columns.forEach(col => {
            let sortIcon = '';
            if (col.sortable) {
                const isActive = this.sortKey === col.key;
                const order = isActive ? this.sortOrder : '';
                sortIcon = `<span class="i8j-data-table__sort-icon is-${order}"><i class="bi bi-chevron-${order === 'asc' ? 'up' : order === 'desc' ? 'down' : 'expand'}"></i></span>`;
            }
            const width = col.width ? ` style="width:${col.width}"` : '';
            headerHtml += `<th class="${col.sortable ? 'is-sortable' : ''}" data-key="${col.key}"${width}>${this.escapeHtml(col.label)}${sortIcon}</th>`;
        });
        headerHtml += '</tr></thead>';

        // 表体
        let bodyHtml = '<tbody>';
        if (this.options.loading) {
            bodyHtml += `<tr><td colspan="${this.colCount()}" class="i8j-data-table__empty"><div class="spinner-border spinner-border-sm"></div> 加载中...</td></tr>`;
        } else if (!this.options.data.length) {
            bodyHtml += `<tr><td colspan="${this.colCount()}" class="i8j-data-table__empty">${this.options.emptyText}</td></tr>`;
        } else {
            this.options.data.forEach(row => {
                const key = row[this.options.rowKey];
                const selected = this.selectedKeys.has(key);
                bodyHtml += `<tr class="${selected ? 'is-selected' : ''}" data-key="${this.escapeHtml(String(key))}">`;
                if (this.options.selectable) {
                    bodyHtml += `<td><input type="checkbox" class="i8j-data-table__select-row" ${selected ? 'checked' : ''}></td>`;
                }
                this.options.columns.forEach(col => {
                    const val = row[col.key];
                    const cell = col.render ? col.render(val, row) : this.escapeHtml(String(val ?? ''));
                    bodyHtml += `<td>${cell}</td>`;
                });
                bodyHtml += '</tr>';
            });
        }
        bodyHtml += '</tbody>';

        // 分页
        const totalPages = Math.max(1, Math.ceil(this.options.total / this.options.pageSize));
        let pagerHtml = '';
        if (totalPages > 1) {
            pagerHtml = '<div class="i8j-data-table__pager">';
            pagerHtml += `<button class="i8j-data-table__page-btn" data-page="prev" ${this.options.page <= 1 ? 'disabled' : ''}>上一页</button>`;
            for (let p = 1; p <= totalPages; p++) {
                if (p === 1 || p === totalPages || (p >= this.options.page - 2 && p <= this.options.page + 2)) {
                    const active = p === this.options.page ? 'is-active' : '';
                    pagerHtml += `<button class="i8j-data-table__page-btn ${active}" data-page="${p}">${p}</button>`;
                } else if (p === this.options.page - 3 || p === this.options.page + 3) {
                    pagerHtml += `<span class="i8j-data-table__page-ellipsis">...</span>`;
                }
            }
            pagerHtml += `<button class="i8j-data-table__page-btn" data-page="next" ${this.options.page >= totalPages ? 'disabled' : ''}>下一页</button>`;
            pagerHtml += `<span class="i8j-data-table__pager-info">共 ${this.options.total} 条</span>`;
            pagerHtml += '</div>';
        }

        // 页大小选择
        let sizeHtml = '';
        if (this.options.pageSizeOptions.length > 0) {
            sizeHtml = '<div class="i8j-data-table__size"><select class="i8j-data-table__size-select">';
            this.options.pageSizeOptions.forEach(s => {
                sizeHtml += `<option value="${s}" ${s === this.options.pageSize ? 'selected' : ''}>${s} 条/页</option>`;
            });
            sizeHtml += '</select></div>';
        }

        this.el.innerHTML = `
            ${toolbarHtml ? '<div class="i8j-data-table__toolbar">' + toolbarHtml + '</div>' : ''}
            <div class="i8j-data-table__wrapper">
                <table class="i8j-data-table__table">${headerHtml}${bodyHtml}</table>
            </div>
            <div class="i8j-data-table__footer">${pagerHtml}${sizeHtml}</div>
        `;
    }

    bindEvents() {
        this.el.addEventListener('click', (e) => {
            const th = e.target.closest('th.is-sortable');
            if (th) this.handleSort(th.dataset.key);

            const pageBtn = e.target.closest('[data-page]');
            if (pageBtn) this.handlePage(pageBtn.dataset.page);

            const selectAll = e.target.closest('.i8j-data-table__select-all');
            if (selectAll) this.toggleSelectAll(selectAll.checked);

            const selectRow = e.target.closest('.i8j-data-table__select-row');
            if (selectRow) {
                const row = selectRow.closest('tr');
                this.toggleSelectRow(row.dataset.key, selectRow.checked);
            }
        });

        this.el.addEventListener('input', (e) => {
            if (e.target.classList.contains('i8j-data-table__search-input')) {
                this.searchValue = e.target.value;
                this.options.page = 1;
                this.debounceRequest();
            }
        });

        this.el.addEventListener('change', (e) => {
            if (e.target.classList.contains('i8j-data-table__size-select')) {
                this.options.pageSize = parseInt(e.target.value, 10);
                this.options.page = 1;
                this.requestData();
            }
        });
    }

    handleSort(key) {
        if (this.sortKey === key) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : this.sortOrder === 'desc' ? '' : 'asc';
        } else {
            this.sortKey = key;
            this.sortOrder = 'asc';
        }
        this.options.page = 1;
        this.requestData();
    }

    handlePage(page) {
        const totalPages = Math.ceil(this.options.total / this.options.pageSize);
        if (page === 'prev') page = Math.max(1, this.options.page - 1);
        else if (page === 'next') page = Math.min(totalPages, this.options.page + 1);
        else page = parseInt(page, 10);

        if (page !== this.options.page) {
            this.options.page = page;
            this.requestData();
        }
    }

    toggleSelectAll(checked) {
        if (checked) {
            this.options.data.forEach(row => this.selectedKeys.add(String(row[this.options.rowKey])));
        } else {
            this.options.data.forEach(row => this.selectedKeys.delete(String(row[this.options.rowKey])));
        }
        this.render();
        this.emit('selectionChange', Array.from(this.selectedKeys));
    }

    toggleSelectRow(key, checked) {
        checked ? this.selectedKeys.add(key) : this.selectedKeys.delete(key);
        this.render();
        this.emit('selectionChange', Array.from(this.selectedKeys));
    }

    isAllSelected() {
        if (!this.options.data.length) return false;
        return this.options.data.every(row => this.selectedKeys.has(String(row[this.options.rowKey])));
    }

    colCount() {
        return this.options.columns.length + (this.options.selectable ? 1 : 0);
    }

    debounceRequest() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => this.requestData(), 300);
    }

    async requestData() {
        if (!this.options.onRequest) return;
        this.options.loading = true;
        this.render();

        try {
            const result = await this.options.onRequest({
                page: this.options.page,
                pageSize: this.options.pageSize,
                sortKey: this.sortKey,
                sortOrder: this.sortOrder,
                search: this.searchValue,
                filters: this.filters,
            });
            this.options.data = result.data || [];
            this.options.total = result.total || 0;
        } catch (err) {
            this.emit('error', err);
        } finally {
            this.options.loading = false;
            this.render();
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    refresh() {
        this.requestData();
    }
}

I8JRegistry.register(I8JDataTable);
