/**
 * AI-CMS 前台 default/pc 布局脚本 - V2.9.2
 * 功能: 分类树折叠 / 搜索联想 / 语言切换
 */
(function () {
    'use strict';

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

    // ==================== 搜索联想 ====================
    var $input = $('#headerSearchInput');
    var $dropdown = $('#searchSuggestDropdown');
    if ($input.length && $dropdown.length) {
        var timer = null;
        $input.on('input', function () {
            clearTimeout(timer);
            var kw = $input.val().trim();
            if (kw.length < 1) { $dropdown.removeClass('show'); return; }
            timer = setTimeout(function () {
                $.get('/api/v1/search/suggest', { keyword: kw, limit: 8 }, function (res) {
                    if (res.code === 0 && res.data && res.data.length > 0) {
                        var html = '';
                        res.data.forEach(function (item) {
                            if (item.type === 'content' && item.id > 0) {
                                html += '<a class="dropdown-item" href="/page/' + item.id + '"><i class="bi bi-file-text text-muted me-2"></i>' + escapeHtml(item.title) + '</a>';
                            } else {
                                html += '<a class="dropdown-item" href="/search?keyword=' + encodeURIComponent(item.title) + '"><i class="bi bi-search text-muted me-2"></i>' + escapeHtml(item.title) + '</a>';
                            }
                        });
                        $dropdown.html(html).addClass('show');
                    } else {
                        $dropdown.removeClass('show');
                    }
                });
            }, 200);
        });
        $input.on('blur', function () { setTimeout(function () { $dropdown.removeClass('show'); }, 200); });
        $input.on('focus', function () { if ($dropdown.children().length) $dropdown.addClass('show'); });
    }

    function escapeHtml(t) {
        var d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    // ==================== 语言切换器 ====================
    var currentLang = window._currentLang || 'zh-CN';
    var langEl = document.getElementById('currentLang');
    if (langEl) langEl.textContent = currentLang;

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
