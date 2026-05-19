/**
 * AI-CMS V2.6 后台双栏菜单控制器
 * 参考eyoucms分列分级设计：左列一级分类(~170px图标+名称) + 右列动态二级分类(~200px图标+名称)
 * 双主题(default/corporate)共用此文件
 *
 * 交互规则:
 *   - 点击一级分类(有子菜单) → 仅切换第二列DOM，不触发PJAX/页面刷新
 *   - 点击一级分类(无子菜单) → doPjax()刷新主内容区，第二列隐藏
 *   - 点击二级菜单项 → doPjax()刷新主内容区，左侧双栏保持不动
 *   - 仪表盘 → doPjax()跳转首页
 */

(function () {
    'use strict';

    // ==================== 配置常量 ====================
    var STORAGE_KEY = 'admin_l1_group';

    // ==================== 状态变量 ====================
    var currentGroupId = null;
    var menuData = [];

    // ==================== DOM引用 ====================
    var $l1Container = null;
    var $l2Container = null;

    /**
     * 初始化
     */
    window.initAdminSidebar = function () {
        menuData = window.MENU_DATA || [];

        $l1Container = $('#sidebarL1');
        $l2Container = $('#sidebarL2');

        if (!$l1Container.length || !$l2Container.length) {
            console.warn('[admin-sidebar] 未找到双栏容器，回退到旧模式');
            return false;
        }

        // 1. 渲染一级分类
        renderL1Menu();

        // 2. 根据当前URL智能初始化第二列状态
        initL2State();

        // 3. URL匹配高亮
        highlightCurrent();

        // 4. 绑定事件
        bindEvents();

        // 5. corporate 主题初始化 Bootstrap Tooltip（延迟到 Bootstrap 可用）
        if (document.body.classList.contains('theme-corporate')) {
            initCorporateTooltips();
        }

        return true;
    };

    /**
     * corporate 主题：为 L1 图标初始化 Bootstrap Tooltip
     */
    function initCorporateTooltips() {
        var maxWait = 100;
        var waited = 0;
        function tryInit() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip && waited < maxWait) {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('.l1-item[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (el) {
                    return new bootstrap.Tooltip(el, { placement: 'right', boundary: 'window' });
                });
            } else if (waited < maxWait) {
                waited++;
                setTimeout(tryInit, 100);
            }
        }
        tryInit();
    }

    /**
     * 根据当前URL初始化第二列显示状态
     */
    function initL2State() {
        var url = window.location.pathname + window.location.search;
        var isDashboard = (url === '/admin' || url === '/admin/' || url.indexOf('/admin/index') === 0);

        if (isDashboard) {
            // 首页：隐藏二级列，高亮仪表盘
            hideL2();
            currentGroupId = null;
            $('.l1-item').removeClass('active');
            $('.l1-item[data-id="dashboard"]').addClass('active');
            return;
        }

        // 非首页：尝试URL匹配所属分组
        var matchedGroupId = findGroupIdByUrl(url);
        if (matchedGroupId) {
            switchGroup(matchedGroupId, false);
            return;
        }

        // 未匹配：尝试localStorage恢复
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved && findGroupById(saved)) {
            switchGroup(parseInt(saved), false);
            return;
        }

        // 默认收起
        hideL2();
    }

    /**
     * 渲染一级分类列
     */
    function renderL1Menu() {
        var html = '';
        var isCorp = document.body.classList.contains('theme-corporate');
        var tooltipAttr = isCorp ? ' data-bs-toggle="tooltip" data-bs-placement="right"' : '';

        // 仪表盘
        html += '<a class="l1-item l1-dashboard" data-id="dashboard" href="/admin"' + tooltipAttr + ' title="仪表盘">';
        html += '<i class="bi bi-speedometer2"></i><span class="l1-text">仪表盘</span>';
        html += '</a>';

        for (var i = 0; i < menuData.length; i++) {
            var g = menuData[i];
            var hasChildren = !!(g.children && g.children.length);
            var icon = g.icon || 'bi bi-circle';
            var titleAttr = tooltipAttr + ' title="' + escapeHtml(g.name) + '"';

            if (!hasChildren && g.url) {
                // 无子菜单但有url：用<a>标签，点击直接跳转
                html += '<a class="l1-item l1-group" href="' + escapeHtml(g.url) + '" ';
                html += 'data-id="' + g.id + '" ';
                html += 'data-has-children="0"' + titleAttr + '>';
                html += '<i class="' + icon + '"></i>';
                html += '<span class="l1-text">' + escapeHtml(g.name) + '</span>';
                html += '</a>';
            } else {
                // 有子菜单：用<div>，点击仅展开二级列
                html += '<div class="l1-item l1-group" ';
                html += 'data-id="' + g.id + '" ';
                html += 'data-has-children="' + (hasChildren ? 1 : 0) + '"' + titleAttr + '>';
                html += '<i class="' + icon + '"></i>';
                html += '<span class="l1-text">' + escapeHtml(g.name) + '</span>';
                if (hasChildren) {
                    html += '<i class="bi bi-chevron-down l1-arrow"></i>';
                }
                html += '</div>';
            }
        }

        $('#l1MenuList').html(html);
    }

    /**
     * 切换一级分组（仅更新第二列DOM，绝不触发页面刷新）
     */
    window.switchGroup = function (groupId, saveToStorage) {
        if (typeof saveToStorage === 'undefined') saveToStorage = true;
        if (currentGroupId === groupId) return;

        // 还原上一组箭头：right → down（表示L2列已收起，可展开）
        if (currentGroupId !== null) {
            $('.l1-item[data-id="' + currentGroupId + '"] .l1-arrow')
                .removeClass('bi-chevron-right').addClass('bi-chevron-down');
        }

        currentGroupId = groupId;

        // 更新一级高亮
        $('.l1-item').removeClass('active');
        $('.l1-item[data-id="' + groupId + '"]').addClass('active');

        var group = findGroupById(groupId);
        if (!group || !group.children || !group.children.length) {
            hideL2();
            return;
        }

        // 当前组箭头：down → right（表示L2列已展开，子菜单在右侧）
        $('.l1-item[data-id="' + groupId + '"] .l1-arrow')
            .removeClass('bi-chevron-down').addClass('bi-chevron-right');

        // 渲染二级菜单 + 展开二级列
        renderL2Menu(group);
        showL2();

        if (saveToStorage) {
            try { localStorage.setItem(STORAGE_KEY, String(groupId)); } catch (e) {}
        }
    };

    /**
     * 显示二级列并动态更新总宽度
     */
    function showL2() {
        $l2Container.addClass('has-content');
        // 动态更新 CSS 变量，让主内容区跟随缩进
        document.documentElement.style.setProperty('--sidebar-total', 'calc(var(--l1-width) + var(--l2-width))');
        var $wrapper = $('.sidebar-wrapper');
        if ($wrapper.length) $wrapper.css('width', 'calc(var(--l1-width) + var(--l2-width))');
        var $main = $('.main-wrapper');
        if ($main.length) $main.css('margin-left', 'calc(var(--l1-width) + var(--l2-width))');
    }

    /**
     * 隐藏二级列并重置总宽度
     */
    function hideL2() {
        $l2Container.removeClass('has-content');
        document.documentElement.style.setProperty('--sidebar-total', 'var(--l1-width)');
        var $wrapper = $('.sidebar-wrapper');
        if ($wrapper.length) $wrapper.css('width', 'var(--l1-width)');
        var $main = $('.main-wrapper');
        if ($main.length) $main.css('margin-left', 'var(--l1-width)');

        // 还原所有一级箭头：right → down
        $('.l1-arrow').removeClass('bi-chevron-right').addClass('bi-chevron-down');
    }

    /**
     * 渲染二级子菜单列
     */
    function renderL2Menu(group) {
        var icon = group.icon || 'bi bi-folder2-open';
        var children = group.children || [];

        var html = '';
        html += '<div class="l2-header">';
        html += '<i class="' + icon + '"></i>';
        html += '<span>' + escapeHtml(group.name) + '</span>';
        html += '</div>';

        html += '<ul class="l2-menu-list">';
        for (var i = 0; i < children.length; i++) {
            var item = children[i];
            var itemIcon = item.icon || 'bi bi-circle';
            html += '<li>';
            html += '<a class="l2-item" href="' + escapeHtml(item.url) + '" data-active="' + escapeHtml(item.active || '') + '">';
            html += '<i class="' + itemIcon + '"></i>';
            html += '<span>' + escapeHtml(item.name) + '</span>';
            html += '</a>';
            html += '</li>';
        }
        html += '</ul>';

        $l2Container.html(html).addClass('has-content').show();
        highlightCurrentL2();
    }

    /**
     * 绑定事件委托（延迟到 window.doPjax 可用后）
     */
    function bindEvents() {
        var maxWait = 100; // 最多等 10 秒
        var waited = 0;

        function doBind() {
            if (typeof window.doPjax !== 'function' && waited < maxWait) {
                waited++;
                setTimeout(doBind, 100);
                return;
            }
            realBind();
        }

        function realBind() {
            // 一级分类点击
            $l1Container.on('click', '.l1-item', function (e) {
                var $el = $(this);
                var rawId = $el.data('id');

                // 仪表盘
                if (rawId === 'dashboard') {
                    e.preventDefault();
                    e.stopPropagation();
                    window.doPjax('/admin');
                    return;
                }

                var groupId = parseInt(rawId);
                var hasChildren = $el.data('has-children') == 1;

                if (hasChildren) {
                    e.preventDefault();
                    e.stopPropagation();
                    switchGroup(groupId, true);
                } else {
                    // 无子菜单：直接调用 doPjax（兼容 <a> 和普通 div）
                    var href = $el.attr('href') || $el.find('a').attr('href');
                    if (href && href.indexOf('#') !== 0 && href.indexOf('javascript:') !== 0) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.doPjax(href);
                    }
                }
            });

            // 二级菜单点击 → doPjax（阻止冒泡避免与 pjax.js 的 a[href] 拦截器重复调用）
            $l2Container.on('click', '.l2-item', function (e) {
                var href = $(this).attr('href');
                if (!href || href.indexOf('#') === 0 || href.indexOf('javascript:') === 0) return;
                e.preventDefault();
                e.stopPropagation();
                if (window.doPjax) {
                    window.doPjax(href);
                } else {
                    console.error('[admin-sidebar] window.doPjax 未定义！');
                    location.href = href;
                }
            });
        }

        // 延迟绑定，确保 window.doPjax 已定义
        if (document.readyState === 'loading') {
            $(document).ready(doBind);
        } else {
            doBind();
        }
    }

    /**
     * URL反推高亮
     */
    function highlightCurrent() {
        var url = window.location.pathname + window.location.search;
        var isDashboard = (url === '/admin' || url === '/admin/' || url.indexOf('/admin/index') === 0);

        if (isDashboard) {
            $('.l1-item').removeClass('active');
            $('.l1-item[data-id="dashboard"]').addClass('active');
            return;
        }

        var matchedGroupId = findGroupIdByUrl(url);
        if (matchedGroupId && matchedGroupId !== currentGroupId) {
            switchGroup(matchedGroupId, true);
        }

        highlightCurrentL2();
    }

    /**
     * 高亮当前URL对应的二级菜单项
     * 支持2级回退：精确URL匹配 → 前缀路径匹配（处理 edit/add/detail 等子页面）
     */
    function highlightCurrentL2() {
        var url = window.location.pathname + window.location.search;
        var matched = false;

        // 第1级：精确URL匹配
        $('.l2-item').each(function () {
            var href = $(this).attr('href');
            var m = !!(href && isUrlMatch(url, href));
            $(this).toggleClass('active', m);
            if (m) matched = true;
        });

        // 第2级：前缀路径匹配（降级方案，用于无独立菜单URL的子页面）
        // 注意：不使用 data-active/MENU_ACTIVE 匹配，因为 PJAX 导航下该值会脏
        if (!matched) {
            $('.l2-item').each(function () {
                var href = $(this).attr('href');
                if (href && isUrlPrefixMatch(url, href)) {
                    $(this).addClass('active');
                    return false; // 只高亮第一个匹配项
                }
            });
        }
    }

    /**
     * 通过active标识高亮二级菜单项
     */
    function highlightL2ByActive(activeKey) {
        $('.l2-item').each(function () {
            $(this).toggleClass('active', $(this).attr('data-active') === activeKey);
        });
    }

    /**
     * 按URL查找所属分组ID
     * 支持2级回退：精确URL匹配 → 前缀路径匹配
     */
    function findGroupIdByUrl(url) {
        // 第1级：精确匹配
        for (var i = 0; i < menuData.length; i++) {
            var children = menuData[i].children || [];
            for (var j = 0; j < children.length; j++) {
                if (children[j].url && isUrlMatch(url, children[j].url)) {
                    return menuData[i].id;
                }
            }
        }
        // 第2级：前缀匹配，处理 edit/add/detail 等子页面
        for (var i = 0; i < menuData.length; i++) {
            var children = menuData[i].children || [];
            for (var j = 0; j < children.length; j++) {
                if (children[j].url && isUrlPrefixMatch(url, children[j].url)) {
                    return menuData[i].id;
                }
            }
        }
        return null;
    }

    /**
     * 按ID查找一级分组数据
     */
    function findGroupById(groupId) {
        for (var i = 0; i < menuData.length; i++) {
            if (menuData[i].id == groupId) return menuData[i];
        }
        return null;
    }

    /**
     * URL精确匹配判断（支持查询参数）
     */
    function isUrlMatch(currentUrl, targetUrl) {
        if (!targetUrl) return false;
        if (currentUrl === targetUrl) return true;
        if (currentUrl.indexOf(targetUrl + '?') === 0) return true;
        if (currentUrl.indexOf(targetUrl + '&') === 0) return true;
        return false;
    }

    /**
     * URL前缀匹配判断
     * 用于子页面（edit/add/detail）继承父列表页的菜单分组
     * 如 /admin/workflow/edit 匹配 /admin/workflow/index 所在分组
     */
    function isUrlPrefixMatch(currentUrl, targetUrl) {
        if (!targetUrl || !currentUrl) return false;
        var lastSlash = targetUrl.lastIndexOf('/');
        if (lastSlash <= 0) return false;
        var prefix = targetUrl.substring(0, lastSlash + 1);
        return currentUrl.indexOf(prefix) === 0;
    }

    /**
     * HTML转义
     */
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML.replace(/'/g, '&#39;');
    }

    // ==================== PJAX集成 ====================

    window.updateSidebarActiveForDualBar = function (url) {
        if (!url) url = window.location.pathname + window.location.search;

        var isDashboard = (url === '/admin' || url === '/admin/' || url.indexOf('/admin/index') === 0);
        if (isDashboard) {
            hideL2();
            currentGroupId = null;
            $('.l1-item').removeClass('active');
            $('.l1-item[data-id="dashboard"]').addClass('active');
            return;
        }

        var matchedGroupId = findGroupIdByUrl(url);
        if (matchedGroupId && matchedGroupId !== currentGroupId) {
            switchGroup(matchedGroupId, false);
        }
        highlightCurrentL2();
    };

    // ==================== 自动初始化 ====================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(window.initAdminSidebar, 50);
        });
    } else {
        setTimeout(window.initAdminSidebar, 50);
    }
})();
