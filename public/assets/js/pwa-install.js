/**
 * PWA安装提示组件 - V2.9.3
 * 修复：关闭后刷新/跳转不再重复弹出
 */
(function() {
    let deferredPrompt = null;
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

    // 检查是否已安装
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        return;
    }

    // 监听beforeinstallprompt（仅在未关闭时弹窗）
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        const dismissed = localStorage.getItem('pwa_dismissed');
        if (dismissed && Date.now() - parseInt(dismissed) < 7 * 86400000) {
            return; // 7天内不再提示
        }
        deferredPrompt = e;
        showInstallPrompt();
    });

    function showInstallPrompt() {
        if (document.getElementById('pwa-install-toast')) return;

        // 再次检查关闭状态（防并发）
        const dismissed = localStorage.getItem('pwa_dismissed');
        if (dismissed && Date.now() - parseInt(dismissed) < 7 * 86400000) {
            return;
        }

        const toast = document.createElement('div');
        toast.id = 'pwa-install-toast';
        toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:9999;background:#1e293b;color:#fff;padding:12px 20px;border-radius:8px;display:flex;align-items:center;gap:12px;box-shadow:0 4px 12px rgba(0,0,0,0.3);font-size:14px;';

        if (isIOS && isSafari) {
            toast.innerHTML = '<span>使用 Safari 的「分享」→「添加到主屏幕」安装</span><button style="background:#3b82f6;border:none;color:#fff;padding:4px 12px;border-radius:4px;cursor:pointer;">知道了</button>';
        } else {
            toast.innerHTML = '<span>安装到桌面，离线也能访问</span><button id="pwa-install-btn" style="background:#3b82f6;border:none;color:#fff;padding:4px 12px;border-radius:4px;cursor:pointer;">安装</button><button id="pwa-dismiss-btn" style="background:transparent;border:none;color:#94a3b8;cursor:pointer;">稍后</button>';
        }

        document.body.appendChild(toast);

        const dismissBtn = document.getElementById('pwa-dismiss-btn');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                toast.remove();
                localStorage.setItem('pwa_dismissed', Date.now().toString());
            });
        }

        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn && deferredPrompt) {
            installBtn.addEventListener('click', async () => {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    toast.remove();
                }
                deferredPrompt = null;
            });
        }
    }

    // 注册Service Worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('SW注册失败:', err);
        });
    }
})();
