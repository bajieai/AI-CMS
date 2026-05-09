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

    // 页面请求：StaleWhileRevalidate（先返缓存，后台更新）
    if (request.mode === 'navigate' || request.destination === 'document') {
        event.respondWith(
            caches.match(request).then((response) => {
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, networkResponse.clone());
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // 网络失败时，如果缓存也没有，返回离线页面
                    if (!response) {
                        return new Response('<h1>离线模式</h1><p>您当前处于离线状态，请连接网络后重试。</p>', {
                            headers: { 'Content-Type': 'text/html' }
                        });
                    }
                });
                return response || fetchPromise;
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
