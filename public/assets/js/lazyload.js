/**
 * 统一懒加载组件 - V2.9.1 M14b
 * 基于原生 IntersectionObserver API，零依赖
 *
 * 用法:
 *   1. 给 img 标签添加 data-src="真实图片URL" 和 class="lazyload"
 *   2. 可选：添加 data-srcset="..." 支持响应式图片
 *   3. 页面底部引入此JS后自动初始化
 *
 * 特性:
 *   - 自动监听所有 .lazyload 元素
 *   - 图片进入视口前 200px 提前加载（rootMargin）
 *   - 加载完成后移除 .lazyload 类，添加 .lazyloaded 类
 *   - 支持 picture/source 子元素
 *   - 失败时显示占位图并移除监听
 *   - 兼容不支持 IntersectionObserver 的旧浏览器（降级为立即加载）
 */
(function () {
    'use strict';

    const LAZY_CLASS = 'lazyload';
    const LOADED_CLASS = 'lazyloaded';
    const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * 加载单个元素
     */
    function loadElement(el) {
        if (el.tagName === 'IMG') {
            const src = el.getAttribute('data-src');
            const srcset = el.getAttribute('data-srcset');
            if (!src) return;

            const img = new Image();
            img.onload = function () {
                el.src = src;
                if (srcset) el.srcset = srcset;
                el.classList.remove(LAZY_CLASS);
                el.classList.add(LOADED_CLASS);
                el.removeAttribute('data-src');
                el.removeAttribute('data-srcset');
            };
            img.onerror = function () {
                el.classList.remove(LAZY_CLASS);
                el.classList.add('lazyerror');
                // 保留原始data-src以便调试
            };
            img.src = src;
            if (srcset) img.srcset = srcset;
        } else if (el.tagName === 'PICTURE') {
            const sources = el.querySelectorAll('source[data-srcset]');
            sources.forEach(function (source) {
                source.srcset = source.getAttribute('data-srcset');
                source.removeAttribute('data-srcset');
            });
            const img = el.querySelector('img[data-src]');
            if (img) loadElement(img);
        } else {
            // 支持 background-image 懒加载
            const bg = el.getAttribute('data-bg');
            if (bg) {
                el.style.backgroundImage = 'url(' + bg + ')';
                el.classList.remove(LAZY_CLASS);
                el.classList.add(LOADED_CLASS);
                el.removeAttribute('data-bg');
            }
        }
    }

    /**
     * 初始化 IntersectionObserver
     */
    function initObserver() {
        if (!('IntersectionObserver' in window)) {
            // 降级：立即加载所有
            document.querySelectorAll('.' + LAZY_CLASS).forEach(loadElement);
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadElement(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '200px 0px', // 视口下方200px提前加载
            threshold: 0,
        });

        document.querySelectorAll('.' + LAZY_CLASS).forEach(function (el) {
            observer.observe(el);
        });
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initObserver);
    } else {
        initObserver();
    }

    // 暴露全局方法，支持动态内容（如AJAX加载后手动调用）
    window.Lazyload = {
        refresh: initObserver,
        load: loadElement,
    };
})();
