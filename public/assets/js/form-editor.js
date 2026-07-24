/**
 * 表单可视化编辑器核心 - V2.7 Sprint3
 * 三栏布局: 左侧字段工具箱 / 中间画布 / 右侧属性面板
 */
(function() {
    'use strict';

    var FIELD_TYPES = [
        { type: 'text',     label: '单行文本', icon: 'bi-input-cursor-text', default: { label: '姓名', placeholder: '', required: false } },
        { type: 'textarea', label: '多行文本', icon: 'bi-textarea-resize',   default: { label: '留言', placeholder: '', rows: 4, required: false } },
        { type: 'email',    label: '邮箱',     icon: 'bi-envelope',          default: { label: '电子邮箱', placeholder: 'example@mail.com', required: true } },
        { type: 'tel',      label: '手机',     icon: 'bi-phone',             default: { label: '手机号码', placeholder: '', required: true } },
        { type: 'number',   label: '数字',     icon: 'bi-123',               default: { label: '数量', placeholder: '', min: 0, max: 999, required: false } },
        { type: 'select',   label: '下拉选择', icon: 'bi-menu-down',         default: { label: '请选择', options: '选项1\n选项2\n选项3', required: false } },
        { type: 'radio',    label: '单选框',   icon: 'bi-ui-radios',         default: { label: '性别', options: '男\n女', required: false } },
        { type: 'checkbox', label: '复选框',   icon: 'bi-ui-checks',         default: { label: '兴趣', options: '阅读\n运动\n音乐', required: false } },
        { type: 'date',     label: '日期',     icon: 'bi-calendar',          default: { label: '日期', required: false } },
        { type: 'file',     label: '文件上传', icon: 'bi-paperclip',         default: { label: '附件', accept: '*', required: false } },
        { type: 'rating',   label: '评分',     icon: 'bi-star',              default: { label: '满意度', max: 5, required: false } },
        { type: 'divider',  label: '分隔线',   icon: 'bi-hr',                default: { label: '分隔线' } },
    ];

    var PRESETS = [
        { name: 'contact',  label: '联系我们',   fields: [
            { type: 'text', label: '您的姓名', required: true },
            { type: 'tel', label: '联系电话', required: true },
            { type: 'email', label: '电子邮箱', required: false },
            { type: 'textarea', label: '留言内容', required: true }
        ]},
        { name: 'register', label: '在线报名',   fields: [
            { type: 'text', label: '姓名', required: true },
            { type: 'select', label: '性别', options: '男\n女', required: false },
            { type: 'tel', label: '手机', required: true },
            { type: 'select', label: '报名项目', options: '初级班\n中级班\n高级班', required: true }
        ]},
        { name: 'survey',   label: '问卷调查',   fields: [
            { type: 'radio', label: '您从何处了解到我们', options: '搜索引擎\n朋友推荐\n社交媒体\n其他', required: true },
            { type: 'rating', label: '整体满意度', max: 5, required: true },
            { type: 'checkbox', label: '您感兴趣的服务', options: '产品咨询\n技术支持\n售后服务\n合作洽谈', required: false },
            { type: 'textarea', label: '其他建议', required: false }
        ]},
        { name: 'feedback', label: '意见反馈',   fields: [
            { type: 'select', label: '反馈类型', options: '功能建议\nBug报告\n用户体验\n其他', required: true },
            { type: 'textarea', label: '详细描述', required: true },
            { type: 'file', label: '截图附件', required: false }
        ]},
    ];

    window.FormEditor = {
        fields: [],
        selectedIdx: -1,

        init: function() {
            this.renderToolbox();
            this.bindEvents();
            this.loadPreset('contact');
        },

        renderToolbox: function() {
            var html = '';
            FIELD_TYPES.forEach(function(f) {
                html += '<div class="fe-tool-item" data-type="' + f.type + '" draggable="true">' +
                    '<i class="bi ' + f.icon + '"></i><span>' + f.label + '</span></div>';
            });
            $('#feToolbox').html(html);
        },

        bindEvents: function() {
            var self = this;
            // 工具箱拖拽
            $('#feToolbox').on('dragstart', '.fe-tool-item', function(e) {
                e.originalEvent.dataTransfer.setData('type', $(this).data('type'));
            });
            // 画布拖放
            $('#feCanvas').on('dragover', function(e) { e.preventDefault(); })
                .on('drop', function(e) {
                    e.preventDefault();
                    var type = e.originalEvent.dataTransfer.getData('type');
                    if (type) self.addField(type);
                });
            // 属性面板变更实时同步
            $('#feProps').on('input change', '[data-prop]', function() {
                self.updateFieldProp($(this).data('prop'), $(this).val());
            });
            // 预设加载
            $('#fePresetSelect').on('change', function() {
                var name = $(this).val();
                if (name && confirm('加载预设将覆盖当前画布，确定继续？')) self.loadPreset(name);
            });
        },

        addField: function(type, cfg) {
            var def = FIELD_TYPES.find(function(f) { return f.type === type; });
            if (!def) return;
            var field = $.extend({}, def.default, { type: type, key: 'f' + Date.now() + '_' + Math.floor(Math.random()*1000) }, cfg || {});
            this.fields.push(field);
            this.renderCanvas();
            this.selectField(this.fields.length - 1);
        },

        removeField: function(idx) {
            this.fields.splice(idx, 1);
            this.selectedIdx = -1;
            this.renderCanvas();
            this.renderProps();
        },

        moveField: function(from, to) {
            var item = this.fields.splice(from, 1)[0];
            this.fields.splice(to, 0, item);
            this.renderCanvas();
        },

        selectField: function(idx) {
            this.selectedIdx = idx;
            this.renderCanvas();
            this.renderProps();
        },

        updateFieldProp: function(prop, value) {
            if (this.selectedIdx < 0) return;
            if (prop === 'required') value = $('#prop_required').is(':checked') ? true : false;
            this.fields[this.selectedIdx][prop] = value;
            this.renderCanvas();
        },

        loadPreset: function(name) {
            var preset = PRESETS.find(function(p) { return p.name === name; });
            if (!preset) return;
            this.fields = preset.fields.map(function(f, i) {
                var def = FIELD_TYPES.find(function(t) { return t.type === f.type; });
                return $.extend({}, def ? def.default : {}, f, { key: 'f' + Date.now() + '_' + i });
            });
            this.selectedIdx = -1;
            this.renderCanvas();
            this.renderProps();
        },

        renderCanvas: function() {
            var self = this;
            var html = '';
            if (this.fields.length === 0) {
                html = '<div class="fe-empty">从左侧拖拽字段到此处</div>';
            } else {
                this.fields.forEach(function(field, i) {
                    var isActive = i === self.selectedIdx;
                    html += '<div class="fe-field-item ' + (isActive ? 'active' : '') + '" data-idx="' + i + '">' +
                        '<div class="fe-field-label">' + escapeHtml(field.label) + (field.required ? ' <span class="text-danger">*</span>' : '') + '</div>' +
                        '<div class="fe-field-preview">' + self.buildPreview(field) + '</div>' +
                        '<div class="fe-field-actions">' +
                        '<button type="button" class="btn btn-xs btn-light" onclick="FormEditor.moveField(' + i + ',' + (i-1) + ')" ' + (i===0?'disabled':'') + '><i class="bi bi-arrow-up"></i></button>' +
                        '<button type="button" class="btn btn-xs btn-light" onclick="FormEditor.moveField(' + i + ',' + (i+1) + ')" ' + (i===self.fields.length-1?'disabled':'') + '><i class="bi bi-arrow-down"></i></button>' +
                        '<button type="button" class="btn btn-xs btn-outline-danger" onclick="FormEditor.removeField(' + i + ')"><i class="bi bi-trash"></i></button>' +
                        '</div></div>';
                });
            }
            $('#feCanvas').html(html);
            $('#feCanvas .fe-field-item').on('click', function() {
                self.selectField($(this).data('idx'));
            });
        },

        buildPreview: function(field) {
            switch(field.type) {
                case 'text':     return '<input type="text" class="form-control form-control-sm" placeholder="' + escapeHtml(field.placeholder||'') + '" disabled>';
                case 'email':    return '<input type="email" class="form-control form-control-sm" placeholder="' + escapeHtml(field.placeholder||'') + '" disabled>';
                case 'tel':      return '<input type="tel" class="form-control form-control-sm" placeholder="' + escapeHtml(field.placeholder||'') + '" disabled>';
                case 'number':   return '<input type="number" class="form-control form-control-sm" placeholder="' + escapeHtml(field.placeholder||'') + '" disabled>';
                case 'textarea': return '<textarea class="form-control form-control-sm" rows="' + (field.rows||3) + '" disabled></textarea>';
                case 'select':   return '<select class="form-select form-select-sm" disabled><option>' + escapeHtml(field.label) + '</option></select>';
                case 'radio':    return '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" disabled><label class="form-check-label">选项</label></div>';
                case 'checkbox': return '<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">选项</label></div>';
                case 'date':     return '<input type="date" class="form-control form-control-sm" disabled>';
                case 'file':     return '<input type="file" class="form-control form-control-sm" disabled>';
                case 'rating':   return '<div class="text-warning"><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i></div>';
                case 'divider':  return '<hr class="my-2">';
                default:         return '<input type="text" class="form-control form-control-sm" disabled>';
            }
        },

        renderProps: function() {
            if (this.selectedIdx < 0) {
                $('#feProps').html('<div class="text-muted small text-center py-4">点击画布中的字段编辑属性</div>');
                return;
            }
            var field = this.fields[this.selectedIdx];
            var html = '<div class="mb-3"><label class="form-label small">字段类型</label><input class="form-control form-control-sm" value="' + field.type + '" disabled></div>';
            html += '<div class="mb-3"><label class="form-label small">标签文字</label><input class="form-control form-control-sm" data-prop="label" value="' + escapeHtml(field.label||'') + '"></div>';
            if (field.type !== 'divider' && field.type !== 'rating' && field.type !== 'date' && field.type !== 'file') {
                html += '<div class="mb-3"><label class="form-label small">占位提示</label><input class="form-control form-control-sm" data-prop="placeholder" value="' + escapeHtml(field.placeholder||'') + '"></div>';
            }
            if (field.type === 'number') {
                html += '<div class="row"><div class="col-6 mb-3"><label class="form-label small">最小值</label><input type="number" class="form-control form-control-sm" data-prop="min" value="' + (field.min||0) + '"></div><div class="col-6 mb-3"><label class="form-label small">最大值</label><input type="number" class="form-control form-control-sm" data-prop="max" value="' + (field.max||999) + '"></div></div>';
            }
            if (field.type === 'select' || field.type === 'radio' || field.type === 'checkbox') {
                html += '<div class="mb-3"><label class="form-label small">选项（每行一个）</label><textarea class="form-control form-control-sm" rows="4" data-prop="options">' + escapeHtml(field.options||'') + '</textarea></div>';
            }
            if (field.type === 'textarea') {
                html += '<div class="mb-3"><label class="form-label small">行数</label><input type="number" class="form-control form-control-sm" data-prop="rows" value="' + (field.rows||4) + '"></div>';
            }
            if (field.type === 'rating') {
                html += '<div class="mb-3"><label class="form-label small">最大星级</label><input type="number" class="form-control form-control-sm" data-prop="max" value="' + (field.max||5) + '"></div>';
            }
            if (field.type !== 'divider') {
                html += '<div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="prop_required" data-prop="required" ' + (field.required?'checked':'') + '><label class="form-check-label small" for="prop_required">必填</label></div>';
            }
            $('#feProps').html(html);
        },

        getJson: function() {
            return JSON.stringify(this.fields);
        },

        loadJson: function(json) {
            try {
                var arr = JSON.parse(json);
                if (Array.isArray(arr)) {
                    this.fields = arr;
                    this.renderCanvas();
                }
            } catch(e) {
                alert('JSON格式错误');
            }
        }
    };

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
