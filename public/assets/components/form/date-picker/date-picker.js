/**
 * I8J DatePicker 组件 - V3.0 Phase 3
 * 功能：日期选择 + 范围 + 快捷选项，纯 Vanilla JS 实现（零外部依赖）
 */
class I8JDatePicker extends I8JComponent {
    static tag = 'i8j-date-picker';

    get defaults() {
        return {
            mode: 'single',     // single | range
            format: 'YYYY-MM-DD',
            placeholder: '请选择日期',
            shortcuts: [],      // ['today', 'yesterday', 'last7days', 'last30days']
            disabledDate: null, // function(date) => bool
            firstDayOfWeek: 1,  // 0=周日, 1=周一
        };
    }

    init() {
        this.value = this.options.mode === 'range' ? ['', ''] : '';
        this.viewDate = new Date();
        this.picking = null; // range模式下记录起始点
        this.isOpen = false;
        this.render();
        this.bindEvents();
    }

    render() {
        this.el.classList.add('i8j-date-picker');

        this.inputEl = document.createElement('input');
        this.inputEl.type = 'text';
        this.inputEl.className = 'i8j-date-picker__input';
        this.inputEl.placeholder = this.options.placeholder;
        this.inputEl.readOnly = true;

        this.panelEl = document.createElement('div');
        this.panelEl.className = 'i8j-date-picker__panel';
        this.panelEl.style.display = 'none';

        this.el.innerHTML = '';
        this.el.appendChild(this.inputEl);
        this.el.appendChild(this.panelEl);
    }

    bindEvents() {
        this.inputEl.addEventListener('click', () => this.toggle());
        document.addEventListener('click', (e) => {
            if (!this.el.contains(e.target)) this.close();
        });
        this.panelEl.addEventListener('click', (e) => {
            const cell = e.target.closest('.i8j-date-picker__day');
            if (cell && !cell.classList.contains('is-disabled')) {
                this.selectDate(cell.dataset.date);
            }
            const nav = e.target.closest('[data-nav]');
            if (nav) this.navigate(nav.dataset.nav);
            const shortcut = e.target.closest('.i8j-date-picker__shortcut');
            if (shortcut) this.applyShortcut(shortcut.dataset.shortcut);
        });
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.panelEl.style.display = 'block';
        this.renderCalendar();
    }

    close() {
        this.isOpen = false;
        this.panelEl.style.display = 'none';
    }

    navigate(dir) {
        if (dir === 'prev-month') this.viewDate.setMonth(this.viewDate.getMonth() - 1);
        if (dir === 'next-month') this.viewDate.setMonth(this.viewDate.getMonth() + 1);
        if (dir === 'prev-year') this.viewDate.setFullYear(this.viewDate.getFullYear() - 1);
        if (dir === 'next-year') this.viewDate.setFullYear(this.viewDate.getFullYear() + 1);
        this.renderCalendar();
    }

    renderCalendar() {
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();

        let html = '<div class="i8j-date-picker__header">';
        html += '<button data-nav="prev-year" type="button">&laquo;</button>';
        html += '<button data-nav="prev-month" type="button">&lsaquo;</button>';
        html += '<span>' + year + '年' + (month + 1) + '月</span>';
        html += '<button data-nav="next-month" type="button">&rsaquo;</button>';
        html += '<button data-nav="next-year" type="button">&raquo;</button>';
        html += '</div>';

        // 快捷选项
        if (this.options.shortcuts.length > 0) {
            html += '<div class="i8j-date-picker__shortcuts">';
            const labels = { today: '今天', yesterday: '昨天', last7days: '近7天', last30days: '近30天' };
            this.options.shortcuts.forEach(s => {
                html += '<span class="i8j-date-picker__shortcut" data-shortcut="' + s + '">' + (labels[s] || s) + '</span>';
            });
            html += '</div>';
        }

        // 星期头
        const weekDays = ['日', '一', '二', '三', '四', '五', '六'];
        const start = this.options.firstDayOfWeek;
        html += '<div class="i8j-date-picker__weekdays">';
        for (let i = 0; i < 7; i++) {
            html += '<span>' + weekDays[(start + i) % 7] + '</span>';
        }
        html += '</div>';

        // 日期格子
        const firstDay = new Date(year, month, 1);
        const startOffset = (firstDay.getDay() - start + 7) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const prevDays = new Date(year, month, 0).getDate();

        html += '<div class="i8j-date-picker__days">';
        // 上月
        for (let i = startOffset - 1; i >= 0; i--) {
            html += '<span class="i8j-date-picker__day is-prev-month">' + (prevDays - i) + '</span>';
        }
        // 当月
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = this.formatDate(new Date(year, month, d));
            const cellDate = new Date(year, month, d);
            let cls = 'i8j-date-picker__day';
            if (this.isSelected(dateStr)) cls += ' is-selected';
            if (this.isInRange(dateStr)) cls += ' is-in-range';
            if (this.isDisabled(cellDate)) cls += ' is-disabled';
            if (this.isToday(cellDate)) cls += ' is-today';
            html += '<span class="' + cls + '" data-date="' + dateStr + '">' + d + '</span>';
        }
        // 下月
        const totalCells = startOffset + daysInMonth;
        const nextCells = (7 - (totalCells % 7)) % 7;
        for (let d = 1; d <= nextCells; d++) {
            html += '<span class="i8j-date-picker__day is-next-month">' + d + '</span>';
        }
        html += '</div>';

        this.panelEl.innerHTML = html;
    }

    selectDate(dateStr) {
        if (this.options.mode === 'range') {
            if (!this.picking || this.picking === 'end') {
                this.value = [dateStr, ''];
                this.picking = 'start';
                this.renderCalendar();
            } else {
                if (dateStr < this.value[0]) {
                    this.value = [dateStr, this.value[0]];
                } else {
                    this.value[1] = dateStr;
                }
                this.picking = null;
                this.inputEl.value = this.value[0] + ' ~ ' + this.value[1];
                this.emit('change', { value: this.value });
                this.renderCalendar();
                if (this.value[1]) this.close();
            }
        } else {
            this.value = dateStr;
            this.inputEl.value = dateStr;
            this.emit('change', { value: dateStr });
            this.renderCalendar();
            this.close();
        }
    }

    applyShortcut(key) {
        const today = new Date();
        const fmt = (d) => this.formatDate(d);
        let start, end;

        if (key === 'today') {
            start = end = today;
        } else if (key === 'yesterday') {
            start = end = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
        } else if (key === 'last7days') {
            start = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6);
            end = today;
        } else if (key === 'last30days') {
            start = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29);
            end = today;
        }

        if (this.options.mode === 'range') {
            this.value = [fmt(start), fmt(end)];
            this.inputEl.value = this.value[0] + ' ~ ' + this.value[1];
            this.emit('change', { value: this.value });
        } else {
            this.value = fmt(start);
            this.inputEl.value = this.value;
            this.emit('change', { value: this.value });
        }
        this.close();
    }

    formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return this.options.format.replace('YYYY', y).replace('MM', m).replace('DD', d);
    }

    parseDate(str) {
        const parts = str.match(/(\d{4})-(\d{2})-(\d{2})/);
        if (!parts) return null;
        return new Date(+parts[1], +parts[2] - 1, +parts[3]);
    }

    isSelected(dateStr) {
        if (this.options.mode === 'range') return this.value[0] === dateStr || this.value[1] === dateStr;
        return this.value === dateStr;
    }

    isInRange(dateStr) {
        if (this.options.mode !== 'range' || !this.value[0] || !this.value[1]) return false;
        return dateStr > this.value[0] && dateStr < this.value[1];
    }

    isDisabled(date) {
        if (typeof this.options.disabledDate === 'function') {
            return this.options.disabledDate(date);
        }
        return false;
    }

    isToday(date) {
        const t = new Date();
        return date.getFullYear() === t.getFullYear() && date.getMonth() === t.getMonth() && date.getDate() === t.getDate();
    }
}

I8JRegistry.register(I8JDatePicker);
