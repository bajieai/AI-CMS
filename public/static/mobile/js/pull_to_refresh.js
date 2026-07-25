/**
 * V2.9.24 H-1: 移动端下拉刷新组件（兼容性增强版）
 * 修复：iOS Safari 回弹、多点触控、scrollTop 检测、被动事件监听器、动画冲突
 */
(function (window) {
    'use strict';

    function PullToRefresh(options) {
        this.container = typeof options.container === 'string'
            ? document.querySelector(options.container)
            : options.container;
        this.scrollContainer = options.scrollContainer
            ? (typeof options.scrollContainer === 'string'
                ? document.querySelector(options.scrollContainer)
                : options.scrollContainer)
            : this.container;
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
        this.touchId = null; // 追踪触摸 ID 防止多点触控干扰

        this.init();
    }

    PullToRefresh.prototype.init = function () {
        if (!this.container) return;

        // iOS Safari 回弹微调：限制默认触摸行为
        this.container.style.touchAction = 'pan-y';
        // overscrollBehavior 兼容性处理（iOS Safari < 16 不支持）
        if ('overscrollBehaviorY' in document.body.style) {
            this.container.style.overscrollBehaviorY = 'contain';
        }

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
            'transition: height ' + this.duration + 'ms ease, opacity ' + this.duration + 'ms ease',
            'z-index: 10',
            'pointer-events: none'
        ].join(';');

        this.indicator.innerHTML = [
            '<span class="ptr-icon" style="margin-right:6px;font-size:16px;transition:transform .2s ease;">↓</span>',
            '<span class="ptr-text">' + this.refreshText + '</span>'
        ].join('');

        // 确保容器有相对定位
        var containerStyle = window.getComputedStyle(this.container);
        if (containerStyle.position === 'static') {
            this.container.style.position = 'relative';
        }
        this.container.insertBefore(this.indicator, this.container.firstChild);
    };

    PullToRefresh.prototype.getScrollTop = function () {
        // 兼容多种滚动容器检测
        var st = this.scrollContainer.scrollTop;
        if (st > 0) return st;
        // 降级检测 document.scrollingElement
        if (this.scrollContainer === document.body || this.scrollContainer === document.documentElement) {
            return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
        }
        return 0;
    };

    PullToRefresh.prototype.bindEvents = function () {
        var self = this;

        this.container.addEventListener('touchstart', function (e) {
            if (self.state === 'loading') return;
            // 多点触控：只追踪第一个触点
            if (e.touches.length > 1) {
                self.startY = 0;
                self.touchId = null;
                return;
            }
            // 仅在顶部时触发
            if (self.getScrollTop() > 0) return;
            self.startY = e.touches[0].clientY;
            self.touchId = e.touches[0].identifier;
            self.state = 'idle';
        }, { passive: true });

        this.container.addEventListener('touchmove', function (e) {
            if (self.state === 'loading') return;
            if (self.getScrollTop() > 0) return;
            if (!self.startY) return;

            // 多点触控：找到追踪的触点
            var touch = null;
            for (var i = 0; i < e.touches.length; i++) {
                if (e.touches[i].identifier === self.touchId) {
                    touch = e.touches[i];
                    break;
                }
            }
            if (!touch) return;

            self.currentY = touch.clientY;
            self.pullDistance = Math.max(0, self.currentY - self.startY);

            if (self.pullDistance > 0) {
                // 阻止默认行为（必须在非 passive 监听器中调用）
                if (e.cancelable) {
                    e.preventDefault();
                }
                // 阻尼效果：越往下拉阻力越大
                var dampened = self.pullDistance * (1 - self.pullDistance / (self.maxPull * 2.5));
                var height = Math.min(dampened, self.maxPull);
                var progress = Math.min(dampened / self.threshold, 1);

                self.indicator.style.height = height + 'px';
                self.indicator.style.opacity = Math.max(progress, 0.3);

                var icon = self.indicator.querySelector('.ptr-icon');
                var text = self.indicator.querySelector('.ptr-text');

                if (dampened >= self.threshold) {
                    self.state = 'releasing';
                    icon.textContent = '↑';
                    text.textContent = self.releaseText;
                    self.indicator.style.color = '#0d6efd';
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    self.state = 'pulling';
                    icon.textContent = '↓';
                    text.textContent = self.refreshText;
                    self.indicator.style.color = '#888';
                    icon.style.transform = 'rotate(' + (progress * 180) + 'deg)';
                }
            }
        }, { passive: false });

        this.container.addEventListener('touchend', function (e) {
            if (self.state === 'loading') return;

            // 检查是否是追踪的触点结束
            var wasTracking = false;
            for (var i = 0; i < e.changedTouches.length; i++) {
                if (e.changedTouches[i].identifier === self.touchId) {
                    wasTracking = true;
                    break;
                }
            }
            if (!wasTracking && self.touchId !== null) return;

            if (self.state === 'releasing' && self.pullDistance >= self.threshold) {
                self.doRefresh();
            } else {
                self.reset();
            }

            self.startY = 0;
            self.currentY = 0;
            self.pullDistance = 0;
            self.touchId = null;
        });

        // touchcancel 时重置状态
        this.container.addEventListener('touchcancel', function () {
            if (self.state !== 'loading') {
                self.reset();
                self.startY = 0;
                self.currentY = 0;
                self.pullDistance = 0;
                self.touchId = null;
            }
        });
    };

    PullToRefresh.prototype.doRefresh = function () {
        var self = this;
        this.state = 'loading';
        this.indicator.style.height = '50px';
        this.indicator.style.opacity = '1';
        this.indicator.style.color = '#0d6efd';

        var icon = this.indicator.querySelector('.ptr-icon');
        var text = this.indicator.querySelector('.ptr-text');
        icon.textContent = '⟳';
        text.textContent = this.loadingText;
        // 使用 class 管理旋转动画，避免内联样式冲突
        icon.style.transform = 'none';
        icon.classList.add('ptr-spinning');

        // 注入旋转动画 CSS（幂等）
        var style = document.getElementById('ptr-style');
        if (!style) {
            style = document.createElement('style');
            style.id = 'ptr-style';
            style.textContent = '@keyframes ptr-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}.ptr-spinning{animation:ptr-spin 1s linear infinite!important;}';
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
        var icon = this.indicator.querySelector('.ptr-icon');
        icon.textContent = '✓';
        icon.classList.remove('ptr-spinning');
        icon.style.transform = 'none';
        this.indicator.querySelector('.ptr-text').textContent = this.successText;
        this.indicator.style.color = '#198754';

        setTimeout(function () {
            self.reset();
        }, 800);
    };

    PullToRefresh.prototype.showError = function () {
        var self = this;
        var icon = this.indicator.querySelector('.ptr-icon');
        icon.textContent = '✗';
        icon.classList.remove('ptr-spinning');
        icon.style.transform = 'none';
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
        var icon = this.indicator.querySelector('.ptr-icon');
        if (icon) {
            icon.classList.remove('ptr-spinning');
            icon.style.transform = 'none';
        }
    };

    // 暴露到全局
    window.PullToRefresh = PullToRefresh;
})(window);
