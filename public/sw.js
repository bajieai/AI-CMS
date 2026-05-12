/**
 * Service Worker - V2.9.2 M22a
 * 预缓存 + StaleWhileRevalidate策略
 */
const CACHE_NAME = 'ai-cms-v1';
const STATIC_ASSETS = [
    '/',
    '/assets/css/bootstrap.min.css',
    '/assets/js/bootstrap.bundle.min.js',
    '/assets/js/jquery.min.js',
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

// 激活：清理旧缓存
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

// 接收页面消息（登录/登出后清除缓存、强制更新）
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

    // 图片资源：Cache First
    if (request.destination === 'image') {
        event.respondWith(
            caches.match(request).then((response) => {
                return response || fetch(request).then((fetchResponse) => {
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

    // 页面请求：Network First（优先网络，确保登录状态等动态内容始终最新）
    // V2.9.4 修复：原 StaleWhileRevalidate 会先返回旧缓存（含游客版本），
    // 导致用户登录后首次访问已缓存页面时仍显示未登录状态。改为 Network First
    // 后，在线时总是获取最新内容；离线时回退到缓存。
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
                // 网络失败时回退缓存
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
