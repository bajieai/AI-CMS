/**
 * 门户新闻资讯 - 前端脚本
 * 生成日期: 2026-05-14
 */

// DOM加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    initBanner();
    initMobileMenu();
    initLazyLoad();
});

// Banner轮播
function initBanner() {
    var slider = document.getElementById('bannerSlider');
    if (!slider) return;
    var slides = slider.querySelectorAll('.banner-slide');
    var dots = document.querySelectorAll('.banner-dots .dot');
    if (slides.length === 0) return;
    var current = 0;
    var timer = setInterval(function() {
        nextSlide();
    }, 5000);
    dots.forEach(function(dot, index) {
        dot.addEventListener('click', function() {
            clearInterval(timer);
            goToSlide(index);
            timer = setInterval(function() { nextSlide(); }, 5000);
        });
    });
    function goToSlide(index) {
        slides[current].classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        current = index;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
    }
    function nextSlide() {
        goToSlide((current + 1) % slides.length);
    }
}

// 移动端菜单
function initMobileMenu() {
    var menuToggle = document.querySelector('.menu-toggle');
    var navList = document.querySelector('.nav-list');
    if (!menuToggle || !navList) return;
    menuToggle.addEventListener('click', function() {
        navList.classList.toggle('show');
        menuToggle.classList.toggle('active');
    });
}

// 懒加载
function initLazyLoad() {
    var images = document.querySelectorAll('img[data-src]');
    if (images.length === 0) return;
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        images.forEach(function(img) { observer.observe(img); });
    } else {
        images.forEach(function(img) { img.src = img.dataset.src; });
    }
}