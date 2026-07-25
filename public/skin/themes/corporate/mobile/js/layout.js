/**
 * AI-CMS 前台 corporate/mobile 布局脚本 - V2.9.2
 * 功能: 搜索面板 / 菜单面板 / 分类树折叠 / Toast提示 / 语言切换
 */
(function () {
    'use strict';

    // ==================== 搜索面板切换 ====================
    window.togS = function () {
        document.getElementById('csOv').classList.toggle('show');
        if (document.getElementById('csOv').classList.contains('show'))
            setTimeout(function () { document.querySelector('#csOv input').focus(); }, 200);
    };

    // ==================== 菜单面板切换 ====================
    window.togM = function () {
        document.getElementById('cmOv').classList.toggle('show');
        document.getElementById('cmPn').classList.toggle('show');
    };

    // ==================== 分类树折叠 ====================
    document.addEventListener('click', function (e) {
        var el = e.target.closest('.cate-toggle');
        if (!el) return;
        e.preventDefault();
        var t = document.getElementById(el.getAttribute('data-target'));
        if (t) {
            var h = t.style.display === 'none';
            t.style.display = h ? 'block' : 'none';
            el.classList.toggle('bi-chevron-right', !h);
            el.classList.toggle('bi-chevron-down', h);
        }
    });

    // ==================== 移动端 Toast 封装 ====================
    window.showToast = function (m, t) {
        t = t || 'success';
        var c = t === 'success' ? '#198754' : t === 'danger' ? '#dc3545' : '#3b82f6';
        var d = document.createElement('div');
        d.className = 'cm-toast';
        d.textContent = m;
        d.style.cssText = 'position:fixed;top:58px;left:50%;transform:translateX(-50%);z-index:1080;padding:10px 22px;border-radius:22px;font-size:13.5px;color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.2);animation:ctIn .3s ease;background:' + c;
        document.body.appendChild(d);
        setTimeout(function () {
            d.style.opacity = '0';
            d.style.transition = 'opacity .25s';
            setTimeout(function () { d.remove(); }, 250);
        }, 1800);
    };
    $.toast = window.showToast;

    // ==================== 语言切换器 ====================
    document.querySelectorAll('#langList .dropdown-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            var lang = this.getAttribute('data-lang');
            fetch('/api/language/switch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ lang: lang })
            }).then(function () { location.reload(); }).catch(function () { location.reload(); });
        });
    });
})();
