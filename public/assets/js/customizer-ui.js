/**
 * V2.9.9 主题定制器 - UI渲染层 (customizer-ui.js)
 * 职责：颜色选择器、字体/布局渲染、预设、Logo上传、导出、新手引导
 */
(function() {
    'use strict';

    // 引用核心模块（对象引用，同步更新）
    const core = window.ThemeCustomizer || {};
    const state = core.state || {};
    const UndoManager = core.UndoManager || {};
    const apiGet = core.apiGet || function(){};
    const apiPost = core.apiPost || function(){};
    const debouncedPreview = core.debouncedPreview || function(){};
    const showToast = core.showToast || function(){};
    const applyCustomizationToUI = core.applyCustomizationToUI || function(){};
    const sendCustomToPreview = core.sendCustomToPreview || function(){};
    const getCurrentValue = core.getCurrentValue || function(){ return ''; };
    const loadDefaults = core.loadDefaults || function(){};
    const loadVariants = core.loadVariants || function(){};

    // === 渲染颜色选择器 ===
    function renderColorPickers() {
        const $grid = $('#colorGrid');
        $grid.empty();

        const cssVars = state.defaults.css_vars || {};
        const colorVars = {};

        Object.entries(cssVars).forEach(function(entry) {
            const varName = entry[0];
            const def = entry[1];
            if (def.type === 'color' && def.group !== '按钮') {
                colorVars[varName] = def;
            }
        });

        Object.entries(colorVars).forEach(function(entry) {
            const varName = entry[0];
            const def = entry[1];
            const id = varName.replace(/^--/, '').replace(/-/g, '_');
            $grid.append(
                '<div class="color-row">' +
                '<label>' + (def.label || varName) + '</label>' +
                '<div class="d-flex align-items-center gap-2">' +
                '<div class="pickr-wrapper"><input type="text" id="pickr-' + id + '"></div>' +
                '<input type="text" class="form-control form-control-sm hex-input" id="hex-' + id + '" placeholder="#000000" style="width:100px;">' +
                '</div></div>'
            );
        });

        Object.entries(colorVars).forEach(function(entry) {
            const varName = entry[0];
            const def = entry[1];
            initPickr(varName, def);
        });

        initPickr('--btn-primary-bg', { default: getCurrentValue('--btn-primary-bg') || 'var(--primary)', label: '按钮主色' });
        initPickr('--btn-primary-hover', { default: getCurrentValue('--btn-primary-hover') || '#1d4ed8', label: '按钮悬停色' });
    }

    function initPickr(varName, def, containerId, hexId) {
        const id = containerId || 'pickr-' + varName.replace(/^--/, '').replace(/-/g, '_');
        const hid = hexId || 'hex-' + varName.replace(/^--/, '').replace(/-/g, '_');
        const el = document.getElementById(id);
        if (!el) return;

        const currentValue = getCurrentValue(varName) || def.default || '#3b82f6';
        const initColor = currentValue.startsWith('var(') ? '#3b82f6' : currentValue;

        const pickr = Pickr.create({
            el: el,
            theme: 'classic',
            default: initColor,
            swatches: [
                '#3b82f6', '#2563eb', '#1d4ed8',
                '#ef4444', '#dc2626', '#b91c1c',
                '#22c55e', '#16a34a', '#15803d',
                '#f59e0b', '#d97706', '#b45309',
                '#8b5cf6', '#7c3aed', '#6d28d9',
                '#06b6d4', '#0891b2', '#0e7490',
                '#ec4899', '#db2777', '#be185d',
                '#1e293b', '#334155', '#64748b',
            ],
            components: {
                preview: true,
                opacity: false,
                hue: true,
                interaction: {
                    hex: true,
                    input: true,
                    save: true,
                },
            },
        });

        pickr.on('save', function(color) {
            const hex = color.toHEXA().toString();
            $('#' + hid).val(hex);
            state.customization[varName] = hex;
            debouncedPreview();
        });

        pickr.on('change', function(color) {
            const hex = color.toHEXA().toString();
            $('#' + hid).val(hex);
            state.customization[varName] = hex;
            UndoManager.push();
            debouncedPreview();
        });

        state.pickrs[varName] = pickr;

        $('#' + hid).off('input').on('input', function() {
            const val = $(this).val();
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                pickr.setColor(val);
                state.customization[varName] = val;
                UndoManager.push();
                debouncedPreview();
            }
        });
    }

    // === 渲染字体选择器 ===
    function renderFontSelectors() {
        const fontPresets = state.presets.fonts || state.defaults.fonts || {};
        const $heading = $('#fontHeading');
        const $body = $('#fontBody');
        $heading.empty();
        $body.empty();

        Object.entries(fontPresets).forEach(function(entry) {
            const key = entry[0];
            const preset = entry[1];
            $heading.append('<option value="' + key + '">' + preset.label + '</option>');
            $body.append('<option value="' + key + '">' + preset.label + '</option>');
        });

        $heading.off('change').on('change', function() {
            const key = $(this).val();
            const preset = fontPresets[key];
            if (preset) {
                state.customization['--font-heading'] = preset.heading;
                $('#headingPreview').css('font-family', preset.heading);
                UndoManager.push();
                debouncedPreview();
            }
        });

        $body.off('change').on('change', function() {
            const key = $(this).val();
            const preset = fontPresets[key];
            if (preset) {
                state.customization['--font-body'] = preset.body;
                $('#bodyPreview').css('font-family', preset.body);
                UndoManager.push();
                debouncedPreview();
            }
        });
    }

    // === 渲染布局选项 ===
    function renderLayoutOptions() {
        setupLayoutOptionGroup('sidebarPosOptions', '--sidebar-pos', 'left');
        setupLayoutOptionGroup('contentWidthOptions', '--content-width', '1200px');
        setupLayoutOptionGroup('headerStyleOptions', '--header-style', 'full');
    }

    function setupLayoutOptionGroup(containerId, cssVar, defaultVal) {
        const currentVal = getCurrentValue(cssVar) || defaultVal;
        const $container = $('#' + containerId);

        $container.find('.layout-opt').each(function() {
            if ($(this).data('value') === currentVal) {
                $(this).addClass('active');
            }
        });

        $container.find('.layout-opt').off('click').on('click', function() {
            $container.find('.layout-opt').removeClass('active');
            $(this).addClass('active');
            state.customization[cssVar] = String($(this).data('value'));
            UndoManager.push();
            debouncedPreview();
        });
    }

    // === 渲染配色预设 ===
    function renderColorPresets(data) {
        const $container = $('#presetGrid');
        if (!$container.length) return;
        $container.empty();

        const systemPresets = data.system || [];
        const themePresets = data.theme || [];
        const all = [];

        systemPresets.forEach(function(p, i) {
            all.push({ type: 'system', index: i, preset: p });
        });
        themePresets.forEach(function(p, i) {
            all.push({ type: 'theme', index: i, preset: p });
        });

        if (all.length === 0) {
            $container.append('<div class="text-muted small">暂无预设方案</div>');
            return;
        }

        all.forEach(function(item) {
            const p = item.preset;
            const color = p.preview_color || (p.css_vars && p.css_vars['--primary']) || '#3b82f6';
            $container.append(
                '<div class="preset-card" onclick="applyColorPreset(' + item.index + ', \'' + item.type + '\')" title="' + (p.description || p.name) + '">' +
                '<div class="preset-swatch" style="background:' + color + ';"></div>' +
                '<div class="preset-name">' + p.name + '</div>' +
                '</div>'
            );
        });
    }

    // V2.9.8 C-1: 应用配色预设
    window.applyColorPreset = function(index, type) {
        apiGet('theme_custom/colorPresets', { theme: state.themeId }).done(function(res) {
            if (res.code !== 0) return;
            const data = res.data || {};
            const presets = type === 'system' ? (data.system || []) : (data.theme || []);
            const preset = presets[index];
            if (!preset || !preset.css_vars) return;

            UndoManager.push();

            Object.entries(preset.css_vars).forEach(function(entry) {
                const key = entry[0];
                const value = entry[1];
                state.customization[key] = value;
                if (state.pickrs[key] && !value.startsWith('var(')) {
                    try { state.pickrs[key].setColor(value); } catch(e) {}
                }
                const hid = 'hex-' + key.replace(/^--/, '').replace(/-/g, '_');
                $('#' + hid).val(value);
            });

            applyCustomizationToUI();
            debouncedPreview();
            showToast('已应用预设: ' + preset.name, 'success');
        });
    };

    // =====================================================
    // V2.9.8 第二轮增强：B-1 预设12色卡 + 智能推荐 + 恢复默认
    // =====================================================

    function renderEnhancedColorPresets(data, recommendedKey) {
        const $container = $('#presetGrid');
        if (!$container.length) return;
        $container.empty().addClass('preset-grid--12');

        const systemPresets = data.system || [];
        const themePresets = data.theme || [];
        const all = [];

        systemPresets.forEach(function(p, i) {
            all.push({ type: 'system', index: i, preset: p, key: p.key || ('sys_' + i) });
        });
        themePresets.forEach(function(p, i) {
            all.push({ type: 'theme', index: i, preset: p, key: p.key || ('theme_' + i) });
        });

        if (all.length === 0) {
            $container.append('<div class="text-muted small">暂无预设方案</div>');
            return;
        }

        all.forEach(function(item) {
            const p = item.preset;
            const color = p.preview_color || (p.css_vars && p.css_vars['--primary']) || '#3b82f6';
            const gradient = p.preview_gradient || color;
            const isRecommended = recommendedKey && item.key === recommendedKey;

            $container.append(
                '<div class="preset-card' + (isRecommended ? ' recommended' : '') + '" onclick="applyColorPreset(' + item.index + ', \'' + item.type + '\')" title="' + (p.description || p.name) + '">' +
                '<div class="preset-color" style="background:' + gradient + ';">' +
                (isRecommended ? '<span class="preset-tag">推荐</span>' : '') +
                '</div>' +
                '<div class="preset-name">' + p.name + '</div>' +
                '</div>'
            );
        });
    }

    function loadRecommendedPreset() {
        apiGet('theme_custom/recommendPreset', { theme: state.themeId }).done(function(res) {
            if (res.code === 0 && res.data && res.data.recommended_preset) {
                state.recommendedPreset = res.data.recommended_preset;
            }
            apiGet('theme_custom/colorPresets', { theme: state.themeId }).done(function(res2) {
                if (res2.code === 0) {
                    renderEnhancedColorPresets(res2.data || {}, state.recommendedPreset);
                }
            });
        });
    }

    // B-1/C-2: 恢复默认配色
    window.resetToDefaults = function() {
        if (!confirm('确定要恢复模板默认配色吗？')) return;

        UndoManager.push();
        apiGet('theme_custom/defaultVars', { theme: state.themeId }).done(function(res) {
            if (res.code !== 0) return;
            const defaultVars = res.data.default_vars || {};
            const cssVars = defaultVars.css_vars || {};

            Object.entries(cssVars).forEach(function(entry) {
                const key = entry[0];
                const meta = entry[1];
                if (meta.default && !meta.default.startsWith('var(')) {
                    state.customization[key] = meta.default;
                    if (state.pickrs[key]) {
                        state.pickrs[key].setColor(meta.default);
                    }
                }
            });

            debouncedPreview();
            showToast('已恢复为默认配色', 'success');
        });
    };

    // === 事件监听与Logo上传 ===
    function setupEventListeners() {
        $('#logoUploadArea').on('click', function() {
            $('#logoFileInput').trigger('click');
        });

        $('#logoUploadArea').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('border-primary');
        }).on('dragleave drop', function() {
            $(this).removeClass('border-primary');
        }).on('drop', function(e) {
            e.preventDefault();
            const file = e.originalEvent.dataTransfer.files[0];
            if (file) uploadLogo(file);
        });

        $('#logoFileInput').on('change', function() {
            const file = this.files[0];
            if (file) uploadLogo(file);
        });

        $('#logoMaxHeight').on('input', function() {
            const val = $(this).val();
            $('#logoHeightVal').text(val);
            state.customization['--logo-max-height'] = val + 'px';
            UndoManager.push();
            debouncedPreview();
        });

        $('#variantSelect').on('change', function() {
            const variant = $(this).val();
            state.currentVariant = variant;
            apiPost('theme_custom/activate', {
                theme: state.themeId,
                variant: variant,
            }).done(function() {
                loadDefaults().done(function() {
                    renderColorPickers();
                    renderFontSelectors();
                    renderLayoutOptions();
                    applyCustomizationToUI();
                    loadVariants();
                });
            });
        });
    }

    function uploadLogo(file) {
        if (!file.type.startsWith('image/')) {
            showToast('请上传图片文件', 'danger');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            showToast('图片不能超过2MB', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('theme', state.themeId);

        $.ajax({
            url: '/admin/theme_custom/uploadLogo',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
        }).done(function(res) {
            if (res.code === 0 && res.data && res.data.url) {
                state.customization['--logo-url'] = res.data.url;
                $('#logoPreviewImg').attr('src', res.data.url);
                $('#logoPreviewWrap').show();
                $('#logoUploadArea').hide();
                UndoManager.push();
                debouncedPreview();
                showToast('Logo上传成功', 'success');
            } else {
                showToast(res.msg || '上传失败', 'danger');
            }
        }).fail(function() {
            showToast('上传请求失败', 'danger');
        });
    }

    window.removeLogo = function() {
        state.customization['--logo-url'] = '';
        delete state.customization['--logo-url'];
        $('#logoPreviewWrap').hide();
        $('#logoUploadArea').show();
        $('#logoFileInput').val('');
        UndoManager.push();
        debouncedPreview();
    };

    // V2.9.8 C-2: 导出前预览确认
    window.handleExportWithPreview = function() {
        $.getJSON('/admin/theme_custom/previewExport?theme=' + encodeURIComponent(state.themeId), function(res) {
            if (res.code !== 0) {
                showToast(res.msg || '预览加载失败', 'danger');
                return;
            }
            const d = res.data || {};
            if (!d.has_customization) {
                window.location.href = '/admin/theme_custom/export?theme=' + encodeURIComponent(state.themeId);
                return;
            }
            const confirmed = confirm(
                '导出确认\n\n' +
                '主题: ' + d.theme_name + '\n' +
                '激活变体: ' + (d.active_variant || 'default') + '\n' +
                '变体数量: ' + d.variant_count + '\n\n' +
                '定制摘要: ' + d.summary + '\n\n' +
                '确定要导出吗？'
            );
            if (confirmed) {
                window.location.href = '/admin/theme_custom/export?theme=' + encodeURIComponent(state.themeId);
            }
        }).fail(function() {
            showToast('预览请求失败', 'danger');
        });
    };

    // V2.9.8 B-3: 增强导出
    window.handleExportEnhanced = function() {
        apiGet('theme_custom/previewExport', { theme: state.themeId }).done(function(res) {
            if (res.code !== 0) return;
            const d = res.data || {};

            if (!d.has_customization) {
                window.location.href = '/admin/theme_custom/export?theme=' + encodeURIComponent(state.themeId);
                return;
            }

            const confirmed = confirm(
                '\u2705 导出确认\n\n' +
                '主题: ' + (d.theme_name || '') + '\n' +
                '定制摘要: ' + (d.summary || '') + '\n\n' +
                '确定要导出吗？'
            );
            if (confirmed) {
                window.location.href = '/admin/theme_custom/export?theme=' + encodeURIComponent(state.themeId);
            }
        });
    };

    // B-3: 导出成功后快捷操作按钮
    window.showExportSuccessActions = function(themeId) {
        const $modal = $(
            '<div class="modal fade" tabindex="-1">' +
            '<div class="modal-dialog modal-sm modal-dialog-centered">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">\u2705 导出成功</h5></div>' +
            '<div class="modal-body">' +
            '<div class="d-grid gap-2">' +
            '<button class="btn btn-primary" onclick="window.open(\'/theme/preview/' + themeId + '\', \'_blank\');$(this).closest(\'.modal\').modal(\'hide\')">\uD83D\uDC41 预览新模板</button>' +
            '<button class="btn btn-outline-primary" onclick="window.location.href=\'/admin/market\';$(this).closest(\'.modal\').modal(\'hide\')">\uD83C\uDFEA 浏览市场</button>' +
            '<button class="btn btn-outline-secondary" onclick="$(this).closest(\'.modal\').modal(\'hide\')">\u270F 继续编辑</button>' +
            '</div></div></div></div></div>'
        );
        $('body').append($modal);
        $modal.modal('show');
        $modal.on('hidden.bs.modal', function() { $modal.remove(); });
    };

    // =====================================================
    // V2.9.8 第二轮增强：B-2 新手引导 + 面板折叠 + 实时反馈
    // =====================================================

    const OnboardingGuide = {
        steps: [
            { icon: '\uD83C\uDFA8', title: '选配色', desc: '从预设方案或色盘中选择喜欢的配色' },
            { icon: '\uD83D\uDCDD', title: '调字体', desc: '调整标题和正文的字体系列和大小' },
            { icon: '\uD83D\uDCBE', title: '保存预览', desc: '一键保存修改，预览整体效果' },
        ],
        currentStep: 0,
        dismissed: localStorage.getItem('onboarding_dismissed') === '1',

        init: function() {
            if (this.dismissed) return;
            const $guide = $('#onboarding-guide');
            if (!$guide.length) return;
            this.render();
        },

        render: function() {
            const $guide = $('#onboarding-guide');
            if (!$guide.length || this.dismissed) {
                if ($guide.length) $guide.hide();
                return;
            }
            $guide.show().html(
                '<div class="onboarding-bar">' +
                '<div class="onboarding-steps">' +
                this.steps.map(function(step, i) {
                    return '<div class="onboarding-step' + (i <= OnboardingGuide.currentStep ? ' active' : '') + '">' +
                        '<span class="step-icon">' + step.icon + '</span>' +
                        '<span class="step-label">' + step.title + '</span>' +
                        (i < OnboardingGuide.steps.length - 1 ? '<span class="step-arrow">\u2192</span>' : '') +
                        '</div>';
                }).join('') +
                '</div>' +
                '<button class="onboarding-close" onclick="OnboardingGuide.dismiss()">\u2715</button>' +
                '</div>'
            );
        },

        nextStep: function() {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
                this.render();
            }
        },

        dismiss: function() {
            this.dismissed = true;
            localStorage.setItem('onboarding_dismissed', '1');
            this.render();
        },
    };
    window.OnboardingGuide = OnboardingGuide;

    const CollapsibleSection = {
        init: function() {
            $('.section-collapsible').each(function(index) {
                const $section = $(this);
                const $header = $section.find('.section-header');
                const $body = $section.find('.section-body');

                if (index === 0) {
                    $header.addClass('expanded');
                    $body.css('max-height', $body[0].scrollHeight + 'px');
                } else {
                    $header.removeClass('expanded');
                    $body.css('max-height', '0');
                }

                $header.off('click').on('click', function() {
                    const isExpanded = $header.toggleClass('expanded').hasClass('expanded');
                    $body.css('max-height', isExpanded ? $body[0].scrollHeight + 'px' : '0');
                });
            });
        },
    };

    function showPreviewFeedback(changedVar, newValue) {
        const $frame = $('#previewFrame');
        if ($frame.length) {
            $frame.css({
                'transition': 'box-shadow 0.3s ease',
                'box-shadow': '0 0 0 2px var(--primary, #2563EB), 0 0 12px rgba(37,99,235,0.3)'
            });
            setTimeout(function() { $frame.css('box-shadow', 'none'); }, 600);
        }

        const $toast = $('<div class="custom-toast">\u2713 预览已更新</div>');
        $('body').append($toast);
        setTimeout(function() { $toast.remove(); }, 2000);

        if (!OnboardingGuide.dismissed) {
            OnboardingGuide.nextStep();
        }
    }

    // 暴露UI模块到全局
    window.ThemeCustomizerUI = {
        renderColorPickers: renderColorPickers,
        renderFontSelectors: renderFontSelectors,
        renderLayoutOptions: renderLayoutOptions,
        renderColorPresets: renderColorPresets,
        setupEventListeners: setupEventListeners,
        loadRecommendedPreset: loadRecommendedPreset,
        OnboardingGuide: OnboardingGuide,
        CollapsibleSection: CollapsibleSection,
        renderEnhancedColorPresets: renderEnhancedColorPresets,
    };
})();
