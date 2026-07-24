/**
 * V2.9.9 主题定制器 - 入口桥接 (theme-customizer.js)
 * 职责：按序加载 core + ui 模块，统一初始化，全局事件绑定
 *
 * 加载顺序：
 *   1. customizer-core.js  → 状态/撤销/API/保存/预览
 *   2. customizer-ui.js    → 渲染/事件/引导/导出
 *   3. theme-customizer.js → 入口初始化
 */
(function() {
    'use strict';

    $(document).ready(function() {
        const core = window.ThemeCustomizer;
        const ui = window.ThemeCustomizerUI;
        if (!core || !ui) {
            console.error('ThemeCustomizer 核心或UI模块未加载');
            return;
        }

        const params = new URLSearchParams(window.location.search);
        core.state.themeId = params.get('theme') || '';

        if (!core.state.themeId) {
            core.showToast('缺少主题参数', 'danger');
            return;
        }

        $('#themeBadge').text(core.state.themeId);

        // 加载默认参数 → 渲染UI → 应用数据 → 加载变体
        core.loadDefaults().done(function() {
            ui.renderColorPickers();
            ui.renderFontSelectors();
            ui.renderLayoutOptions();
            core.applyCustomizationToUI();
            core.loadVariants();
        });

        core.loadPresets();
        ui.setupEventListeners();
        core.loadPreview();

        // V2.9.8 C-2: 导出按钮绑定预览确认
        $('#exportBtn').on('click', function(e) {
            e.preventDefault();
            handleExportWithPreview();
        });

        // V2.9.8 A-2: 初始化撤销栈
        core.UndoManager.init();

        // V2.9.8 A-2: 键盘快捷键 Ctrl+Z / Ctrl+Shift+Z
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                e.preventDefault();
                if (e.shiftKey) {
                    core.UndoManager.redo();
                } else {
                    core.UndoManager.undo();
                }
            }
        });

        // V2.9.8 A-2: 关闭前未保存提示
        window.addEventListener('beforeunload', function(e) {
            if (core.UndoManager.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = '有未保存的定制变更，确定要离开吗？';
            }
        });

        // =====================================================
        // V2.9.8 第二轮增强初始化
        // =====================================================

        // B-1: 加载增强预设+智能推荐
        setTimeout(function() {
            if (ui.loadRecommendedPreset) {
                ui.loadRecommendedPreset();
            }
        }, 500);

        // B-2: 初始化新手引导
        if (ui.OnboardingGuide) {
            ui.OnboardingGuide.init();
        }

        // B-2: 初始化折叠面板
        if (ui.CollapsibleSection) {
            ui.CollapsibleSection.init();
        }
    });
})();
