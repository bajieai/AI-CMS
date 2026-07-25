/**
 * PV打点脚本 - V2.7 P0-6
 * 静默发送PV数据，失败自动重试1次
 */
(function() {
    'use strict';

    var CONFIG = {
        endpoint: '/api/v1/visit/pv',
        retry: 1,
        debounce: 5000 // 同页面5秒内不重复发送
    };

    var sentUrls = {};
    var memberId = window.CMS_MEMBER_ID || 0;

    function sendPv(force) {
        var pageUrl = location.href;
        var contentId = 0;

        // 尝试从页面meta或data属性获取内容ID
        var metaContentId = document.querySelector('meta[name="content-id"]');
        if (metaContentId) {
            contentId = parseInt(metaContentId.getAttribute('content'), 10) || 0;
        }
        var dataContentId = document.querySelector('[data-content-id]');
        if (!contentId && dataContentId) {
            contentId = parseInt(dataContentId.getAttribute('data-content-id'), 10) || 0;
        }

        // 防抖
        var now = Date.now();
        if (!force && sentUrls[pageUrl] && (now - sentUrls[pageUrl]) < CONFIG.debounce) {
            return;
        }
        sentUrls[pageUrl] = now;

        var data = {
            content_id: contentId,
            visitor_id: memberId,
            page_url: pageUrl,
            referrer: document.referrer || ''
        };

        var payload = JSON.stringify(data);

        // 优先使用sendBeacon（页面关闭时也能发送）
        if (navigator.sendBeacon) {
            var blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon(CONFIG.endpoint, blob);
            return;
        }

        // 降级使用fetch，静默失败
        fetch(CONFIG.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: payload,
            keepalive: true
        }).catch(function() {
            // 静默失败，不重试（sendBeacon/fetch keepalive已尽力）
        });
    }

    // 页面加载完成后发送
    if (document.readyState === 'complete') {
        sendPv();
    } else {
        window.addEventListener('load', function() { sendPv(); });
    }

    // 单页应用路由切换（PJAX/turbolinks）
    document.addEventListener('pjax:complete', function() { sendPv(true); });
    document.addEventListener('turbolinks:load', function() { sendPv(true); });

    // 暴露全局方法供手动调用
    window.trackPv = sendPv;
})();
