/**
 * 系统设置页 JS（V2.9.47 从 system_config.html 内联脚本迁移为外部 JS）
 *
 * 关键修复：原内联脚本在 PJAX 局部刷新时不会执行（因为 pjax.js 在 res.js_src
 * 非空时走 loadExternalScripts 分支、跳过 executeInlineScript），导致主题列表
 * 一直停留在"加载中"。改为外部 JS 后，通过 pjax:complete 事件重新初始化，
 * 与 online-upgrade.js 的 initPage 模式一致。
 */
(function($) {
    'use strict';

    // ========== V2.9.2 网站Logo上传与预览 ==========
    function uploadLogo(input) {
        if (!input.files || !input.files[0]) return;
        var formData = new FormData();
        var logoName = $('input[name="logo_name"]').val() || '';
        formData.append('file', input.files[0]);
        formData.append('logo_name', logoName);
        $.ajax({
            url: '/admin/system/uploadLogo',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.code === 1 || res.code === 200 || res.success) {
                    $('#siteLogoInput').val(res.data.url);
                    $('#logoPreview').show().find('img').attr('src', res.data.url);
                    showToast('Logo 上传成功', 'success');
                } else {
                    showToast(res.msg || 'Logo 上传失败', 'error');
                }
            },
            error: function() {
                showToast('Logo 上传失败，请重试', 'error');
            }
        });
    }

    function previewLogo() {
        var url = $('#siteLogoInput').val();
        if (url) {
            $('#logoPreview').show().find('img').attr('src', url);
        } else {
            $('#logoPreview').hide();
        }
    }

    function openMediaBrowser() {
        // V2.9.42: 复用全局媒体选择器
        if (typeof openMediaSelect === 'function') {
            window._mediaTargetInput = '#siteLogoInput';
            window.mediaSelectCallback = function(url) {
                $('#siteLogoInput').val(url);
                previewLogo();
            };
            openMediaSelect();
        }
    }

    // ========== V2.4 模板选择器逻辑 ==========
    var selectedFrontend = '';
    var selectedBackend = '';

    function loadThemes() {
        var $btn = $('#refreshThemeBtn');
        if ($btn.length) {
            $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin"></i> 加载中...');
        }
        $.get('/admin/system/allTemplates', function(res) {
            if ($btn.length) $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> 刷新');
            // 后端 success() 默认 code=0；兼容旧版/第三方可能返回 code=1
            if ((res.code === 0 || res.code === 1) && res.data) {
                var fe = res.data.frontend || {};
                var be = res.data.admin || {};
                renderThemeCards('frontendThemeList', fe.themes || [], fe.active, 'frontend', true);
                renderThemeCards('backendThemeList', be.themes || [], be.active, 'backend', true);
            } else if (res.code === 1) {
                // 兼容旧结构
                renderThemeCards('frontendThemeList', res.frontend || [], res.currentFrontend, 'frontend', true);
                renderThemeCards('backendThemeList', res.backend || [], res.currentBackend, 'backend', true);
            } else {
                $('#frontendThemeList').html('<div class="text-center text-danger py-3">加载失败：' + (res.msg || '未知错误') + '</div>');
                $('#backendThemeList').html('<div class="text-center text-danger py-3">加载失败：' + (res.msg || '未知错误') + '</div>');
            }
        }, 'json').fail(function() {
            if ($btn.length) $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> 刷新');
            $('#frontendThemeList').html('<div class="text-center text-danger py-3">网络错误，点击「刷新」重试</div>');
            $('#backendThemeList').html('<div class="text-center text-danger py-3">网络错误，点击「刷新」重试</div>');
        });
    }

    // 暴露给全局，方便 pjax:complete 重新调用
    window.loadThemes = loadThemes;

    $(document).on('click', '#refreshThemeBtn', function() {
        loadThemes();
    });

    function renderThemeCards(containerId, themes, active, type, allowSelect) {
        var $container = $('#' + containerId);
        if (!$container.length) return;
        if (!themes || !themes.length) {
            $container.html('<div class="text-center text-muted py-3">暂无可用模板</div>');
            return;
        }
        var html = '';
        $.each(themes, function(i, t) {
            var isActive = (t.name === active);
            html += '<div class="theme-card' + (isActive ? ' selected-theme-card' : '') + '" data-theme="' + t.name + '" data-type="' + type + '">';
            html += '  <div class="theme-preview"><i class="bi bi-image"></i></div>';
            html += '  <div class="theme-info"><strong>' + (t.label || t.name) + '</strong>';
            if (t.description) html += '<small class="d-block text-muted">' + t.description + '</small>';
            html += '  </div>';
            if (isActive) html += '<span class="theme-active-badge"><i class="bi bi-check-lg me-1"></i>已选</span>';
            if (allowSelect && !isActive) html += '<button type="button" class="btn btn-sm btn-outline-primary theme-select-btn">选择</button>';
            html += '</div>';
        });
        $container.html(html);
    }

    // 主题卡片点击选中（事件委托，兼容 PJAX 后的新元素）
    $(document).on('click', '.theme-card', function() {
        var $card = $(this);
        var cardType = $card.data('type');
        var themeName = $card.data('theme');
        if (!cardType || !themeName) return;

        // 查询模板信息（从卡片文本获取 label）
        var t = { name: themeName, label: $card.find('.theme-info strong').text() };

        // 取消同类型其他卡片的选中态
        $card.parent().find('.theme-card').each(function() {
            $(this).removeClass('selected-theme-card')
                   .find('.theme-active-badge').remove()
                   .end().find('.theme-overlay').remove()
                   .end().find('.theme-label').css({color:'#1e293b'})
                   .end().find('.theme-preview i').css({color:'#94a3b8'});
        });

        // 设置当前卡片为选中态
        $card.addClass('selected-theme-card');
        $card.find('.theme-info strong:first').after(
            '<span class="theme-active-badge"><i class="bi bi-check-lg me-1"></i>已选</span>'
        );
        $card.append('<div class="theme-overlay"></div>');
        $card.find('.theme-label').css({color:'#0369a1'});
        $card.find('.theme-preview i').css({color:'#3b82f6'});

        // 记录选择
        if (cardType === 'frontend') selectedFrontend = themeName;
        else selectedBackend = themeName;

        showToast('已选择 "' + t.label + '"，请点击「保存配置」生效', 'info');
    });

    // 保存时同时处理主题切换（在原表单submit之前拦截）
    $(document).on('submit', '#configForm', function(e) {
        e.preventDefault();
        var $form = $(this);

        // 处理switch未勾选时不会提交的问题
        $form.find('.form-check-input:not(:checked)').each(function() {
            var name = $(this).attr('name');
            if (name && !$('[name="' + name + '"][type="hidden"]').length) {
                $(this).after('<input type="hidden" name="' + name + '" value="0">');
            }
        });

        // V2.9.45: 直接保存配置，跳过主题切换（避免CSRF干扰）
        // 如果有主题变更，先执行切换API再保存配置
        var switchPromises = [];
        if (typeof selectedFrontend !== 'undefined' && selectedFrontend && selectedFrontend !== '') {
            switchPromises.push($.post('/admin/system/setTheme', { theme: selectedFrontend }));
        }
        if (typeof selectedBackend !== 'undefined' && selectedBackend && selectedBackend !== '') {
            switchPromises.push($.post('/admin/system/setAdminTheme', { admin_theme: selectedBackend }));
        }

        if (switchPromises.length === 0) {
            // 无主题切换，直接保存
            ajaxPost($form.attr('action'), $form.serialize());
        } else {
            Promise.all(switchPromises).then(function() {
                ajaxPost($form.attr('action'), $form.serialize());
            }).catch(function() {
                // 主题切换失败不影响配置保存
                console.warn('主题切换失败，继续保存配置');
                ajaxPost($form.attr('action'), $form.serialize());
            });
        }
    });

    // V2.9.24 J-2: 配置搜索功能
    $(document).on('input', '#configSearchInput', function() {
        var keyword = $(this).val().toLowerCase().trim();
        if (!keyword) {
            $('.config-group-card').show();
            $('.config-group-card .collapse').addClass('show');
            $('.config-group-card .row').show();
            return;
        }
        $('.config-group-card').each(function() {
            var $card = $(this);
            var hasMatch = false;
            $card.find('.row').each(function() {
                var text = $(this).text().toLowerCase();
                var match = text.indexOf(keyword) > -1;
                $(this).toggle(match);
                if (match) hasMatch = true;
            });
            $card.toggle(hasMatch);
            if (hasMatch) {
                $card.find('.collapse').addClass('show');
            }
        });
    });
    $(document).on('click', '#configSearchClear', function() {
        $('#configSearchInput').val('').trigger('input');
    });

    // ========== 页面初始化（兼容 PJAX） ==========
    function initPage() {
        // 仅当系统设置页（含主题列表容器）存在时才初始化主题
        if ($('#frontendThemeList').length || $('#backendThemeList').length) {
            loadThemes();
        }
    }

    if (typeof jQuery !== 'undefined') {
        if (document.readyState === 'loading') {
            $(document).ready(initPage);
        } else {
            initPage();
        }
        // PJAX 局部刷新后重新初始化（菜单点击进入时触发）
        $(document).on('pjax:complete', initPage);
    }
})(jQuery);
