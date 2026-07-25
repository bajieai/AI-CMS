/**
 * I8J Skeleton 组件 - V3.0 Phase 3
 * 功能：多行/多列 + 闪烁动画
 */
class I8JSkeleton extends I8JComponent {
    static tag = 'i8j-skeleton';

    get defaults() {
        return {
            rows: 3,
            cols: 1,
            animated: true,
            avatar: false,
            title: true,
        };
    }

    init() {
        this.render();
    }

    render() {
        this.el.classList.add('i8j-skeleton');
        if (this.options.animated) this.el.classList.add('is-animated');

        let html = '';

        for (let c = 0; c < this.options.cols; c++) {
            html += '<div class="i8j-skeleton__col">';

            if (this.options.avatar) {
                html += '<div class="i8j-skeleton__avatar"></div>';
            }

            html += '<div class="i8j-skeleton__content">';

            if (this.options.title) {
                html += '<div class="i8j-skeleton__row i8j-skeleton__row--title"></div>';
            }

            for (let r = 0; r < this.options.rows; r++) {
                const width = r === this.options.rows - 1 ? '60%' : '100%';
                html += '<div class="i8j-skeleton__row" style="width:' + width + '"></div>';
            }

            html += '</div></div>';
        }

        this.el.innerHTML = html;
    }
}

I8JRegistry.register(I8JSkeleton);
