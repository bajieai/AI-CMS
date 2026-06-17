/**
 * V2.9.23 C-1: 前台模板设计面板交互
 * 实时修改CSS变量 → 即时预览 → 保存持久化
 */
(function($) {
    'use strict';

    window.TemplateDesignPanel = {
        currentColors: {},
        currentLayout: {},
        themeSlug: window.THEME_SLUG || '',
        isOpen: false,

        init: function() {
            if (!this.themeSlug) return;
            this.bindEvents();
            this.loadPresets();
            this.loadLayoutPresets();
        },

        bindEvents: function() {
            var self = this;

            // 浮动按钮点击
            $(document).on('click', '#templateDesignBtn', function() {
                self.togglePanel();
            });

            // 关闭面板
            $(document).on('click', '#closeDesignPanel', function() {
                self.closePanel();
            });

            // 预设配色点击
            $(document).on('click', '.color-preset-item', function() {
                var colors = $(this).data('colors');
                if (colors) {
                    self.applyColors(colors);
                }
            });

            // AI配色生成
            $(document).on('click', '#aiColorBtn', function() {
                var desc = $('#aiColorInput').val().trim();
                if (desc) {
                    self.generateAIColor(desc);
                }
            });

            // 布局方案点击
            $(document).on('click', '.layout-preset-item', function() {
                var layout = $(this).data('layout');
                if (layout) {
                    self.applyLayout(layout);
                }
            });

            // 预览模式切换
            $(document).on('click', '.preview-options button', function() {
                var width = $(this).data('width');
                self.previewMode(width);
                $(this).addClass('active').siblings().removeClass('active');
            });

            // 保存设计
            $(document).on('click', '#saveDesignConfig', function() {
                self.saveConfig();
            });
        },

        togglePanel: function() {
            var $panel = $('#templateDesignPanel');
            if ($panel.hasClass('d-none')) {
                $panel.removeClass('d-none');
                this.isOpen = true;
            } else {
                this.closePanel();
            }
        },

        closePanel: function() {
            $('#templateDesignPanel').addClass('d-none');
            this.isOpen = false;
        },

        // 加载预设配色
        loadPresets: function() {
            var self = this;
            $.get('/api/template/presetColors', function(res) {
                if (res.code === 0 && res.data) {
                    var html = '';
                    res.data.forEach(function(preset) {
                        var colors = preset.colors;
                        if (typeof colors === 'string') {
                            colors = JSON.parse(colors);
                        }
                        html += '<div class="color-preset-item" data-colors=\'' + JSON.stringify(colors) + '\'>';
                        html += '<div class="color-swatches d-flex gap-1 mb-1">';
                        html += '<span style="background:' + (colors.primary || '#ccc') + ';width:20px;height:20px;border-radius:4px;"></span>';
                        html += '<span style="background:' + (colors.secondary || '#ccc') + ';width:20px;height:20px;border-radius:4px;"></span>';
                        html += '<span style="background:' + (colors.accent || '#ccc') + ';width:20px;height:20px;border-radius:4px;"></span>';
                        html += '</div>';
                        html += '<div class="small text-muted">' + preset.name + '</div>';
                        html += '</div>';
                    });
                    $('#colorPresets').html(html);
                }
            });
        },

        // 加载布局方案
        loadLayoutPresets: function() {
            var self = this;
            $.get('/api/template/layoutPresets', function(res) {
                if (res.code === 0 && res.data) {
                    var html = '';
                    res.data.forEach(function(preset) {
                        html += '<div class="layout-preset-item" data-layout=\'' + JSON.stringify(preset.vars) + '\'>';
                        html += '<div class="fw-bold small">' + preset.name + '</div>';
                        html += '<div class="text-muted small">' + preset.description + '</div>';
                        html += '</div>';
                    });
                    $('#layoutPresets').html(html);
                }
            });
        },

        // 应用配色
        applyColors: function(colors) {
            var root = document.documentElement;
            var mapping = {
                'primary': '--primary',
                'secondary': '--secondary',
                'bg': '--bg',
                'text': '--text',
                'heading': '--font-heading',
                'link': '--primary',
                'accent': '--accent',
            };

            for (var key in mapping) {
                if (colors[key]) {
                    root.style.setProperty(mapping[key], colors[key]);
                }
            }

            this.currentColors = colors;

            // 高亮当前选中的预设
            $('.color-preset-item').removeClass('active');
            // 无法精确匹配，暂时不做高亮
        },

        // 应用布局
        applyLayout: function(layout) {
            var root = document.documentElement;
            for (var key in layout) {
                root.style.setProperty(key, layout[key]);
            }
            this.currentLayout = layout;
        },

        // AI生成配色
        generateAIColor: function(description) {
            var self = this;
            var $btn = $('#aiColorBtn');
            var $result = $('#aiColorResult');

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $result.html('');

            $.post('/api/template/aiGenerateColor', {description: description}, function(res) {
                $btn.prop('disabled', false).html('生成');
                if (res.code === 0 && res.data) {
                    self.applyColors(res.data);
                    $result.html('<span class="text-success"><i class="bi bi-check-circle"></i> AI配色已应用</span>');
                } else {
                    $result.html('<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' + (res.msg || '生成失败') + '</span>');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('生成');
                $result.html('<span class="text-danger"><i class="bi bi-x-circle"></i> 网络错误</span>');
            });
        },

        // 预览模式
        previewMode: function(width) {
            $('body').css('max-width', width);
            if (width === '100%') {
                $('body').css('margin', '');
                $('body').css('border', 'none');
                $('body').css('border-radius', '0');
                $('body').css('box-shadow', 'none');
            } else {
                $('body').css('margin', '0 auto');
                $('body').css('border', '2px solid #dee2e6');
                $('body').css('border-radius', '8px');
                $('body').css('box-shadow', '0 4px 20px rgba(0,0,0,0.1)');
            }
        },

        // 保存设计
        saveConfig: function() {
            var self = this;
            var $btn = $('#saveDesignConfig');

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>保存中...');

            var data = {
                theme_slug: this.themeSlug,
                colors: this.currentColors,
                layout: this.currentLayout
            };

            $.post('/api/template/saveDesignConfig', data, function(res) {
                $btn.prop('disabled', false).html('保存设计');
                if (res.code === 0) {
                    self.showToast('设计已保存', 'success');
                } else {
                    self.showToast(res.msg || '保存失败', 'error');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('保存设计');
                self.showToast('网络错误', 'error');
            });
        },

        showToast: function(msg, type) {
            type = type || 'info';
            var bgClass = {
                success: 'bg-success',
                error: 'bg-danger',
                warning: 'bg-warning text-dark',
                info: 'bg-info text-dark'
            }[type] || 'bg-info';

            var $toast = $('<div class="toast align-items-center ' + bgClass + ' text-white border-0 position-fixed" style="top:20px;right:20px;z-index:9999;min-width:200px;" role="alert"><div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>');
            $('body').append($toast);
            $toast.toast('show');
            setTimeout(function() { $toast.remove(); }, 3000);
        }
    };

    $(document).ready(function() {
        TemplateDesignPanel.init();
    });
})(jQuery);
