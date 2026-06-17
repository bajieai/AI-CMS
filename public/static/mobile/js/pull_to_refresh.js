/**
 * V2.9.23 E-3: 移动端下拉刷新组件
 * 支持列表页下拉刷新，与现有无限滚动兼容
 */
(function (window) {
    'use strict';

    function PullToRefresh(options) {
        this.container = typeof options.container === 'string'
            ? document.querySelector(options.container)
            : options.container;
        this.onRefresh = options.onRefresh || function () {};
        this.threshold = options.threshold || 80;
        this.maxPull = options.maxPull || 120;
        this.refreshText = options.refreshText || '下拉刷新';
        this.releaseText = options.releaseText || '释放刷新';
        this.loadingText = options.loadingText || '加载中...';
        this.successText = options.successText || '刷新成功';
        this.errorText = options.errorText || '刷新失败';
        this.duration = options.duration || 300;

        this.state = 'idle'; // idle | pulling | releasing | loading
        this.startY = 0;
        this.currentY = 0;
        this.pullDistance = 0;

        this.init();
    }

    PullToRefresh.prototype.init = function () {
        if (!this.container) return;

        // iOS Safari 回弹微调：限制默认触摸行为，由组件自行管理
        this.container.style.touchAction = 'pan-y';
        this.container.style.overscrollBehaviorY = 'contain';

        this.createIndicator();
        this.bindEvents();
    };

    PullToRefresh.prototype.createIndicator = function () {
        this.indicator = document.createElement('div');
        this.indicator.className = 'pull-to-refresh-indicator';
        this.indicator.style.cssText = [
            'position: absolute',
            'top: 0',
            'left: 0',
            'right: 0',
            'height: 0',
            'overflow: hidden',
            'display: flex',
            'align-items: center',
            'justify-content: center',
            'background: #f8f9fa',
            'color: #888',
            'font-size: 13px',
            'transition: height ' + this.duration + 'ms ease',
            'z-index: 10'
        ].join(';');

        this.indicator.innerHTML = [
            '<span class="ptr-icon" style="margin-right:6px;font-size:16px;">↓</span>',
            '<span class="ptr-text">' + this.refreshText + '</span>'
        ].join('');

        // 确保容器有相对定位
        var containerStyle = window.getComputedStyle(this.container);
        if (containerStyle.position === 'static') {
            this.container.style.position = 'relative';
        }
        this.container.insertBefore(this.indicator, this.container.firstChild);
    };

    PullToRefresh.prototype.bindEvents = function () {
        var self = this;

        this.container.addEventListener('touchstart', function (e) {
            if (self.state === 'loading') return;
            // 仅在顶部时触发
            if (self.container.scrollTop > 0) return;
            self.startY = e.touches[0].clientY;
            self.state = 'idle';
        }, { passive: true });

        this.container.addEventListener('touchmove', function (e) {
            if (self.state === 'loading') return;
            if (self.container.scrollTop > 0) return;
            if (!self.startY) return;

            self.currentY = e.touches[0].clientY;
            self.pullDistance = Math.max(0, self.currentY - self.startY);

            if (self.pullDistance > 0) {
                e.preventDefault();
                var height = Math.min(self.pullDistance, self.maxPull);
                var progress = Math.min(self.pullDistance / self.threshold, 1);

                self.indicator.style.height = height + 'px';
                self.indicator.style.opacity = progress;

                var icon = self.indicator.querySelector('.ptr-icon');
                var text = self.indicator.querySelector('.ptr-text');

                if (self.pullDistance >= self.threshold) {
                    self.state = 'releasing';
                    icon.textContent = '↑';
                    text.textContent = self.releaseText;
                    self.indicator.style.color = '#0d6efd';
                } else {
                    self.state = 'pulling';
                    icon.textContent = '↓';
                    text.textContent = self.refreshText;
                    self.indicator.style.color = '#888';
                    icon.style.transform = 'rotate(' + (progress * 180) + 'deg)';
                }
            }
        }, { passive: false });

        this.container.addEventListener('touchend', function () {
            if (self.state === 'loading') return;

            if (self.state === 'releasing' && self.pullDistance >= self.threshold) {
                self.doRefresh();
            } else {
                self.reset();
            }

            self.startY = 0;
            self.currentY = 0;
            self.pullDistance = 0;
        });
    };

    PullToRefresh.prototype.doRefresh = function () {
        var self = this;
        this.state = 'loading';
        this.indicator.style.height = '50px';
        this.indicator.style.color = '#0d6efd';
        this.indicator.querySelector('.ptr-icon').textContent = '⟳';
        this.indicator.querySelector('.ptr-text').textContent = this.loadingText;

        // 添加旋转动画
        this.indicator.querySelector('.ptr-icon').style.cssText += ';display:inline-block;animation:ptr-spin 1s linear infinite;';

        var style = document.getElementById('ptr-style');
        if (!style) {
            style = document.createElement('style');
            style.id = 'ptr-style';
            style.textContent = '@keyframes ptr-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
            document.head.appendChild(style);
        }

        var result = this.onRefresh();
        if (result && typeof result.then === 'function') {
            result.then(function () {
                self.showSuccess();
            }).catch(function () {
                self.showError();
            });
        } else {
            setTimeout(function () {
                self.showSuccess();
            }, 1000);
        }
    };

    PullToRefresh.prototype.showSuccess = function () {
        var self = this;
        this.indicator.querySelector('.ptr-icon').textContent = '✓';
        this.indicator.querySelector('.ptr-icon').style.animation = 'none';
        this.indicator.querySelector('.ptr-text').textContent = this.successText;
        this.indicator.style.color = '#198754';

        setTimeout(function () {
            self.reset();
        }, 800);
    };

    PullToRefresh.prototype.showError = function () {
        var self = this;
        this.indicator.querySelector('.ptr-icon').textContent = '✗';
        this.indicator.querySelector('.ptr-icon').style.animation = 'none';
        this.indicator.querySelector('.ptr-text').textContent = this.errorText;
        this.indicator.style.color = '#dc3545';

        setTimeout(function () {
            self.reset();
        }, 1200);
    };

    PullToRefresh.prototype.reset = function () {
        this.state = 'idle';
        this.indicator.style.height = '0';
        this.indicator.style.opacity = '0';
        this.indicator.querySelector('.ptr-icon').style.animation = 'none';
    };

    // 暴露到全局
    window.PullToRefresh = PullToRefresh;
})(window);
