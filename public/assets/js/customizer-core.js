/**
 * V2.9.9 主题定制器 - 核心逻辑层 (customizer-core.js)
 * 职责：状态管理、撤销栈、API封装、数据加载、保存/重置、预览通信
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

        clone(obj) {
            if (typeof window.structuredClone === 'function') {
                try { return window.structuredClone(obj); } catch(e) {}
            }
            return JSON.parse(JSON.stringify(obj));
        },

        snapshot() {
            return {
                customization: this.clone(state.customization),
                currentVariant: state.currentVariant,
            };
        },

        push() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => { this._doPush(); }, 150);
        },

        _doPush() {
            const snap = this.snapshot();
            if (this.pointer >= 0) {
                const last = this.stack[this.pointer];
                if (JSON.stringify(last.customization) === JSON.stringify(snap.customization)) {
                    return;
                }
            }
            if (this.pointer < this.stack.length - 1) {
                this.stack = this.stack.slice(0, this.pointer + 1);
            }
            this.stack.push(snap);
            if (this.stack.length > this.maxDepth) {
                this.stack.shift();
                if (this.savePointIndex >= 0) this.savePointIndex--;
            } else {
                this.pointer++;
            }
            this.redoStack = [];
            this.updateUI();
        },

        undo() {
            if (!this.canUndo()) return;
            this.pointer--;
            this._restore(this.stack[this.pointer]);
            this.updateUI();
        },

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

        hasUnsavedChanges() { return this.pointer !== this.savePointIndex; },

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
            this.stack.push(this.snapshot());
            this.pointer = 0;
            this.savePointIndex = 0;
            this.updateUI();
        }
    };

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
        const dfd = $.Deferred();
        apiGet('theme_custom/defaults', { theme: state.themeId }).done(function(res) {
            if (res.code !== 0) {
                showToast(res.msg || '加载默认参数失败', 'danger');
                dfd.reject();
                return;
            }
            state.defaults = res.data.defaults || {};
            state.customization = res.data.customization || {};
            dfd.resolve();
        }).fail(function() {
            showToast('网络请求失败', 'danger');
            dfd.reject();
        });
        return dfd;
    }

    // === 加载预设 ===
    function loadPresets() {
        apiGet('theme_custom/presets').done(function(res) {
            if (res.code === 0) {
                state.presets = res.data || {};
            }
        });
        apiGet('theme_custom/colorPresets', { theme: state.themeId }).done(function(res) {
            if (res.code === 0 && window.ThemeCustomizerUI) {
                window.ThemeCustomizerUI.renderColorPresets(res.data || {});
            }
        });
    }

    // === 工具函数 ===
    function getCurrentValue(varName) {
        if (state.customization && state.customization[varName]) {
            return state.customization[varName];
        }
        const cssVars = state.defaults.css_vars || {};
        return cssVars[varName] ? cssVars[varName].default : '';
    }

    function findFontKey(fontValue, type) {
        const fonts = state.presets.fonts || state.defaults.fonts || {};
        for (const entry of Object.entries(fonts)) {
            if (entry[1][type] === fontValue) return entry[0];
        }
        return null;
    }

    function activateLayoutOpt(containerId, value) {
        $('#' + containerId + ' .layout-opt').removeClass('active');
        $('#' + containerId + ' .layout-opt[data-value="' + value + '"]').addClass('active');
    }

    // === 应用当前定制数据到UI ===
    function applyCustomizationToUI() {
        const custom = state.customization || {};

        Object.entries(state.pickrs).forEach(function(entry) {
            const varName = entry[0];
            const pickr = entry[1];
            if (custom[varName] && !custom[varName].startsWith('var(')) {
                pickr.setColor(custom[varName]);
                const id = 'hex-' + varName.replace(/^--/, '').replace(/-/g, '_');
                $('#' + id).val(custom[varName]);
            }
        });

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

        if (custom['--sidebar-pos']) activateLayoutOpt('sidebarPosOptions', custom['--sidebar-pos']);
        if (custom['--content-width']) activateLayoutOpt('contentWidthOptions', custom['--content-width']);
        if (custom['--header-style']) activateLayoutOpt('headerStyleOptions', custom['--header-style']);

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
                loadDefaults().done(function() {
                    if (window.ThemeCustomizerUI) {
                        window.ThemeCustomizerUI.renderColorPickers();
                        window.ThemeCustomizerUI.renderFontSelectors();
                        window.ThemeCustomizerUI.renderLayoutOptions();
                    }
                    applyCustomizationToUI();
                    loadVariants();
                });
                showToast('变体已激活', 'success');
            }
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

    // V2.9.8 C-2: 撤销栈超时清空
    (function() {
        let panelCloseTime = null;
        const FIFTEEN_MINUTES = 15 * 60 * 1000;

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                panelCloseTime = Date.now();
            } else {
                if (panelCloseTime && (Date.now() - panelCloseTime > FIFTEEN_MINUTES)) {
                    UndoManager.clear();
                    UndoManager.init();
                    panelCloseTime = null;
                }
            }
        });
    })();

    // 暴露到全局命名空间
    window.ThemeCustomizer = {
        state: state,
        UndoManager: UndoManager,
        apiGet: apiGet,
        apiPost: apiPost,
        loadDefaults: loadDefaults,
        loadPresets: loadPresets,
        loadVariants: loadVariants,
        applyCustomizationToUI: applyCustomizationToUI,
        getCurrentValue: getCurrentValue,
        findFontKey: findFontKey,
        activateLayoutOpt: activateLayoutOpt,
        loadPreview: loadPreview,
        debouncedPreview: debouncedPreview,
        sendCustomToPreview: sendCustomToPreview,
        showToast: showToast,
    };
})();
