/**
 * Service Worker - V2.9.24 H-5
 * PWA离线缓存优化：版本升级 + 智能缓存策略 + 骨架屏预缓存
 */
const CACHE_NAME = 'ai-cms-v2.9.24-1';
const STATIC_ASSETS = [
    '/',
    '/assets/css/bootstrap.min.css',
    '/assets/js/bootstrap.bundle.min.js',
    '/assets/js/jquery.min.js',
    // V2.9.24 H-5: 骨架屏 + 移动端增强资源预缓存
    // 注: mobile.css/mobile.js 实际为 mobile-enhance.js + mobile-swipe.js
    '/static/mobile/css/skeleton.css',
    '/static/mobile/js/skeleton.js',
    '/static/mobile/js/bottom_nav.js',
    '/static/mobile/js/search_enhance.js',
    '/static/mobile/css/search_enhance.css',
    '/static/mobile/js/share_panel.js',
    '/static/mobile/css/share_panel.css',
];

// 安装：预缓存静态资源
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => {
            return self.skipWaiting();
        })
    );
});

// 激活：清理旧缓存（V2.9.23 E-2：清理v1旧缓存）
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        }).then(() => {
            return self.clients.claim();
        })
    );
});

// 接收页面消息
self.addEventListener('message', (event) => {
    if (!event.data) return;
    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data.type === 'CLEAR_PAGES') {
        caches.open(CACHE_NAME).then((cache) => {
            cache.keys().then((requests) => {
                const toDelete = requests.filter((req) => {
                    const url = new URL(req.url);
                    return req.mode === 'navigate' || url.pathname === '/';
                });
                return Promise.all(toDelete.map((req) => cache.delete(req)));
            });
        });
    }
});

// 拦截请求
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // API请求：Network Only
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/admin/')) {
        return;
    }

    // 图片资源：Cache First + 30天过期
    if (request.destination === 'image') {
        event.respondWith(
            caches.match(request).then((response) => {
                if (response) {
                    // 检查缓存是否过期（30天）
                    const dateHeader = response.headers.get('date');
                    if (dateHeader) {
                        const age = (Date.now() - new Date(dateHeader).getTime()) / (1000 * 60 * 60 * 24);
                        if (age < 30) return response;
                    } else {
                        return response;
                    }
                }
                return fetch(request).then((fetchResponse) => {
                    return caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, fetchResponse.clone());
                        return fetchResponse;
                    });
                });
            })
        );
        return;
    }

    // 静态资源：Cache First
    if (request.destination === 'style' || request.destination === 'script' || request.destination === 'font') {
        event.respondWith(
            caches.match(request).then((response) => {
                return response || fetch(request);
            })
        );
        return;
    }

    // 页面请求：Network First（优先网络，确保动态内容最新）
    if (request.mode === 'navigate' || request.destination === 'document') {
        event.respondWith(
            fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseToCache);
                    });
                }
                return networkResponse;
            }).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    return cachedResponse || new Response('<h1>离线模式</h1><p>您当前处于离线状态，请连接网络后重试。</p>', {
                        headers: { 'Content-Type': 'text/html' }
                    });
                });
            })
        );
        return;
    }

    // 默认：Network First
    event.respondWith(
        fetch(request).catch(() => {
            return caches.match(request);
        })
    );
});
