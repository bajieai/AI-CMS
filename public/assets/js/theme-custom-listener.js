/**
 * V2.9.7 前台主题定制 postMessage 监听器
 * 
 * 在前台页面中监听来自定制面板的CSS变量更新消息，
 * 实时覆盖:root中的CSS变量，实现即时预览。
 * 
 * 注入方式：在layout.html中引入此脚本（约20行核心代码，零侵入模板结构）
 */
(function() {
    'use strict';

    // 仅在定制预览模式下启用
    if (window.location.search.indexOf('preview_custom=1') === -1 &&
        !window.sessionStorage.getItem('theme_custom_preview')) {
        return;
    }

    // 标记预览模式
    window.sessionStorage.setItem('theme_custom_preview', '1');

    // 监听postMessage
    window.addEventListener('message', function(event) {
        // 安全校验：仅接受同源消息
        if (event.origin !== window.location.origin) return;

        // 仅处理定制更新消息
        if (!event.data || event.data.type !== 'theme-custom-update') return;

        const cssVars = event.data.cssVars;
        if (!cssVars || typeof cssVars !== 'object') return;

        // 白名单校验
        const allowedVars = [
            '--primary', '--secondary', '--accent',
            '--bg', '--bg-secondary',
            '--text', '--text-secondary', '--border',
            '--radius', '--shadow',
            '--font-heading', '--font-body',
            '--sidebar-pos', '--content-width', '--header-style',
            '--logo-url', '--logo-max-height',
            '--btn-primary-bg', '--btn-primary-hover',
        ];

        // 查找或创建覆盖<style>标签
        let styleEl = document.getElementById('theme-custom-preview-style');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'theme-custom-preview-style';
            document.head.appendChild(styleEl);
        }

        // 生成CSS
        const lines = [':root {'];
        Object.entries(cssVars).forEach(function(entry) {
            const varName = entry[0];
            const value = entry[1];
            if (allowedVars.indexOf(varName) !== -1 && value) {
                if (varName === '--logo-url' && value && value.indexOf('url(') === -1) {
                    lines.push('    ' + varName + ": url('" + value + "');");
                } else {
                    lines.push('    ' + varName + ': ' + value + ';');
                }
            }
        });
        lines.push('}');

        styleEl.textContent = lines.join('\n');
    });

    // 页面加载完成后通知父窗口
    window.addEventListener('load', function() {
        if (window.parent !== window) {
            window.parent.postMessage({ type: 'theme-custom-ready' }, window.location.origin);
        }
    });
})();
