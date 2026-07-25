/**
 * AI-CMS 前台 default/mobile 布局脚本 - V2.9.2
 * 功能: 搜索面板 / 菜单面板 / 分类树折叠 / Toast提示
 */
(function () {
    'use strict';

    // ==================== 搜索面板切换 ====================
    window.toggleSearch = function () {
        var o = document.getElementById('mSearchOverlay');
        o.classList.toggle('show');
        if (o.classList.contains('show')) setTimeout(function () { o.querySelector('input').focus(); }, 200);
    };

    // ==================== 菜单面板切换 ====================
    window.toggleMenu = function () {
        document.getElementById('mMenuOverlay').classList.toggle('show');
        document.getElementById('mMenuPanel').classList.toggle('show');
    };

    // ==================== 分类树折叠 ====================
    document.addEventListener('click', function (e) {
        var el = e.target.closest('.cate-toggle');
        if (!el) return;
        e.preventDefault();
        var tid = el.getAttribute('data-target');
        var t = document.getElementById(tid);
        if (t) {
            var h = t.style.display === 'none';
            t.style.display = h ? 'block' : 'none';
            el.classList.toggle('bi-chevron-right', !h);
            el.classList.toggle('bi-chevron-down', h);
        }
    });

    // ==================== 移动端 Toast 封装 ====================
    window.showToast = function (msg, type) {
        type = type || 'success';
        var c = type === 'success' ? '#198754' : type === 'danger' ? '#dc3545' : '#0d6efd';
        var d = document.createElement('div');
        d.className = 'm-toast';
        d.style.background = c;
        d.textContent = msg;
        document.body.appendChild(d);
        setTimeout(function () {
            d.style.opacity = '0';
            d.style.transition = 'opacity .25s';
            setTimeout(function () { d.remove(); }, 250);
        }, 1800);
    };
    $.toast = window.showToast;
})();
