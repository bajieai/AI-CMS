/**
 * AI-CMS PJAX 局部刷新模块
 * 独立文件，确保在 admin-sidebar.js 之前加载
 * v3.4 - 外部JS串行加载，保证依赖顺序（TinyMCE→业务JS），PJAX并行加载下的AI_CMS_CONFIG守卫
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
     * V2.9.42: 只加载尚未加载的 JS（避免重复加载导致事件委托堆积）
     * 已加载过的 JS 不会重新执行 IIFE，通过 pjax:complete 事件触发重新初始化
     */
    function loadExternalScripts(urls, callback) {
        if (!urls || urls.length === 0) { callback && callback(); return; }
        
        var idx = 0;
        function loadNext() {
            if (idx >= urls.length) {
                callback && callback();
                return;
            }
            var url = urls[idx];
            idx++;
            
            // 检查是否已加载过：已存在则跳过（避免 IIFE 重复执行导致事件委托堆积）
            // V2.9.42: 用 document 而非 document.head，因为页面 script 标签可能在 body 中
            var existing = document.querySelector('script[src="' + url + '"]');
            if (existing) {
                loadNext();
                return;
            }
            
            var s = document.createElement('script');
            s.src = url;
            s.setAttribute('data-pjax-page-js', '1');
            s.onload = s.onreadystatechange = function() {
                if (!this.readyState || this.readyState === 'loaded' || this.readyState === 'complete') {
                    s.onload = s.onreadystatechange = null;
                    loadNext();
                }
            };
            s.onerror = function() {
                console.error('[PJAX] 外部脚本加载失败:', url);
                loadNext();
            };
            document.head.appendChild(s);
        }
        loadNext();
    }

    /**
     * 安全执行内联JS脚本
     */
    function executeInlineScript(jsText) {
        if (!jsText) return;
        // V2.9.42: 跳过全局已存在的函数声明（PJAX 重复执行场景）
        // 仅执行事件绑定和变量赋值等非函数声明代码
        var funcDecl = jsText.match(/^function\s+(\w+)\s*\(/m);
        if (funcDecl && typeof window[funcDecl[1]] === 'function') {
            return; // 已声明过，跳过
        }
        try {
            var s = document.createElement('script');
            s.textContent = jsText;
            document.head.appendChild(s);
            document.head.removeChild(s);
        } catch (e) {
            console.warn('[PJAX] 内联脚本执行警告:', e.message);
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
            $this.is('[data-no-pjax]') ||
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
