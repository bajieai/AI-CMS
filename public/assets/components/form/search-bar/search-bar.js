/**
 * I8JSearchBar - 搜索条组件
 * V3.0 Phase 2 UI组件库
 *
 * 特性：
 * - debounce防抖
 * - Enter触发搜索
 * - 清空按钮
 * - 搜索历史（可选）
 * - 聚焦高亮
 */
class I8JSearchBar extends I8JComponent {
    constructor(element, options = {}) {
        super(element, options);
    }

    getDefaultOptions() {
        return {
            placeholder: '搜索...',
            debounce: 300,
            showHistory: false,
            maxHistory: 5,
            onSearch: null,
            onClear: null,
        };
    }

    render() {
        const { placeholder } = this.options;

        this.element.className = 'i8j-search-bar';
        this.element.innerHTML = `
            <div class="i8j-search-bar__wrapper">
                <i class="bi bi-search i8j-search-bar__icon"></i>
                <input type="text" class="i8j-search-bar__input" placeholder="${this.escapeHtml(placeholder)}" autocomplete="off">
                <button type="button" class="i8j-search-bar__clear" style="display:none;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </div>
            <div class="i8j-search-bar__history" style="display:none;"></div>
        `;
    }

    bindEvents() {
        const input = this.$('.i8j-search-bar__input');
        const clearBtn = this.$('.i8j-search-bar__clear');

        let debounceTimer = null;

        this.on(input, 'input', () => {
            const value = input.value.trim();
            clearBtn.style.display = value ? 'flex' : 'none';

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (typeof this.options.onSearch === 'function') {
                    this.options.onSearch(value);
                }
            }, this.options.debounce);
        });

        this.on(input, 'keydown', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(debounceTimer);
                const value = input.value.trim();
                if (value) this.addHistory(value);
                if (typeof this.options.onSearch === 'function') {
                    this.options.onSearch(value);
                }
            }
        });

        this.on(clearBtn, 'click', () => {
            input.value = '';
            clearBtn.style.display = 'none';
            input.focus();
            if (typeof this.options.onClear === 'function') {
                this.options.onClear();
            }
        });

        // 历史记录
        if (this.options.showHistory) {
            this.on(input, 'focus', () => this.showHistory());
            this.on(input, 'blur', () => {
                setTimeout(() => this.hideHistory(), 200);
            });
        }
    }

    get value() {
        const input = this.$('.i8j-search-bar__input');
        return input ? input.value.trim() : '';
    }

    set value(val) {
        const input = this.$('.i8j-search-bar__input');
        if (input) {
            input.value = val;
            const clearBtn = this.$('.i8j-search-bar__clear');
            if (clearBtn) clearBtn.style.display = val ? 'flex' : 'none';
        }
    }

    focus() {
        const input = this.$('.i8j-search-bar__input');
        if (input) input.focus();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    getHistory() {
        try {
            const raw = localStorage.getItem('i8j_search_history');
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    saveHistory(list) {
        try {
            localStorage.setItem('i8j_search_history', JSON.stringify(list.slice(0, this.options.maxHistory)));
        } catch (e) {}
    }

    addHistory(keyword) {
        if (!keyword) return;
        const list = this.getHistory().filter(k => k !== keyword);
        list.unshift(keyword);
        this.saveHistory(list);
    }

    showHistory() {
        const list = this.getHistory();
        const historyEl = this.$('.i8j-search-bar__history');
        if (!historyEl || list.length === 0) return;

        historyEl.innerHTML = list.map(k => `
            <div class="i8j-search-bar__history-item" data-keyword="${this.escapeHtml(k)}">
                <i class="bi bi-clock-history me-2"></i>${this.escapeHtml(k)}
            </div>
        `).join('');

        historyEl.querySelectorAll('.i8j-search-bar__history-item').forEach(item => {
            item.addEventListener('click', () => {
                this.value = item.dataset.keyword;
                if (typeof this.options.onSearch === 'function') {
                    this.options.onSearch(this.value);
                }
            });
        });

        historyEl.style.display = 'block';
    }

    hideHistory() {
        const historyEl = this.$('.i8j-search-bar__history');
        if (historyEl) historyEl.style.display = 'none';
    }
}

window.I8JSearchBar = I8JSearchBar;
