/**
 * 企业版系统设置页 JS（V2.9.47 从 corporate/system_config.html 内联脚本迁移为外部 JS）
 *
 * 关键修复：原内联脚本在 PJAX 局部刷新时不会执行（pjax.js 在 res.js_src 非空时
 * 走 loadExternalScripts 分支、跳过 executeInlineScript），导致主题列表一直停留在
 * "加载中"。改为外部 JS 后，通过 pjax:complete 事件重新初始化。
 */
(function($) {
    'use strict';

    // ========== V2.9.2 网站Logo上传与预览 ==========
    function uploadLogo(input) {
        var file = input.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('file', file);
        $.ajax({
            url: '/api/upload/image',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.code === 0 && res.data && res.data.url) {
                    $('#siteLogoInput').val(res.data.url);
                    $('#logoPreview img').attr('src', res.data.url);
                    $('#logoPreview').show();
                    showToast('Logo上传成功', 'success');
                } else {
                    showToast(res.msg || '上传失败', 'danger');
                }
            },
            error: function() {
                showToast('网络错误', 'danger');
            }
        });
        input.value = '';
    }

    function previewLogo() {
        $('#logoPreview').toggle();
    }

    function openMediaBrowser() {
        $('#mediaBrowserIframe').attr('src', '/admin/media/select');
        $('#mediaBrowserModal').modal('show');
    }

    $(document).on('change', '#logoIconOnly', function() {
        $('#logoNameGroup').toggle(this.checked);
    });

    window.addEventListener('message', function(e) {
        var data = e.data || {};
        if (data.mceAction === 'insertContent' && data.content) {
            var match = data.content.match(/src=["']([^"']+)["']/);
            if (match && match[1]) {
                $('#siteLogoInput').val(match[1]);
                $('#logoPreview img').attr('src', match[1]);
                $('#logoPreview').show();
                $('#mediaBrowserModal').modal('hide');
                showToast('已选择Logo图片', 'success');
            }
        }
    });

    // ========== V2.4 模板选择器逻辑 ==========
    var selectedFrontend = '';
    var selectedBackend = '';

    function loadThemes() {
        var $btn = $('#refreshThemeBtn');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>加载中');
        $.get('/admin/system/allTemplates', function(res) {
            if (res.code !== 0 || !res.data) {
                $('#frontendThemeList').html('<div class="text-danger small p-3"><i class="bi bi-exclamation-triangle me-1"></i>加载失败' + (res.msg ? ': ' + res.msg : '') + '</div>');
                $('#backendThemeList').html('<div class="text-danger small p-3"><i class="bi bi-exclamation-triangle me-1"></i>加载失败' + (res.msg ? ': ' + res.msg : '') + '</div>');
                return;
            }
            renderThemeCards('frontendThemeList', res.data.frontend.themes, res.data.frontend.active, 'frontend', true);
            renderThemeCards('backendThemeList', res.data.admin.themes, res.data.admin.active, 'admin', true);
        }).fail(function() {
            $('#frontendThemeList').html('<div class="text-danger small p-3"><i class="bi bi-exclamation-triangle me-1"></i>网络错误，请点击刷新重试</div>');
            $('#backendThemeList').html('<div class="text-danger small p-3"><i class="bi bi-exclamation-triangle me-1"></i>网络错误，请点击刷新重试</div>');
        }).always(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
    // 确保全局可访问（PJAX环境下 onclick 可能失效）
    window.loadThemes = loadThemes;
    // 委托事件绑定（比内联 onclick 更可靠）
    $(document).on('click', '#refreshThemeBtn', function(e) {
        e.preventDefault();
        loadThemes();
    });

    function renderThemeCards(containerId, themes, active, type, allowSelect) {
        var $container = $('#' + containerId);
        $container.empty();
        if (!themes || !themes.length) { $container.html('<div class="text-muted small text-center py-3">暂无可用主题</div>'); return; }
        $.each(themes, function(i, t) {
            var isActive = t.name === active;
            var selClass = isActive ? ' selected-theme-card' : '';
            var badgeHtml = isActive ? '<span class="theme-active-badge"><i class="bi bi-check-lg me-1"></i>当前</span>' : '';
            var overlayHtml = isActive ? '<div class="theme-overlay"></div>' : '';
            var cardHtml = '<div class="theme-card' +
                selClass +
                '" data-theme="' + t.name + '" data-type="' + type + '">' +
                '<div class="theme-preview"' +
                    (t.screenshot ? 'style="background:#f1f5f5;">' : '>') +
                    (t.screenshot
                        ? '<img src="/template/' + (type === 'admin' ? 'admin/' : 'themes/') + t.name + '/' + t.screenshot + '" loading="lazy">'
                        : '<i class="bi bi-' + (type === 'admin' ? 'gear-fill' : 'palette2') + ' display-3"></i>'
                    ) +
                '</div>' +
                '<div class="theme-info">' +
                    '<strong class="theme-label">' + t.label + '</strong>' +
                    badgeHtml +
                    '<small class="theme-desc">' + (t.description || '企业风格模板') + '</small>' +
                    '<small class="theme-meta">v' + (t.version || '1.0') + ' &middot; ' + (t.author || 'AI-CMS') + (t.supports && t.supports.length ? ' &middot; 支持: ' + t.supports.join(', ') : '') + '</small>' +
                '</div>' +
                overlayHtml +
                '</div>';
            $container.append(cardHtml);

            // 绑定点击事件
            if (allowSelect) {
                $container.find('.theme-card:last').off('click').on('click', function() {
                    var $card = $(this);
                    var themeName = $card.data('theme');
                    var cardType = $card.data('type');

                    // 如果已是选中态则不重复处理
                    if ($card.hasClass('selected-theme-card')) return;

                    // 清除同容器内所有卡片的选中状态
                    $container.find('.theme-card').each(function() {
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

                    // 联动：如果开启联动，自动同步另一个
                    if ($('#linkThemesToggle').is(':checked')) {
                        var otherType = cardType === 'frontend' ? 'admin' : 'frontend';
                        var otherTargetId = cardType === 'frontend' ? 'backendThemeList' : 'frontendThemeList';
                        var $otherContainer = $('#' + otherTargetId);

                        // 清除另一侧所有选中
                        $otherContainer.find('.theme-card').each(function() {
                            $(this).removeClass('selected-theme-card')
                                   .find('.theme-active-badge').remove()
                                   .end().find('.theme-overlay').remove()
                                   .end().find('.theme-label').css({color:'#1e293b'})
                                   .end().find('.theme-preview i').css({color:'#94a3b8'});
                        });
                        // 查找同名主题并高亮
                        var $sameCard = $otherContainer.find('.theme-card[data-theme="'+themeName+'"][data-type="'+otherType+'"]');
                        if ($sameCard.length) {
                            $sameCard.addClass('selected-theme-card');
                            $sameCard.find('.theme-info strong:first').after(
                                '<span class="theme-active-badge"><i class="bi bi-check-lg me-1"></i>已选</span>'
                            );
                            $sameCard.append('<div class="theme-overlay"></div>');
                            $sameCard.find('.theme-label').css({color:'#0369a1'});
                            $sameCard.find('.theme-preview i').css({color:'#3b82f6'});
                            if (otherType === 'admin') selectedBackend = themeName;
                            else selectedFrontend = themeName;
                        }
                    }

                    showToast('已选择 "' + t.label + '"，请点击「保存配置」生效', 'info');
                });
            }
        });
    }

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
        var switchPromises = [];
        if (typeof selectedFrontend !== 'undefined' && selectedFrontend && selectedFrontend !== '') {
            switchPromises.push($.post('/admin/system/setTheme', { theme: selectedFrontend }));
        }
        if (typeof selectedBackend !== 'undefined' && selectedBackend && selectedBackend !== '') {
            switchPromises.push($.post('/admin/system/setAdminTheme', { admin_theme: selectedBackend }));
        }

        if (switchPromises.length === 0) {
            ajaxPost($form.attr('action'), $form.serialize());
        } else {
            Promise.all(switchPromises).then(function() {
                ajaxPost($form.attr('action'), $form.serialize());
            }).catch(function() {
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
        $(document).on('pjax:complete', initPage);
    }
})(jQuery);
