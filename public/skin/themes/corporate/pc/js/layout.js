/**
 * AI-CMS 前台 corporate/pc 布局脚本 - V2.9.2
 * 功能: 导航栏滚动效果 / 分类树折叠 / 语言切换
 */
(function () {
    'use strict';

    // ==================== 导航栏滚动效果 ====================
    var nav = document.getElementById('corporateNavbar');
    if (nav) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 50) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        }, { passive: true });
    }

    // ==================== 分类树折叠展开 ====================
    document.addEventListener('click', function (e) {
        var el = e.target.closest('.cate-toggle');
        if (!el) return;
        e.preventDefault();
        e.stopPropagation();
        var targetId = el.getAttribute('data-target');
        var target = document.getElementById(targetId);
        if (target) {
            var isHidden = target.style.display === 'none';
            target.style.display = isHidden ? 'block' : 'none';
            el.classList.toggle('bi-chevron-right', !isHidden);
            el.classList.toggle('bi-chevron-down', isHidden);
        }
    });

    // ==================== 语言切换器 ====================
    var currentLang = window._currentLang || 'zh-CN';
    var btn = document.querySelector('#langSwitcher .dropdown-toggle');
    if (btn) btn.innerHTML = '<i class="bi bi-globe"></i> ' + currentLang;

    document.querySelectorAll('#langList .dropdown-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            var lang = this.getAttribute('data-lang');
            if (lang === currentLang) return;
            fetch('/api/language/switch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lang: lang })
            }).then(function () { location.reload(); });
        });
    });
})();
