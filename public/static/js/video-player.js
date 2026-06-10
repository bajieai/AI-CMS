/**
 * V2.9.20 A-4: HTML5 视频播放器封装
 * 
 * 用法：
 *   <div class="i8j-video-player" data-src="视频URL" data-poster="封面图URL"></div>
 *   引入此 JS 后自动初始化
 */
(function() {
    'use strict';

    function initVideoPlayers() {
        document.querySelectorAll('.i8j-video-player').forEach(function(container) {
            if (container.querySelector('video')) return;

            var src = container.dataset.src || '';
            var poster = container.dataset.poster || '';
            if (!src) return;

            var contentId = container.dataset.contentId || '';

            var video = document.createElement('video');
            video.className = 'i8j-video-element';
            video.src = src;
            video.poster = poster;
            video.controls = true;
            video.preload = 'metadata';
            video.playsInline = true;
            video.style.cssText = 'width:100%;max-height:480px;border-radius:8px;background:#000;';

            // V2.9.21 D-1: 播放上报（首次播放时触发）
            var playReported = false;
            video.addEventListener('play', function() {
                if (playReported || !contentId) return;
                playReported = true;

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '/api/content/play_count', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send('content_id=' + encodeURIComponent(contentId));
            });

            // 响应式高度限制
            function adjustHeight() {
                var w = container.offsetWidth;
                video.style.maxHeight = (w > 768 ? '480px' : '260px');
            }
            adjustHeight();
            window.addEventListener('resize', adjustHeight);

            container.appendChild(video);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVideoPlayers);
    } else {
        initVideoPlayers();
    }
})();
