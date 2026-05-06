/**
 * AI-CMS PJAX 局部刷新模块
 * 独立文件，确保在 admin-sidebar.js 之前加载
 * v3.3 - 支持页面级外部JS动态加载（ECharts/TinyMCE等），确保在外部脚本加载完成后再执行内联脚本
 */
(function($) {
    'use strict';

    var containerSelector = '#pjax-container';
    // 记录已注入的页面级CSS标记，避免重复注入
    var injectedCssMarker = 'data-pjax-css';

    // ==================== PJAX 核心函数 ====================
    function doPjax(url, pushState) {
        if (pushState !== false) pushState = true;
        window.showPageLoader && window.showPageLoader();

        $.ajax({
            url: url,
            type: 'GET',
            cache: false,
            headers: { 'X-PJAX': 'true', 'X-Requested-With': 'XMLHttpRequest' },
            dataType: 'json',
            success: function(res) {
                if (!res || !res.html || res.html.length === 0) {
                    // 内容为空，降级为整页跳转
                    location.href = url;
                    return;
                }

                // 清理上一页注入的页面级CSS
                cleanupPageCss();

                // 替换内容区
                $(containerSelector).html(res.html);

                // 更新页面标题
                if (res.title) document.title = res.title;

                // 注入页面级CSS
                if (res.css) {
                    injectPageCss(res.css);
                }

                // 更新浏览器历史
                if (pushState) {
                    history.pushState({pjax: true, url: url}, '', url);
                }

                // 更新菜单高亮
                updateSidebarActive(url);

                // 更新 CSRF Token
                if (res.csrf_token) {
                    $('input[name="__token__"]').val(res.csrf_token);
                }

                // 动态加载页面级外部JS，然后执行内联脚本
                var externalScripts = res.js_src || [];
                var inlineJs = res.js || '';

                if (externalScripts.length > 0) {
                    loadExternalScripts(externalScripts, function() {
                        executeInlineScript(inlineJs);
                        finishPjax();
                    });
                } else {
                    executeInlineScript(inlineJs);
                    finishPjax();
                }
            },
            error: function(xhr, status, err) {
                console.error('[PJAX] AJAX失败:', status, err);
                location.href = url;
            }
        });
    }

    // ==================== 外部脚本加载 & 内联脚本执行 ====================

    /**
     * 串行加载外部JS文件，全部完成后回调
     */
    function loadExternalScripts(urls, callback) {
        if (!urls || urls.length === 0) { callback && callback(); return; }
        var loaded = 0;
        var total = urls.length;
        urls.forEach(function(url) {
            // 避免重复加载：检查页面中是否已存在同src的script
            var existing = document.querySelector('script[src="' + url + '"]');
            if (existing) {
                loaded++;
                if (loaded === total) callback && callback();
                return;
            }
            var s = document.createElement('script');
            s.src = url;
            s.onload = s.onreadystatechange = function() {
                if (!this.readyState || this.readyState === 'loaded' || this.readyState === 'complete') {
                    loaded++;
                    if (loaded === total) callback && callback();
                    s.onload = s.onreadystatechange = null;
                }
            };
            s.onerror = function() {
                console.error('[PJAX] 外部脚本加载失败:', url);
                loaded++;
                if (loaded === total) callback && callback();
            };
            document.head.appendChild(s);
        });
    }

    /**
     * 安全执行内联JS脚本
     */
    function executeInlineScript(jsText) {
        if (!jsText) return;
        try {
            var s = document.createElement('script');
            s.textContent = jsText;
            document.head.appendChild(s);
            document.head.removeChild(s);
        } catch (e) {
            console.error('[PJAX] 内联脚本执行错误:', e);
        }
    }

    /**
     * PJAX切换收尾：触发事件、滚动、隐藏加载条
     */
    function finishPjax() {
        $(document).trigger('pjax:complete');
        window.scrollTo(0, 0);
        window.hidePageLoader && window.hidePageLoader();
    }

    // ==================== 页面级CSS注入/清理 ====================

    /**
     * 注入页面级CSS（带标记，切页时自动清理）
     */
    function injectPageCss(cssText) {
        var style = document.createElement('style');
        style.setAttribute(injectedCssMarker, 'page');
        style.textContent = cssText;
        document.head.appendChild(style);
    }

    /**
     * 清理上一页注入的页面级CSS
     */
    function cleanupPageCss() {
        var old = document.head.querySelectorAll('style[' + injectedCssMarker + ']');
        for (var i = 0; i < old.length; i++) {
            old[i].parentNode.removeChild(old[i]);
        }
    }

    // ==================== 菜单高亮更新 ====================
    function updateSidebarActive(url) {
        // V2.6: 优先使用双栏菜单高亮函数
        if (window.updateSidebarActiveForDualBar) {
            window.updateSidebarActiveForDualBar(url);
            return;
        }
        // 回退：单栏高亮
        $('.l2-item').removeClass('active');
        $('.l2-item').each(function() {
            var href = $(this).attr('href');
            if (href && (url === href || url.indexOf(href + '?') === 0 || url.indexOf(href + '&') === 0)) {
                $(this).addClass('active');
            }
        });
    }

    // ==================== 暴露给全局 ====================
    window.doPjax = doPjax;
    window.updateSidebarActive = updateSidebarActive;

    // ==================== 拦截页面内所有链接 ====================
    $(document).on('click', 'a[href]', function(e) {
        var $this = $(this);
        var href = $this.attr('href');
        if (!href ||
            href.indexOf('#') === 0 ||
            href.indexOf('javascript:') === 0 ||
            $this.attr('target') ||
            $this.attr('data-no-pjax') ||
            (href.indexOf('http') === 0 && href.indexOf(window.location.host) === -1)
        ) {
            return;
        }
        e.preventDefault();
        doPjax(href);
    });

    // ==================== 浏览器前进后退 ====================
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.pjax) {
            doPjax(e.state.url, false);
        }
    });

})(jQuery);
