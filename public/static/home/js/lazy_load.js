/**
 * MO-4: 移动端性能优化 — V2.9.28
 * 图片懒加载 + 滚动优化
 */
(function() {
    'use strict';

    // 图片懒加载
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '50px' });

        document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
    } else {
        // 降级：直接加载所有图片
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }

    // 滚动节流
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });

    // 触摸优化
    document.addEventListener('touchstart', () => {}, { passive: true });
})();
