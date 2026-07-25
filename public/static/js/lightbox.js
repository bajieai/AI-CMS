/**
 * V2.9.20 A-4: 图片轮播 Lightbox 组件
 * 
 * 用法：
 *   1. 在模板中给图片容器加 data-lightbox="gallery"
 *   2. 引入此 JS 后自动生效
 *   3. 点击缩略图放大查看，支持左右切换
 */
(function() {
    'use strict';

    var overlay = null;
    var images = [];
    var currentIndex = 0;

    function initLightbox() {
        document.querySelectorAll('[data-lightbox]').forEach(function(gallery) {
            gallery.querySelectorAll('img').forEach(function(img, idx) {
                img.style.cursor = 'zoom-in';
                img.addEventListener('click', function(e) {
                    e.preventDefault();
                    openGallery(gallery, idx);
                });
            });
        });
    }

    function openGallery(gallery, startIdx) {
        images = Array.from(gallery.querySelectorAll('img')).map(function(img) {
            return img.src || img.dataset.src || '';
        }).filter(Boolean);
        currentIndex = startIdx;
        showOverlay();
    }

    function showOverlay() {
        if (overlay) closeOverlay();

        overlay = document.createElement('div');
        overlay.className = 'i8j-lightbox-overlay';
        overlay.innerHTML =
            '<div class="i8j-lightbox-backdrop"></div>' +
            '<div class="i8j-lightbox-content">' +
                '<img src="' + images[currentIndex] + '" class="i8j-lightbox-img" alt="">' +
                '<div class="i8j-lightbox-counter">' + (currentIndex + 1) + ' / ' + images.length + '</div>' +
                (images.length > 1 ? '<button class="i8j-lightbox-prev">&#10094;</button><button class="i8j-lightbox-next">&#10095;</button>' : '') +
                '<button class="i8j-lightbox-close">&times;</button>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        overlay.querySelector('.i8j-lightbox-close').addEventListener('click', closeOverlay);
        overlay.querySelector('.i8j-lightbox-backdrop').addEventListener('click', closeOverlay);

        var prevBtn = overlay.querySelector('.i8j-lightbox-prev');
        var nextBtn = overlay.querySelector('.i8j-lightbox-next');
        if (prevBtn) prevBtn.addEventListener('click', function(e) { e.stopPropagation(); navigate(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function(e) { e.stopPropagation(); navigate(1); });

        document.addEventListener('keydown', handleKey);
    }

    function navigate(dir) {
        currentIndex = (currentIndex + dir + images.length) % images.length;
        var img = overlay.querySelector('.i8j-lightbox-img');
        if (img) img.src = images[currentIndex];
        var counter = overlay.querySelector('.i8j-lightbox-counter');
        if (counter) counter.textContent = (currentIndex + 1) + ' / ' + images.length;
    }

    function handleKey(e) {
        if (!overlay) return;
        if (e.key === 'Escape') closeOverlay();
        if (e.key === 'ArrowLeft') navigate(-1);
        if (e.key === 'ArrowRight') navigate(1);
    }

    function closeOverlay() {
        if (!overlay) return;
        overlay.remove();
        overlay = null;
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleKey);
    }

    // 注入基础样式
    if (!document.getElementById('i8j-lightbox-style')) {
        var style = document.createElement('style');
        style.id = 'i8j-lightbox-style';
        style.textContent =
            '.i8j-lightbox-overlay{position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;display:flex;align-items:center;justify-content:center;}' +
            '.i8j-lightbox-backdrop{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);}' +
            '.i8j-lightbox-content{position:relative;z-index:1;max-width:90vw;max-height:90vh;text-align:center;}' +
            '.i8j-lightbox-img{max-width:100%;max-height:85vh;border-radius:4px;box-shadow:0 4px 20px rgba(0,0,0,0.3);}' +
            '.i8j-lightbox-counter{position:absolute;bottom:-28px;left:0;right:0;color:#fff;font-size:14px;text-align:center;}' +
            '.i8j-lightbox-prev,.i8j-lightbox-next{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);color:#fff;border:none;width:44px;height:44px;font-size:20px;cursor:pointer;border-radius:50%;display:flex;align-items:center;justify-content:center;}' +
            '.i8j-lightbox-prev{left:10px;}.i8j-lightbox-next{right:10px;}' +
            '.i8j-lightbox-prev:hover,.i8j-lightbox-next:hover{background:rgba(255,255,255,0.3);}' +
            '.i8j-lightbox-close{position:absolute;top:-40px;right:0;background:none;border:none;color:#fff;font-size:36px;cursor:pointer;line-height:1;padding:0 4px;}';
        document.head.appendChild(style);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLightbox);
    } else {
        initLightbox();
    }
})();
