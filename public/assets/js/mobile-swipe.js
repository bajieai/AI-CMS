/**
 * 手势滑动 - V2.9.2 M22b
 * 左右翻页 + 下拉刷新
 */
(function() {
    const isMobile = window.innerWidth < 768;
    if (!isMobile) return;

    let startX = 0, startY = 0, endX = 0, endY = 0;
    let isSwiping = false;
    const threshold = 80;

    document.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        isSwiping = true;
    }, { passive: true });

    document.addEventListener('touchmove', (e) => {
        if (!isSwiping) return;
        endX = e.touches[0].clientX;
        endY = e.touches[0].clientY;
    }, { passive: true });

    document.addEventListener('touchend', () => {
        if (!isSwiping) return;
        isSwiping = false;

        const diffX = endX - startX;
        const diffY = endY - startY;

        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > threshold) {
            // 水平滑动
            const eventName = diffX > 0 ? 'swiperight' : 'swipeleft';
            document.dispatchEvent(new CustomEvent(eventName, {
                detail: { distance: Math.abs(diffX) }
            }));
        }

        if (Math.abs(diffY) > Math.abs(diffX) && diffY > threshold && window.scrollY <= 5) {
            // 下拉刷新（仅在页面顶部）
            document.dispatchEvent(new CustomEvent('pulldownrefresh'));
        }
    });

    // 下拉刷新UI
    let pullIndicator = null;
    document.addEventListener('pulldownrefresh', () => {
        if (!pullIndicator) {
            pullIndicator = document.createElement('div');
            pullIndicator.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:9998;text-align:center;padding:12px;background:#f0f9ff;color:#3b82f6;font-size:14px;transform:translateY(-100%);transition:transform 0.2s;';
            pullIndicator.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> 正在刷新...';
            document.body.appendChild(pullIndicator);
        }
        pullIndicator.style.transform = 'translateY(0)';
        setTimeout(() => {
            location.reload();
        }, 600);
    });
})();
