/**
 * MO-5: PWA Service Worker — V2.9.28
 * 离线缓存 + 后台更新
 */
const CACHE_VERSION = 'v2.9.28';
const STATIC_CACHE = `aicms-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `aicms-dynamic-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/static/home/css/mobile_nav.css',
    '/static/home/css/mobile_content.css',
    '/static/home/js/lazy_load.js',
];

// 安装：缓存静态资源
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(cache => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
    );
});

// 激活：清理旧缓存
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => !key.includes(CACHE_VERSION))
                    .map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// 请求拦截：缓存优先，网络降级
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    event.respondWith(
        caches.match(event.request).then(cached => {
            if (cached) return cached;

            return fetch(event.request).then(response => {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }
                const responseClone = response.clone();
                caches.open(DYNAMIC_CACHE).then(cache => cache.put(event.request, responseClone));
                return response;
            }).catch(() => {
                if (event.request.destination === 'document') {
                    return caches.match(OFFLINE_URL);
                }
            });
        })
    );
});

// 推送通知
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    event.waitUntil(
        self.registration.showNotification(data.title || 'AI-CMS通知', {
            body: data.body || '',
            icon: '/static/home/img/icon-192.png',
            badge: '/static/home/img/badge-72.png',
            data: { url: data.url || '/' }
        })
    );
});

// 通知点击
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});
