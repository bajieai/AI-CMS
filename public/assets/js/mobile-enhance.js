/**
 * H5移动端深度优化 - V2.9.2 M22b
 * 骨架屏 + 无限滚动 + 底部Sheet
 */
(function() {
    const isMobile = window.innerWidth < 768;
    if (!isMobile) return;

    // ===== 骨架屏 =====
    function showSkeleton(container, count) {
        count = count || 3;
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="skeleton-item" style="padding:12px 0;border-bottom:1px solid #f0f0f0;">
                    <div style="background:#f0f0f0;height:18px;width:70%;border-radius:4px;margin-bottom:8px;animation: skeleton-pulse 1.5s infinite;"></div>
                    <div style="background:#f0f0f0;height:14px;width:40%;border-radius:4px;animation: skeleton-pulse 1.5s infinite;"></div>
                </div>
            `;
        }
        container.innerHTML = html;
    }

    function hideSkeleton(container) {
        container.innerHTML = '';
    }

    // 注册骨架屏CSS
    if (!document.getElementById('skeleton-style')) {
        const style = document.createElement('style');
        style.id = 'skeleton-style';
        style.textContent = `
            @keyframes skeleton-pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }

    // ===== 无限滚动 =====
    function initInfiniteScroll(options) {
        const defaults = {
            container: null,
            nextSelector: '.pagination a[rel="next"]',
            itemSelector: '.item',
            loadingText: '加载中...',
            finishedText: '没有更多了',
        };
        const opts = Object.assign({}, defaults, options);
        const container = typeof opts.container === 'string' ? document.querySelector(opts.container) : opts.container;
        if (!container) return;

        let isLoading = false;
        let isFinished = false;

        function loadNext() {
            if (isLoading || isFinished) return;
            const nextLink = document.querySelector(opts.nextSelector);
            if (!nextLink) {
                isFinished = true;
                showStatus(opts.finishedText);
                return;
            }

            isLoading = true;
            showStatus(opts.loadingText);

            fetch(nextLink.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const items = doc.querySelectorAll(opts.itemSelector);

                    if (items.length === 0) {
                        isFinished = true;
                        showStatus(opts.finishedText);
                        return;
                    }

                    items.forEach(item => container.appendChild(item.cloneNode(true)));

                    // 更新分页链接
                    const newNext = doc.querySelector(opts.nextSelector);
                    if (newNext) {
                        nextLink.href = newNext.href;
                    } else {
                        nextLink.remove();
                        isFinished = true;
                        showStatus(opts.finishedText);
                    }
                })
                .catch(() => {
                    showStatus('加载失败，请重试');
                })
                .finally(() => {
                    isLoading = false;
                    hideStatus();
                });
        }

        function showStatus(text) {
            let el = document.getElementById('infinite-scroll-status');
            if (!el) {
                el = document.createElement('div');
                el.id = 'infinite-scroll-status';
                el.style.cssText = 'text-align:center;padding:16px;color:#999;font-size:14px;';
                container.parentNode.insertBefore(el, container.nextSibling);
            }
            el.textContent = text;
        }

        function hideStatus() {
            const el = document.getElementById('infinite-scroll-status');
            if (el) el.textContent = '';
        }

        window.addEventListener('scroll', () => {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
                loadNext();
            }
        });
    }

    // ===== 底部Sheet（图片查看器）=====
    function showBottomSheet(contentHtml) {
        let sheet = document.getElementById('mobile-bottom-sheet');
        if (!sheet) {
            sheet = document.createElement('div');
            sheet.id = 'mobile-bottom-sheet';
            sheet.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:10000;background:#fff;border-radius:16px 16px 0 0;transform:translateY(100%);transition:transform 0.3s ease;max-height:80vh;overflow:auto;box-shadow:0 -4px 20px rgba(0,0,0,0.15);';
            sheet.innerHTML = '<div style="padding:12px 0;text-align:center;"><div style="width:40px;height:4px;background:#ddd;border-radius:2px;margin:0 auto;"></div></div><div id="bottom-sheet-content"></div>';
            document.body.appendChild(sheet);

            // 点击遮罩关闭
            const overlay = document.createElement('div');
            overlay.id = 'mobile-sheet-overlay';
            overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.4);display:none;';
            overlay.addEventListener('click', hideBottomSheet);
            document.body.insertBefore(overlay, sheet);
        }

        document.getElementById('bottom-sheet-content').innerHTML = contentHtml;
        document.getElementById('mobile-sheet-overlay').style.display = 'block';
        requestAnimationFrame(() => {
            sheet.style.transform = 'translateY(0)';
        });
    }

    function hideBottomSheet() {
        const sheet = document.getElementById('mobile-bottom-sheet');
        const overlay = document.getElementById('mobile-sheet-overlay');
        if (sheet) sheet.style.transform = 'translateY(100%)';
        if (overlay) overlay.style.display = 'none';
    }

    // ===== 图片查看器 =====
    function initImageViewer() {
        document.addEventListener('click', (e) => {
            const img = e.target.closest('img');
            if (!img || img.closest('a') || img.classList.contains('no-viewer')) return;

            const src = img.getAttribute('data-src') || img.src;
            if (!src || src.startsWith('data:')) return;

            showBottomSheet(`
                <div style="padding:0 16px 24px;">
                    <img src="${src}" style="width:100%;border-radius:8px;display:block;" onclick="event.stopPropagation()">
                </div>
            `);
        });
    }

    // ===== 暴露全局API =====
    window.MobileEnhance = {
        showSkeleton,
        hideSkeleton,
        initInfiniteScroll,
        showBottomSheet,
        hideBottomSheet,
        initImageViewer,
    };

    // 自动初始化
    document.addEventListener('DOMContentLoaded', () => {
        initImageViewer();
    });
})();
