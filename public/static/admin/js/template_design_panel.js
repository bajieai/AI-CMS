/**
 * V2.9.24 I-1~I-5: 前台模板设计面板交互（增强版）
 * 新增：区块管理Tab、多页面设计切换、AI配色超时、自定义配色保存、区块内容编辑
 */
(function($) {
    'use strict';

    window.TemplateDesignPanel = {
        currentColors: {},
        currentLayout: {},
        themeSlug: window.THEME_SLUG || '',
        currentPageType: 'index',
        isOpen: false,
        aiTimeout: null,

        init: function() {
            if (!this.themeSlug) return;
            this.bindEvents();
            this.loadPresets();
            this.loadLayoutPresets();
            this.loadCustomColors();
            this.loadSections();
        },

        bindEvents: function() {
            var self = this;
            $(document).on('click', '#templateDesignBtn', function() { self.togglePanel(); });
            $(document).on('click', '#closeDesignPanel', function() { self.closePanel(); });
            $(document).on('click', '.color-preset-item', function() {
                var colors = $(this).data('colors');
                if (colors) {
                    if (typeof colors === 'string') colors = JSON.parse(colors);
                    self.applyColors(colors);
                    $('.color-preset-item').removeClass('active');
                    $(this).addClass('active');
                }
            });
            $(document).on('click', '#aiColorBtn', function() {
                var desc = $('#aiColorInput').val().trim();
                if (desc) self.generateAIColor(desc);
            });
            $(document).on('click', '#saveCustomColorBtn', function() { self.saveCustomColor(); });
            $(document).on('click', '.btn-delete-custom-color', function(e) {
                e.stopPropagation();
                self.deleteCustomColor($(this).data('id'));
            });
            $(document).on('click', '.layout-preset-item', function() {
                var layout = $(this).data('layout');
                if (layout) {
                    if (typeof layout === 'string') layout = JSON.parse(layout);
                    self.applyLayout(layout);
                    $('.layout-preset-item').removeClass('active');
                    $(this).addClass('active');
                }
            });
            $(document).on('click', '.preview-options button', function() {
                self.previewMode($(this).data('width'));
                $(this).addClass('active').siblings().removeClass('active');
            });
            $(document).on('click', '#saveDesignConfig', function() { self.saveConfig(); });
            $(document).on('click', '.page-type-tab', function() {
                self.currentPageType = $(this).data('page-type');
                $('.page-type-tab').removeClass('active');
                $(this).addClass('active');
                self.loadSections();
            });
            $(document).on('click', '.section-visibility-toggle', function() {
                var $item = $(this).closest('.section-item');
                var visible = $item.data('visible') === 1 ? 0 : 1;
                $item.data('visible', visible);
                $(this).find('i').toggleClass('bi-eye bi-eye-slash');
                $item.toggleClass('section-hidden', !visible);
            });
            $(document).on('click', '.section-edit-btn', function() {
                self.openSectionEditor($(this).closest('.section-item').data('id'));
            });
            $(document).on('click', '#saveSectionOrder', function() { self.saveSectionOrder(); });
        },

        togglePanel: function() {
            $('#templateDesignPanel').toggleClass('d-none');
            this.isOpen = !$('#templateDesignPanel').hasClass('d-none');
        },
        closePanel: function() { $('#templateDesignPanel').addClass('d-none'); this.isOpen = false; },

        loadPresets: function() {
            var self = this;
            $.get('/api/template/presetColors', function(res) {
                if (res.code === 0 && res.data) {
                    var html = '';
                    res.data.forEach(function(preset) {
                        var colors = preset.colors;
                        if (typeof colors === 'string') colors = JSON.parse(colors);
                        html += self.renderColorPreset(preset.name, colors);
                    });
                    $('#colorPresets').html(html);
                }
            });
        },

        loadCustomColors: function() {
            var self = this;
            $.get('/api/template/customColors', function(res) {
                if (res.code === 0 && res.data && res.data.length > 0) {
                    var html = '<div class="small text-muted mt-3 mb-1">我的配色</div>';
                    res.data.forEach(function(preset) {
                        var colors = preset.colors;
                        if (typeof colors === 'string') colors = JSON.parse(colors);
                        html += '<div class="color-preset-item position-relative" data-colors=\'' + JSON.stringify(colors) + '\'>';
                        html += self.renderSwatches(colors);
                        html += '<div class="small text-muted">' + preset.name + '</div>';
                        html += '<button class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 btn-delete-custom-color" data-id="' + preset.id + '"><i class="bi bi-trash"></i></button>';
                        html += '</div>';
                    });
                    $('#customColors').html(html);
                }
            });
        },

        renderColorPreset: function(name, colors) {
            return '<div class="color-preset-item" data-colors=\'' + JSON.stringify(colors) + '\'>' + this.renderSwatches(colors) + '<div class="small text-muted">' + name + '</div></div>';
        },
        renderSwatches: function(colors) {
            var html = '<div class="color-swatches d-flex gap-1 mb-1">';
            ['primary','secondary','accent'].forEach(function(key) {
                html += '<span style="background:' + (colors[key] || '#ccc') + ';width:20px;height:20px;border-radius:4px;"></span>';
            });
            return html + '</div>';
        },

        loadLayoutPresets: function() {
            $.get('/api/template/layoutPresets', function(res) {
                if (res.code === 0 && res.data) {
                    var html = '';
                    res.data.forEach(function(preset) {
                        html += '<div class="layout-preset-item" data-layout=\'' + JSON.stringify(preset.vars) + '\'><div class="fw-bold small">' + preset.name + '</div><div class="text-muted small">' + preset.description + '</div></div>';
                    });
                    $('#layoutPresets').html(html);
                }
            });
        },

        applyColors: function(colors) {
            var root = document.documentElement;
            var mapping = {'primary':'--primary','secondary':'--secondary','bg':'--bg','text':'--text','heading':'--font-heading','link':'--primary','accent':'--accent'};
            for (var key in mapping) { if (colors[key]) root.style.setProperty(mapping[key], colors[key]); }
            this.currentColors = colors;
        },
        applyLayout: function(layout) {
            var root = document.documentElement;
            for (var key in layout) { root.style.setProperty(key, layout[key]); }
            this.currentLayout = layout;
        },

        // I-4: AI生成配色（30秒超时 + 降级）
        generateAIColor: function(description) {
            var self = this;
            var $btn = $('#aiColorBtn'), $result = $('#aiColorResult');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 生成中...');
            $result.html('<span class="text-muted"><i class="bi bi-clock"></i> AI正在生成（最多30秒）...</span>');

            clearTimeout(this.aiTimeout);
            this.aiTimeout = setTimeout(function() {
                $btn.prop('disabled', false).html('生成');
                $result.html('<div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> AI响应超时，已推荐配色</div>');
                $.get('/api/template/recommendColors', function(res) {
                    if (res.code === 0 && res.data && res.data.length > 0) {
                        var colors = res.data[0].colors;
                        if (typeof colors === 'string') colors = JSON.parse(colors);
                        self.applyColors(colors);
                        $('#saveCustomColorBtn').show();
                    }
                });
            }, 30000);

            $.ajax({
                url: '/api/template/aiGenerateColor', method: 'POST',
                data: { description: description }, timeout: 30000,
                success: function(res) {
                    clearTimeout(self.aiTimeout);
                    $btn.prop('disabled', false).html('生成');
                    if (res.code === 0 && res.data) {
                        self.applyColors(res.data);
                        var msg = res.msg ? '<span class="text-warning"><i class="bi bi-info-circle"></i> ' + res.msg + '</span>' : '<span class="text-success"><i class="bi bi-check-circle"></i> AI配色已应用</span>';
                        $result.html(msg);
                        $('#saveCustomColorBtn').show();
                    } else {
                        $result.html('<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' + (res.msg || '生成失败') + '</span>');
                    }
                },
                error: function() {
                    clearTimeout(self.aiTimeout);
                    $btn.prop('disabled', false).html('生成');
                    $result.html('<span class="text-danger"><i class="bi bi-x-circle"></i> 网络错误，请重试</span>');
                }
            });
        },

        saveCustomColor: function() {
            var self = this;
            if (Object.keys(this.currentColors).length === 0) { this.showToast('请先选择或生成配色', 'warning'); return; }
            var name = prompt('请输入配色方案名称：', '我的配色');
            if (!name) return;
            $.post('/api/template/saveCustomColor', { name: name, colors: this.currentColors }, function(res) {
                if (res.code === 0) { self.showToast('配色方案已保存', 'success'); self.loadCustomColors(); }
                else { self.showToast(res.msg || '保存失败', 'error'); }
            });
        },
        deleteCustomColor: function(id) {
            var self = this;
            if (!confirm('确定删除该配色方案？')) return;
            $.post('/api/template/deleteCustomColor', { id: id }, function(res) {
                if (res.code === 0) { self.showToast('已删除', 'success'); self.loadCustomColors(); }
                else { self.showToast(res.msg || '删除失败', 'error'); }
            });
        },

        // I-5: 区块管理
        loadSections: function() {
            var self = this;
            $.get('/api/template/getSectionOrder', { theme_slug: this.themeSlug, page_type: this.currentPageType }, function(res) {
                if (res.code === 0 && res.data) { self.renderSections(res.data); }
                else { $('#sectionList').html('<div class="text-muted small">暂无区块配置</div>'); }
            });
        },
        renderSections: function(sections) {
            if (!sections || sections.length === 0) { $('#sectionList').html('<div class="text-muted small">暂无区块配置</div>'); return; }
            var html = '';
            sections.forEach(function(section, index) {
                var visible = section.visible !== false ? 1 : 0;
                html += '<div class="section-item' + (visible ? '' : ' section-hidden') + '" data-id="' + section.id + '" data-visible="' + visible + '">';
                html += '<div class="d-flex align-items-center justify-content-between">';
                html += '<div class="d-flex align-items-center gap-2"><i class="bi bi-grip-vertical text-muted section-drag-handle"></i><span class="small">' + (section.name || section.id) + '</span></div>';
                html += '<div class="d-flex gap-1">';
                html += '<button class="btn btn-sm btn-outline-secondary section-visibility-toggle" title="显示/隐藏"><i class="bi ' + (visible ? 'bi-eye' : 'bi-eye-slash') + '"></i></button>';
                html += '<button class="btn btn-sm btn-outline-primary section-edit-btn" title="编辑内容"><i class="bi bi-pencil"></i></button>';
                html += '</div></div></div>';
            });
            $('#sectionList').html(html);
            this.initSectionSortable();
        },
        initSectionSortable: function() {
            var self = this;
            var el = document.getElementById('sectionList');
            if (!el || typeof Sortable === 'undefined') return;
            if (el._sortable) el._sortable.destroy();
            el._sortable = Sortable.create(el, { handle: '.section-drag-handle', animation: 150, onEnd: function() { self.saveSectionOrder(); } });
        },
        saveSectionOrder: function() {
            var sections = [];
            $('#sectionList .section-item').each(function(index) {
                sections.push({ id: $(this).data('id'), name: $(this).find('.small').text(), visible: $(this).data('visible') === 1, sort: index });
            });
            $.post('/api/template/saveSectionOrder', { theme_slug: this.themeSlug, page_type: this.currentPageType, sections: sections }, function(res) {
                if (res.code === 0) window.TemplateDesignPanel.showToast('区块排序已保存', 'success');
            });
        },
        openSectionEditor: function(sectionId) {
            var self = this;
            $.get('/api/template/getSectionContent', { theme_slug: this.themeSlug, page_type: this.currentPageType, section_id: sectionId }, function(res) {
                self.showSectionEditorModal(sectionId, res.data || {});
            });
        },
        showSectionEditorModal: function(sectionId, content) {
            var self = this;
            var html = '<div class="modal fade" id="sectionEditorModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">' +
                '<div class="modal-header"><h6 class="modal-title">编辑区块：' + sectionId + '</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                '<div class="modal-body">' +
                '<div class="mb-3"><label class="form-label small">标题</label><input type="text" class="form-control" id="sectionTitle" value="' + (content.title || '').replace(/"/g, '&quot;') + '"></div>' +
                '<div class="mb-3"><label class="form-label small">描述</label><textarea class="form-control" id="sectionDescription" rows="3">' + (content.description || '') + '</textarea></div>' +
                '</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button><button type="button" class="btn btn-primary" id="saveSectionContentBtn">保存</button></div>' +
                '</div></div></div>';
            $('#sectionEditorModal').remove();
            $('body').append(html);
            var modal = new bootstrap.Modal(document.getElementById('sectionEditorModal'));
            modal.show();
            $('#saveSectionContentBtn').click(function() {
                $.post('/api/template/saveSectionContent', {
                    theme_slug: self.themeSlug, page_type: self.currentPageType, section_id: sectionId,
                    content: { title: $('#sectionTitle').val(), description: $('#sectionDescription').val() }
                }, function(res) {
                    if (res.code === 0) { self.showToast('区块内容已保存', 'success'); modal.hide(); }
                    else { self.showToast(res.msg || '保存失败', 'error'); }
                });
            });
        },

        previewMode: function(width) {
            if (width === '100%') {
                $('body').css({'max-width':'','margin':'','border':'none','border-radius':'0','box-shadow':'none'});
            } else {
                $('body').css({'max-width':width,'margin':'0 auto','border':'2px solid #dee2e6','border-radius':'8px','box-shadow':'0 4px 20px rgba(0,0,0,0.1)'});
            }
        },

        saveConfig: function() {
            var self = this;
            var $btn = $('#saveDesignConfig');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>保存中...');
            $.post('/api/template/saveDesignConfig', { theme_slug: this.themeSlug, colors: this.currentColors, layout: this.currentLayout }, function(res) {
                $btn.prop('disabled', false).html('保存设计');
                if (res.code === 0) { self.showToast('设计已保存', 'success'); }
                else { self.showToast(res.msg || '保存失败', 'error'); }
            }).fail(function() {
                $btn.prop('disabled', false).html('保存设计');
                self.showToast('网络错误', 'error');
            });
        },

        showToast: function(msg, type) {
            type = type || 'info';
            var bgClass = {'success':'bg-success','error':'bg-danger','warning':'bg-warning text-dark','info':'bg-info text-dark'}[type] || 'bg-info';
            var $toast = $('<div class="toast align-items-center ' + bgClass + ' text-white border-0 position-fixed" style="top:20px;right:20px;z-index:9999;min-width:200px;" role="alert"><div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>');
            $('body').append($toast);
            $toast.toast('show');
            setTimeout(function() { $toast.remove(); }, 3000);
        }
    };

    $(document).ready(function() { TemplateDesignPanel.init(); });
})(jQuery);
