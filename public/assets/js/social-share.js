/**
 * 社交分享组件 - V2.8
 * 支持微信、微博、QQ、复制链接
 * 自动记录分享统计埋点
 */
(function() {
    'use strict';

    window.SocialShare = {
        config: {
            title: document.title,
            description: '',
            image: '',
            url: window.location.href,
            enabled: true
        },

        init: function(options) {
            this.config = Object.assign(this.config, options);
            if (!this.config.enabled) return;
            this.bindEvents();
            this.updateOGP();
        },

        bindEvents: function() {
            var self = this;
            document.querySelectorAll('[data-share]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var channel = this.getAttribute('data-share');
                    var btnUrl = this.getAttribute('data-share-url') || self.config.url;
                    self.share(channel, btnUrl);
                });
            });
        },

        share: function(channel, shareUrl) {
            var url = encodeURIComponent(shareUrl || this.config.url);
            var title = encodeURIComponent(this.config.title);
            var pic = encodeURIComponent(this.config.image);
            var desc = encodeURIComponent(this.config.description);
            var shareUrl = '';

            switch(channel) {
                case 'wechat':
                    this.showWechatQR();
                    break;
                case 'weibo':
                    shareUrl = 'https://service.weibo.com/share/share.php?url=' + url + '&title=' + title + '&pic=' + pic;
                    break;
                case 'qq':
                    shareUrl = 'https://connect.qq.com/widget/shareqq/index.html?url=' + url + '&title=' + title + '&summary=' + desc + '&pics=' + pic;
                    break;
                case 'copy':
                    this.copyLink();
                    return;
                default:
                    return;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=500');
            }

            this.trackShare(channel);
        },

        showWechatQR: function() {
            var modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">微信分享</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><p class="text-muted">使用微信扫一扫分享</p><div id="wechatQR" style="width:200px;height:200px;margin:0 auto;"></div></div></div></div>';
            document.body.appendChild(modal);
            var bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // 使用QRCode.js生成二维码（如果已加载）
            if (typeof QRCode !== 'undefined') {
                new QRCode(document.getElementById('wechatQR'), {
                    text: this.config.url,
                    width: 180,
                    height: 180
                });
            } else {
                document.getElementById('wechatQR').innerHTML = '<div class="alert alert-info">请复制链接后在微信中分享</div>';
            }
            
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        },

        copyLink: function() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.config.url).then(function() {
                    if (typeof showToast === 'function') showToast('链接已复制', 'success');
                    else alert('链接已复制');
                });
            } else {
                var input = document.createElement('input');
                input.value = this.config.url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                if (typeof showToast === 'function') showToast('链接已复制', 'success');
                else alert('链接已复制');
            }
        },

        updateOGP: function() {
            var metaTags = [
                {property: 'og:title', content: this.config.title},
                {property: 'og:description', content: this.config.description},
                {property: 'og:image', content: this.config.image},
                {property: 'og:url', content: this.config.url},
                {name: 'twitter:card', content: 'summary_large_image'}
            ];
            
            metaTags.forEach(function(tag) {
                var meta = document.querySelector(tag.property ? '[property="' + tag.property + '"]' : '[name="' + tag.name + '"]');
                if (!meta) {
                    meta = document.createElement('meta');
                    if (tag.property) meta.setAttribute('property', tag.property);
                    if (tag.name) meta.setAttribute('name', tag.name);
                    document.head.appendChild(meta);
                }
                meta.setAttribute('content', tag.content);
            });
        },

        trackShare: function(channel) {
            // V2.9.9: 分享统计埋点 - 写入share_log表
            var data = {
                channel: channel,
                url: this.config.url,
                title: this.config.title
            };
            // 如果页面有content_id元数据，一并上报
            var contentMeta = document.querySelector('meta[name="content-id"]');
            if (contentMeta) {
                data.content_id = contentMeta.getAttribute('content');
            }

            var endpoint = '/api/share/track';
            if (navigator.sendBeacon) {
                navigator.sendBeacon(endpoint, new URLSearchParams(data));
            } else {
                fetch(endpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(data),
                    keepalive: true
                }).catch(function(){});
            }
        }
    };

    // 自动初始化
    if (document.querySelector('.social-share-bar')) {
        window.SocialShare.init();
    }
})();
