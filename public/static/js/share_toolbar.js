/**
 * 分享工具栏 JS - V2.9.18 D-2
 */
(function() {
    // 分享到各平台
    window.shareTo = function(platform) {
        var url = encodeURIComponent(location.href);
        var title = encodeURIComponent(document.title);
        var desc = encodeURIComponent(document.querySelector('meta[name="description"]')?.content || '');
        var shareUrls = {
            weibo: 'https://service.weibo.com/share/share.php?url=' + url + '&title=' + title,
            qq: 'https://connect.qq.com/widget/shareqq/index.html?url=' + url + '&title=' + title + '&desc=' + desc,
            twitter: 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title
        };

        if (platform === 'wechat') {
            // 微信分享用二维码
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + url;
            $('#wechatQrCode').html('<img src="' + qrUrl + '" class="img-fluid" alt="微信分享二维码">');
            $('#wechatQrModal').modal('show');
        } else if (shareUrls[platform]) {
            window.open(shareUrls[platform], '_blank', 'width=600,height=400');
        }

        // 记录分享行为
        trackShare(platform);
    };

    window.copyShareLink = function() {
        var url = location.href;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                alert('链接已复制到剪贴板');
            }).catch(function() {
                fallbackCopy(url);
            });
        } else {
            fallbackCopy(url);
        }
        trackShare('copy');
    };

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        alert('链接已复制到剪贴板');
    }

    function trackShare(source) {
        var contentId = document.querySelector('meta[name="content-id"]')?.content || 0;
        try {
            fetch('/api/share/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content_id: parseInt(contentId), channel: source })
            });
        } catch (e) {}
    }

    // UTM 参数解析
    var params = new URLSearchParams(location.search);
    if (params.get('utm_source')) {
        var contentId = document.querySelector('meta[name="content-id"]')?.content || 0;
        try {
            fetch('/api/share/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    content_id: parseInt(contentId),
                    source: params.get('utm_source'),
                    campaign: params.get('utm_campaign') || ''
                })
            });
        } catch (e) {}
    }
})();
