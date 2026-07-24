/**
 * V2.9.24 H-4: 移动端分享面板增强
 * Web Share API 优先 + 降级自定义面板 + 分享卡片图（Canvas生成）
 */
(function (window) {
    'use strict';

    var SharePanel = {
        config: {
            title: document.title,
            description: '',
            image: '',
            url: window.location.href,
            siteName: 'AI-CMS',
            shareId: ''
        },

        init: function (options) {
            this.config = Object.assign({}, this.config, options);
            this.bindButtons();
        },

        bindButtons: function () {
            var self = this;
            document.querySelectorAll('[data-share-panel]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    self.open();
                });
            });
        },

        open: function () {
            if (navigator.share) {
                navigator.share({
                    title: this.config.title,
                    text: this.config.description,
                    url: this.config.url
                }).catch(function () {});
            } else {
                this.showPanel();
            }
        },

        showPanel: function () {
            var self = this;
            var existing = document.getElementById('sharePanelOverlay');
            if (existing) existing.remove();

            var overlay = document.createElement('div');
            overlay.id = 'sharePanelOverlay';
            overlay.className = 'share-panel-overlay';

            overlay.innerHTML = '<div class="share-panel">' +
                '<div class="share-panel-header"><span>分享到</span>' +
                    '<button class="share-panel-close" onclick="SharePanel.close()"><i class="bi bi-x-lg"></i></button>' +
                '</div>' +
                '<div class="share-panel-cards">' +
                    '<a href="javascript:;" class="share-card" onclick="SharePanel.shareWechat()"><div class="share-card-icon" style="background:#07c160"><i class="bi bi-wechat"></i></div><span>微信</span></a>' +
                    '<a href="' + this.buildWeiboUrl() + '" class="share-card" target="_blank"><div class="share-card-icon" style="background:#e6162d"><i class="bi bi-sina-weibo"></i></div><span>微博</span></a>' +
                    '<a href="' + this.buildQQUrl() + '" class="share-card" target="_blank"><div class="share-card-icon" style="background:#1296db"><i class="bi bi-qq"></i></div><span>QQ</span></a>' +
                    '<a href="javascript:;" class="share-card" onclick="SharePanel.copyLink()"><div class="share-card-icon" style="background:#6c757d"><i class="bi bi-link-45deg"></i></div><span>复制链接</span></a>' +
                    '<a href="javascript:;" class="share-card" onclick="SharePanel.generateCard()"><div class="share-card-icon" style="background:#0d6efd"><i class="bi bi-image"></i></div><span>分享卡片</span></a>' +
                '</div>' +
                '<div class="share-panel-wechat-tip" id="wechatTip" style="display:none;"><i class="bi bi-info-circle"></i> 点击右上角 <i class="bi bi-three-dots"></i> 选择「发送给朋友」或「分享到朋友圈」</div>' +
            '</div>';

            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) self.close();
            });
            document.body.appendChild(overlay);
            setTimeout(function () { overlay.classList.add('show'); }, 10);
        },

        close: function () {
            var overlay = document.getElementById('sharePanelOverlay');
            if (overlay) {
                overlay.classList.remove('show');
                setTimeout(function () { overlay.remove(); }, 300);
            }
        },

        shareWechat: function () {
            var tip = document.getElementById('wechatTip');
            if (tip) { tip.style.display = 'block'; }
            this.trackShare('wechat');
        },

        buildWeiboUrl: function () {
            return 'https://service.weibo.com/share/share.php?url=' + encodeURIComponent(this.config.url) +
                '&title=' + encodeURIComponent(this.config.title) +
                (this.config.image ? '&pic=' + encodeURIComponent(this.config.image) : '');
        },

        buildQQUrl: function () {
            return 'https://connect.qq.com/widget/shareqq/index.html?url=' + encodeURIComponent(this.config.url) +
                '&title=' + encodeURIComponent(this.config.title) +
                '&summary=' + encodeURIComponent(this.config.description);
        },

        copyLink: function () {
            var self = this;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.config.url).then(function () { self.toast('链接已复制'); });
            } else {
                var input = document.createElement('input');
                input.value = this.config.url;
                document.body.appendChild(input);
                input.select();
                try { document.execCommand('copy'); self.toast('链接已复制'); } catch (e) {}
                input.remove();
            }
            this.trackShare('copy');
        },

        generateCard: function () {
            var self = this;
            var canvas = document.createElement('canvas');
            canvas.width = 750;
            canvas.height = 1334;
            var ctx = canvas.getContext('2d');

            // 背景
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 750, 1334);

            // 顶部色带
            var gradient = ctx.createLinearGradient(0, 0, 750, 0);
            gradient.addColorStop(0, '#0d6efd');
            gradient.addColorStop(1, '#3d8bfd');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, 750, 12);

            // 网站名称
            ctx.fillStyle = '#1a1d21';
            ctx.font = 'bold 32px sans-serif';
            ctx.fillText(this.config.siteName, 40, 80);

            // 分隔线
            ctx.fillStyle = '#e9ecef';
            ctx.fillRect(40, 110, 670, 2);

            // 封面图
            if (this.config.image) {
                var img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = function () {
                    ctx.drawImage(img, 40, 140, 670, 400);
                    self.drawCardContent(ctx, canvas);
                };
                img.onerror = function () {
                    ctx.fillStyle = '#f8f9fa';
                    ctx.fillRect(40, 140, 670, 400);
                    self.drawCardContent(ctx, canvas);
                };
                img.src = this.config.image;
            } else {
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(40, 140, 670, 400);
                self.drawCardContent(ctx, canvas);
            }
        },

        drawCardContent: function (ctx, canvas) {
            var self = this;

            // 标题
            ctx.fillStyle = '#1a1d21';
            ctx.font = 'bold 36px sans-serif';
            ctx.fillText(this.truncateText(ctx, this.config.title, 670), 40, 600);

            // 摘要
            ctx.fillStyle = '#6c757d';
            ctx.font = '28px sans-serif';
            ctx.fillText(this.truncateText(ctx, this.config.description, 670), 40, 650);

            // 二维码
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(this.config.url);
            var qrImg = new Image();
            qrImg.crossOrigin = 'anonymous';
            qrImg.onload = function () {
                ctx.drawImage(qrImg, 40, 1050, 200, 200);
                ctx.fillStyle = '#6c757d';
                ctx.font = '24px sans-serif';
                ctx.fillText('扫码阅读全文', 270, 1100);
                ctx.fillStyle = '#adb5bd';
                ctx.font = '20px sans-serif';
                ctx.fillText(self.config.siteName, 270, 1140);
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 1290, 750, 44);
                ctx.fillStyle = '#adb5bd';
                ctx.font = '20px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('长按保存图片，分享给好友', 375, 1320);
                ctx.textAlign = 'left';
                self.showCardImage(canvas);
            };
            qrImg.onerror = function () { self.showCardImage(canvas); };
            qrImg.src = qrUrl;
        },

        showCardImage: function (canvas) {
            var self = this;
            try {
                var dataUrl = canvas.toDataURL('image/png');
                var existing = document.getElementById('shareCardOverlay');
                if (existing) existing.remove();

                var overlay = document.createElement('div');
                overlay.id = 'shareCardOverlay';
                overlay.className = 'share-card-overlay';
                overlay.innerHTML = '<div class="share-card-preview">' +
                    '<img src="' + dataUrl + '" alt="分享卡片">' +
                    '<p>长按图片保存到相册</p>' +
                    '<button class="btn btn-primary" onclick="SharePanel.closeCard()">关闭</button>' +
                '</div>';
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) self.closeCard();
                });
                document.body.appendChild(overlay);
                setTimeout(function () { overlay.classList.add('show'); }, 10);
                this.trackShare('card');
            } catch (e) {
                this.toast('卡片生成失败，请尝试其他分享方式');
            }
        },

        closeCard: function () {
            var overlay = document.getElementById('shareCardOverlay');
            if (overlay) {
                overlay.classList.remove('show');
                setTimeout(function () { overlay.remove(); }, 300);
            }
        },

        truncateText: function (ctx, text, maxWidth) {
            if (!text) return '';
            if (ctx.measureText(text).width <= maxWidth) return text;
            var truncated = text;
            while (ctx.measureText(truncated + '...').width > maxWidth && truncated.length > 0) {
                truncated = truncated.slice(0, -1);
            }
            return truncated + '...';
        },

        toast: function (msg) {
            if (window.showToast) { window.showToast(msg); return; }
            var t = document.createElement('div');
            t.className = 'm-toast';
            t.textContent = msg;
            t.style.background = '#198754';
            document.body.appendChild(t);
            setTimeout(function () { t.remove(); }, 1800);
        },

        trackShare: function (channel) {
            // 记录分享统计
            try {
                var url = '/api/share/track';
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'url=' + encodeURIComponent(this.config.url) + '&channel=' + channel + '&share_id=' + (this.config.shareId || '')
                });
            } catch (e) {}
        }
    };

    window.SharePanel = SharePanel;
})(window);
