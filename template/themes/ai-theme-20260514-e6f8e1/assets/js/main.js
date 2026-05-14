(function() {
    'use strict';

    // 轮播功能
    class Slider {
        constructor(element) {
            this.wrapper = element;
            this.track = element.querySelector('.slider-track');
            this.slides = element.querySelectorAll('.slide-item');
            this.dots = element.querySelectorAll('.dot');
            this.prevBtn = element.querySelector('.slider-prev');
            this.nextBtn = element.querySelector('.slider-next');
            this.currentIndex = 0;
            this.totalSlides = this.slides.length;
            this.autoPlayInterval = null;
            
            if (this.totalSlides > 0) {
                this.init();
            }
        }

        init() {
            this.addEventListeners();
            this.startAutoPlay();
        }

        addEventListeners() {
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.prev());
            }
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.next());
            }
            
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goTo(index));
            });

            this.wrapper.addEventListener('mouseenter', () => this.stopAutoPlay());
            this.wrapper.addEventListener('mouseleave', () => this.startAutoPlay());
        }

        goTo(index) {
            if (index < 0) index = this.totalSlides - 1;
            if (index >= this.totalSlides) index = 0;
            
            this.currentIndex = index;
            this.track.style.transform = `translateX(-${index * 100}%)`;
            
            this.dots.forEach(dot => dot.classList.remove('active'));
            this.dots[index].classList.add('active');
        }

        prev() {
            this.goTo(this.currentIndex - 1);
        }

        next() {
            this.goTo(this.currentIndex + 1);
        }

        startAutoPlay() {
            this.stopAutoPlay();
            this.autoPlayInterval = setInterval(() => this.next(), 5000);
        }

        stopAutoPlay() {
            if (this.autoPlayInterval) {
                clearInterval(this.autoPlayInterval);
                this.autoPlayInterval = null;
            }
        }
    }

    // 移动端菜单
    class MobileMenu {
        constructor() {
            this.menuToggle = document.getElementById('mobileMenuToggle') || document.getElementById('menuToggle');
            this.mobileNav = document.getElementById('mobileNav');
            
            if (this.menuToggle && this.mobileNav) {
                this.init();
            }
        }

        init() {
            this.menuToggle.addEventListener('click', () => {
                this.mobileNav.classList.toggle('active');
                this.menuToggle.textContent = this.mobileNav.classList.contains('active') ? '✕' : '☰';
            });

            document.addEventListener('click', (e) => {
                if (!this.menuToggle.contains(e.target) && !this.mobileNav.contains(e.target)) {
                    this.mobileNav.classList.remove('active');
                    this.menuToggle.textContent = '☰';
                }
            });
        }
    }

    // 移动端搜索
    class MobileSearch {
        constructor() {
            this.searchToggle = document.getElementById('searchToggle');
            this.searchForm = document.querySelector('.mobile-search');
            
            if (this.searchToggle && this.searchForm) {
                this.init();
            }
        }

        init() {
            this.searchToggle.addEventListener('click', () => {
                this.searchForm.style.display = this.searchForm.style.display === 'block' ? 'none' : 'block';
                const input = this.searchForm.querySelector('input');
                if (input) {
                    input.focus();
                }
            });
        }
    }

    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化轮播
        const sliderWrapper = document.getElementById('sliderWrapper');
        if (sliderWrapper) {
            new Slider(sliderWrapper);
        }

        // 初始化移动端菜单
        new MobileMenu();

        // 初始化移动端搜索
        new MobileSearch();

        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // 图片懒加载
        if ('IntersectionObserver' in window) {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    });

})();
