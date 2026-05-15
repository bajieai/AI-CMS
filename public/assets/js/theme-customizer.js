/**
 * V2.9.7 主题定制面板前端JS
 * 
 * 功能：颜色选择器(Pickr) + 字体/布局选择 + Logo上传 + iframe预览 + postMessage
 */
(function() {
    'use strict';

    // === 状态管理 ===
    const state = {
        themeId: '',
        currentVariant: 'default',
        defaults: {},
        customization: {},
        presets: { fonts: {}, layout: {} },
        pickrs: {},
        debounceTimer: null,
    };

    // V2.9.8 A-2: 撤销/重做管理器
    const UndoManager = {
        stack: [],
        redoStack: [],
        pointer: -1,
        maxDepth: 30,
        savePointIndex: -1,
        debounceTimer: null,

        // 深拷贝（structuredClone优先，降级JSON）
        clone(obj) {
            if (typeof window.structuredClone === 'function') {
                try { return window.structuredClone(obj); } catch(e) {}
            }
            return JSON.parse(JSON.stringify(obj));
        },

        // 捕获当前状态的快照
        snapshot() {
            return {
                customization: this.clone(state.customization),
                currentVariant: state.currentVariant,
            };
        },

        // 推入撤销栈（防抖150ms）
        push() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this._doPush();
            }, 150);
        },

        _doPush() {
            const snap = this.snapshot();
            // 如果与当前状态相同，不重复记录
            if (this.pointer >= 0) {
                const last = this.stack[this.pointer];
                if (JSON.stringify(last.customization) === JSON.stringify(snap.customization)) {
                    return;
                }
            }
            // 丢弃pointer之后的状态（重做栈）
            if (this.pointer < this.stack.length - 1) {
                this.stack = this.stack.slice(0, this.pointer + 1);
            }
            this.stack.push(snap);
            // 超出深度时丢弃最旧的
            if (this.stack.length > this.maxDepth) {
                this.stack.shift();
                if (this.savePointIndex >= 0) this.savePointIndex--;
            } else {
                this.pointer++;
            }
            this.redoStack = [];
            this.updateUI();
        },

        // 撤销
        undo() {
            if (!this.canUndo()) return;
            this.pointer--;
            this._restore(this.stack[this.pointer]);
            this.updateUI();
        },

        // 重做
        redo() {
            if (!this.canRedo()) return;
            this.pointer++;
            if (this.pointer < this.stack.length) {
                this._restore(this.stack[this.pointer]);
            }
            this.updateUI();
        },

        _restore(snap) {
            state.customization = this.clone(snap.customization);
            applyCustomizationToUI();
            sendCustomToPreview();
        },

        canUndo() { return this.pointer > 0; },
        canRedo() { return this.pointer < this.stack.length - 1; },

        hasUnsavedChanges() {
            return this.pointer !== this.savePointIndex;
        },

        markSavePoint() {
            this.savePointIndex = this.pointer;
            this.updateUI();
        },

        clear() {
            this.stack = [];
            this.redoStack = [];
            this.pointer = -1;
            this.savePointIndex = -1;
            this.updateUI();
        },

        updateUI() {
            const undoBtn = document.getElementById('btnUndo');
            const redoBtn = document.getElementById('btnRedo');
            const undoCount = document.getElementById('undoCount');
            const redoCount = document.getElementById('redoCount');
            const saveStatus = document.getElementById('saveStatus');
            if (undoBtn) undoBtn.disabled = !this.canUndo();
            if (redoBtn) redoBtn.disabled = !this.canRedo();
            if (undoCount) undoCount.textContent = this.pointer > 0 ? this.pointer : '';
            if (redoCount) redoCount.textContent = this.pointer < this.stack.length - 1 ? (this.stack.length - 1 - this.pointer) : '';
            if (saveStatus) {
                saveStatus.textContent = this.hasUnsavedChanges() ? '● 未保存' : '✓ 已保存';
                saveStatus.style.color = this.hasUnsavedChanges() ? '#ef4444' : '#22c55e';
            }
        },

        init() {
            // 初始状态入栈
            this.stack.push(this.snapshot());
            this.pointer = 0;
            this.savePointIndex = 0;
            this.updateUI();
        }
    };

    // === 初始化 ===
        $(document).ready(function() {
        const params = new URLSearchParams(window.location.search);
        state.themeId = params.get('theme') || '';

        if (!state.themeId) {
            showToast('缺少主题参数', 'danger');
            return;
        }

        $('#themeBadge').text(state.themeId);
        loadDefaults();
        loadPresets();
        setupEventListeners();
        loadPreview();
        // V2.9.8 C-2: 导出按钮绑定预览确认
        $('#exportBtn').on('click', function(e) {
            e.preventDefault();
            handleExportWithPreview();
        });

        // V2.9.8 A-2: 初始化撤销栈
        UndoManager.init();

        // V2.9.8 A-2: 键盘快捷键 Ctrl+Z / Ctrl+Shift+Z
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                e.preventDefault();
                if (e.shiftKey) {
                    UndoManager.redo();
                } else {
                    UndoManager.undo();
                }
            }
        });

        // V2.9.8 A-2: 关闭前未保存提示
        window.addEventListener('beforeunload', function(e) {
            if (UndoManager.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = '有未保存的定制变更，确定要离开吗？';
            }
        });
    });

    // === API调用 ===
    function apiGet(url, params) {
        return $.getJSON('/admin/' + url, params || {});
    }

    function apiPost(url, data) {
        return $.ajax({
            url: '/admin/' + url,
            method: 'POST',
            data: data,
            dataType: 'json',
        });
    }

    // === 加载默认参数 ===
    function loadDefaults() {
        apiGet('theme_custom/defaults', { theme: state.themeId }).done(function(res) {
            if (res.code !== 0) {
                showToast(res.msg || '加载默认参数失败', 'danger');
                return;
            }
            state.defaults = res.data.defaults || {};
            state.customization = res.data.customization || {};
            renderColorPickers();
            renderFontSelectors();
            renderLayoutOptions();
            applyCustomizationToUI();
            loadVariants();
        }).fail(function() {
            showToast('网络请求失败', 'danger');
        });
    }

    // === 加载预设 ===
    function loadPresets() {
        apiGet('theme_custom/presets').done(function(res) {
            if (res.code === 0) {
                state.presets = res.data || {};
            }
        });
        // V2.9.8 C-1: 加载配色预设
        apiGet('theme_custom/colorPresets', { theme: state.themeId }).done(function(res) {
            if (res.code === 0) {
                renderColorPresets(res.data || {});
            }
        });
    }

    // V2.9.8 C-1: 渲染配色预设卡片
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

            // A-2: 撤销联动——应用前推入撤销栈
            UndoManager.push();

            Object.entries(preset.css_vars).forEach(function(entry) {
                const key = entry[0];
                const value = entry[1];
                state.customization[key] = value;
                // 同步更新Pickr颜色
                if (state.pickrs[key] && !value.startsWith('var(')) {
                    try { state.pickrs[key].setColor(value); } catch(e) {}
                }
                // 同步更新HEX输入框
                const hid = 'hex-' + key.replace(/^--/, '').replace(/-/g, '_');
                $('#' + hid).val(value);
            });

            applyCustomizationToUI();
            debouncedPreview();
            showToast('已应用预设: ' + preset.name, 'success');
        });
    };

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

        // 渲染主颜色区
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

        // 初始化Pickr实例（主颜色）
        Object.entries(colorVars).forEach(function(entry) {
            const varName = entry[0];
            const def = entry[1];
            initPickr(varName, def);
        });

        // 初始化按钮颜色区Pickr
        initPickr('--btn-primary-bg', { default: getCurrentValue('--btn-primary-bg') || 'var(--primary)', label: '按钮主色' });
        initPickr('--btn-primary-hover', { default: getCurrentValue('--btn-primary-hover') || '#1d4ed8', label: '按钮悬停色' });
    }

    function initPickr(varName, def, containerId, hexId) {
        const id = containerId || 'pickr-' + varName.replace(/^--/, '').replace(/-/g, '_');
        const hid = hexId || 'hex-' + varName.replace(/^--/, '').replace(/-/g, '_');
        const el = document.getElementById(id);
        if (!el) return;

        const currentValue = getCurrentValue(varName) || def.default || '#3b82f6';
        // 如果是var()引用，使用主色
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

        // HEX输入框事件
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

    // === 应用当前定制数据到UI ===
    function applyCustomizationToUI() {
        const custom = state.customization || {};

        // 更新Pickr颜色
        Object.entries(state.pickrs).forEach(function(entry) {
            const varName = entry[0];
            const pickr = entry[1];
            if (custom[varName] && !custom[varName].startsWith('var(')) {
                pickr.setColor(custom[varName]);
                const id = 'hex-' + varName.replace(/^--/, '').replace(/-/g, '_');
                $('#' + id).val(custom[varName]);
            }
        });

        // 更新字体选择
        if (custom['--font-heading']) {
            const fontKey = findFontKey(custom['--font-heading'], 'heading');
            if (fontKey) $('#fontHeading').val(fontKey);
            $('#headingPreview').css('font-family', custom['--font-heading']);
        }
        if (custom['--font-body']) {
            const fontKey = findFontKey(custom['--font-body'], 'body');
            if (fontKey) $('#fontBody').val(fontKey);
            $('#bodyPreview').css('font-family', custom['--font-body']);
        }

        // 更新布局选项
        if (custom['--sidebar-pos']) activateLayoutOpt('sidebarPosOptions', custom['--sidebar-pos']);
        if (custom['--content-width']) activateLayoutOpt('contentWidthOptions', custom['--content-width']);
        if (custom['--header-style']) activateLayoutOpt('headerStyleOptions', custom['--header-style']);

        // 更新Logo
        if (custom['--logo-url']) {
            $('#logoPreviewImg').attr('src', custom['--logo-url']);
            $('#logoPreviewWrap').show();
            $('#logoUploadArea').hide();
        }
        if (custom['--logo-max-height']) {
            const h = parseInt(custom['--logo-max-height']) || 40;
            $('#logoMaxHeight').val(h);
            $('#logoHeightVal').text(h);
        }
    }

    function activateLayoutOpt(containerId, value) {
        $('#' + containerId + ' .layout-opt').removeClass('active');
        $('#' + containerId + ' .layout-opt[data-value="' + value + '"]').addClass('active');
    }

    function findFontKey(fontValue, type) {
        const fonts = state.presets.fonts || state.defaults.fonts || {};
        for (const entry of Object.entries(fonts)) {
            if (entry[1][type] === fontValue) return entry[0];
        }
        return null;
    }

    function getCurrentValue(varName) {
        if (state.customization && state.customization[varName]) {
            return state.customization[varName];
        }
        const cssVars = state.defaults.css_vars || {};
        return cssVars[varName] ? cssVars[varName].default : '';
    }

    // === iframe预览 ===
    function loadPreview() {
        const frame = document.getElementById('previewFrame');
        if (!frame) return;
        frame.src = window.location.origin + '/?preview_custom=1';
    }

    window.refreshPreview = function() {
        const frame = document.getElementById('previewFrame');
        if (frame) frame.contentWindow.location.reload();
    };

    function debouncedPreview() {
        clearTimeout(state.debounceTimer);
        state.debounceTimer = setTimeout(sendCustomToPreview, 300);
    }

    function sendCustomToPreview() {
        const frame = document.getElementById('previewFrame');
        if (!frame || !frame.contentWindow) return;

        const cssOverrides = {};
        const whitelist = [
            '--primary', '--secondary', '--accent',
            '--bg', '--bg-secondary',
            '--text', '--text-secondary', '--border',
            '--radius', '--shadow',
            '--font-heading', '--font-body',
            '--sidebar-pos', '--content-width', '--header-style',
            '--logo-url', '--logo-max-height',
            '--btn-primary-bg', '--btn-primary-hover',
        ];

        whitelist.forEach(function(v) {
            if (state.customization[v]) {
                cssOverrides[v] = state.customization[v];
            }
        });

        frame.contentWindow.postMessage({
            type: 'theme-custom-update',
            cssVars: cssOverrides,
        }, window.location.origin);
    }

    // === 设备预览 ===
    window.setDevice = function(device) {
        const $frame = $('#previewFrame');
        $frame.removeClass('device-desktop device-tablet device-mobile').addClass('device-' + device);
        $('.preview-toolbar .btn').removeClass('active');
        $('.preview-toolbar .btn[data-device="' + device + '"]').addClass('active');
    };

    // === 保存操作 ===
    window.handleSave = function() {
        apiPost('theme_custom/save', {
            theme: state.themeId,
            variant: state.currentVariant,
            data: JSON.stringify(state.customization),
        }).done(function(res) {
            if (res.success || res.code === 0) {
                UndoManager.markSavePoint();
                showToast('定制已保存并应用', 'success');
                refreshPreview();
            } else {
                showToast(res.message || res.msg || '保存失败', 'danger');
            }
        }).fail(function() {
            showToast('网络请求失败', 'danger');
        });
    };

    window.handleReset = function() {
        if (!confirm('确定要重置为默认值吗？所有定制将丢失。')) return;

        apiPost('theme_custom/reset', { theme: state.themeId }).done(function(res) {
            if (res.success || res.code === 0) {
                showToast('已重置为默认', 'success');
                state.customization = {};
                applyCustomizationToUI();
                UndoManager.clear();
                UndoManager.init();
                const cssVars = state.defaults.css_vars || {};
                Object.entries(state.pickrs).forEach(function(entry) {
                    if (cssVars[entry[0]] && cssVars[entry[0]].default) {
                        const d = cssVars[entry[0]].default;
                        if (!d.startsWith('var(')) entry[1].setColor(d);
                    }
                });
                refreshPreview();
            } else {
                showToast(res.message || '重置失败', 'danger');
            }
        });
    };

    window.handleSaveAs = function() {
        const name = prompt('请输入新变体名称：');
        if (!name || !name.trim()) return;

        apiPost('theme_custom/save', {
            theme: state.themeId,
            variant: state.currentVariant,
            data: JSON.stringify(state.customization),
        }).done(function() {
            apiPost('theme_custom/saveAs', {
                theme: state.themeId,
                name: name.trim(),
            }).done(function(res) {
                if (res.success || res.code === 0) {
                    showToast('已另存为变体: ' + name.trim(), 'success');
                    loadVariants();
                } else {
                    showToast(res.message || '另存为失败', 'danger');
                }
            });
        });
    };

    // === 变体管理 ===
    function loadVariants() {
        apiGet('theme_custom/variants', { theme: state.themeId }).done(function(res) {
            if (res.code !== 0) return;
            const variants = res.data || [];
            const $select = $('#variantSelect');
            const $list = $('#variantList');
            $select.empty();
            $list.empty();

            if (variants.length > 1) {
                $('#variantSection').show();
            }

            variants.forEach(function(v) {
                const isActive = v.is_active === 1;
                $select.append('<option value="' + v.variant_name + '"' + (isActive ? ' selected' : '') + '>' + v.variant_name + '</option>');
                $list.append(
                    '<div class="variant-item' + (isActive ? ' active' : '') + '" data-variant="' + v.variant_name + '">' +
                    '<span>' + v.variant_name + '</span>' +
                    (isActive ? '<span class="badge bg-primary">激活</span>' : '<button class="btn btn-link btn-sm p-0 text-muted" onclick="activateVariant(\'' + v.variant_name + '\')">激活</button>') +
                    '</div>'
                );
            });
        });
    }

    window.activateVariant = function(variant) {
        apiPost('theme_custom/activate', {
            theme: state.themeId,
            variant: variant,
        }).done(function(res) {
            if (res.success) {
                state.currentVariant = variant;
                loadDefaults();
                showToast('变体已激活', 'success');
            }
        });
    };

    // === Logo上传 ===
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
                loadDefaults();
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
                // 无定制数据，直接导出
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

    // === Toast提示 ===
    function showToast(msg, type) {
        type = type || 'info';
        const iconMap = { success: 'check-circle-fill', danger: 'exclamation-triangle-fill', info: 'info-circle-fill' };
        const icon = iconMap[type] || iconMap.info;

        const $toast = $('<div class="position-fixed top-0 end-0 p-3" style="z-index:9999;">' +
            '<div class="toast show align-items-center text-bg-' + type + ' border-0" role="alert">' +
            '<div class="d-flex"><div class="toast-body"><i class="bi bi-' + icon + ' me-2"></i>' + msg + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>');

        $('body').append($toast);
        setTimeout(function() { $toast.remove(); }, 3000);
    }

})();
