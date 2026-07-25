/**
 * 移动端编辑器适配JS — V2.9.30 UX-1
 * 工具栏折叠、按钮适配
 */
$(function() {
    // 检测是否移动端
    function isMobile() {
        return window.innerWidth <= 768;
    }

    if (isMobile()) {
        // 编辑器工具栏折叠
        var toolbar = $('.tox-toolbar__primary, .editor-toolbar');
        if (toolbar.length) {
            toolbar.css({
                'overflow-x': 'auto',
                '-webkit-overflow-scrolling': 'touch',
                'flex-wrap': 'nowrap'
            });
        }

        // 增大按钮触控区域
        $('.btn, .tox-tbtn').each(function() {
            var $btn = $(this);
            if (!$btn.hasClass('dropdown-toggle')) {
                $btn.css({
                    'min-width': '44px',
                    'min-height': '44px'
                });
            }
        });

        // 表单输入框增大
        $('input[type="text"], input[type="email"], input[type="password"], textarea, select').each(function() {
            if (!$(this).hasClass('form-select-sm')) {
                $(this).addClass('form-control-lg');
            }
        });
    }

    // 监听窗口旋转
    $(window).on('orientationchange', function() {
        setTimeout(function() { location.reload(); }, 300);
    });
});
