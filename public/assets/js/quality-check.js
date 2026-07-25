/**
 * V2.9.4 内容质量检测前端组件
 */
(function() {
    var QualityChecker = {
        debounceTimer: null,
        panelVisible: false,

        init: function() {
            this.createPanel();
            this.bindEvents();
        },

        createPanel: function() {
            if ($('#quality-panel').length) return;

            var panel = $(
                '<div id="quality-panel" class="card position-fixed" style="bottom:20px;right:20px;width:360px;z-index:1050;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);">' +
                '  <div class="card-header d-flex justify-content-between align-items-center py-2" style="cursor:move;">' +
                '    <span class="fw-bold"><i class="bi bi-clipboard2-check me-1"></i>AI质量检测</span>' +
                '    <div>' +
                '      <button class="btn btn-sm btn-link p-0 me-1" id="btn-check-now" title="立即检测"><i class="bi bi-arrow-repeat"></i></button>' +
                '      <button class="btn btn-sm btn-link p-0" id="btn-close-panel" title="关闭"><i class="bi bi-x-lg"></i></button>' +
                '    </div>' +
                '  </div>' +
                '  <div class="card-body p-3" style="max-height:500px;overflow-y:auto;" id="quality-content">' +
                '    <div class="text-center text-muted py-4">点击右上角按钮或编辑内容后自动检测</div>' +
                '  </div>' +
                '</div>'
            );
            $('body').append(panel);

            // 浮动按钮
            var fab = $('<button class="btn btn-primary rounded-circle position-fixed" id="quality-fab" style="bottom:20px;right:20px;width:50px;height:50px;z-index:1049;box-shadow:0 2px 8px rgba(0,0,0,0.2);" title="AI质量检测"><i class="bi bi-clipboard2-check"></i></button>');
            $('body').append(fab);
        },

        bindEvents: function() {
            var self = this;

            // 浮动按钮切换面板
            $(document).on('click', '#quality-fab', function() {
                self.panelVisible = !self.panelVisible;
                if (self.panelVisible) {
                    $('#quality-panel').show();
                    $('#quality-fab').hide();
                    self.runCheck();
                }
            });

            // 关闭面板
            $(document).on('click', '#btn-close-panel', function() {
                $('#quality-panel').hide();
                $('#quality-fab').show();
                self.panelVisible = false;
            });

            // 立即检测
            $(document).on('click', '#btn-check-now', function() {
                self.runCheck();
            });

            // 编辑器内容变化时防抖检测
            if (typeof tinymce !== 'undefined') {
                tinymce.on('addeditor', function(e) {
                    e.editor.on('change keyup', function() {
                        self.debounceCheck();
                    });
                });
            }

            // 标题变化
            $(document).on('input', 'input[name="title"]', function() {
                self.debounceCheck();
            });
        },

        debounceCheck: function() {
            var self = this;
            clearTimeout(self.debounceTimer);
            self.debounceTimer = setTimeout(function() {
                if (self.panelVisible) {
                    self.runCheck();
                }
            }, 1500);
        },

        runCheck: function() {
            var title = $('input[name="title"]').val() || '';
            var content = '';
            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                content = tinymce.activeEditor.getContent();
            } else {
                content = $('textarea[name="content"]').val() || '';
            }
            var keywords = $('input[name="seo_keywords"]').val() || '';

            if (!content || content.length < 20) {
                $('#quality-content').html('<div class="text-center text-muted py-3">内容过少，无法检测</div>');
                return;
            }

            $('#quality-content').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 small text-muted">检测中...</div></div>');

            $.ajax({
                url: '/admin/quality_check/check',
                method: 'POST',
                data: { title: title, content: content, keywords: keywords },
                dataType: 'json',
                success: function(res) {
                    if (res.code === 0) {
                        QualityChecker.renderResults(res.data);
                    } else {
                        $('#quality-content').html('<div class="text-danger py-3">' + (res.msg || '检测失败') + '</div>');
                    }
                },
                error: function() {
                    $('#quality-content').html('<div class="text-danger py-3">检测请求失败</div>');
                }
            });
        },

        renderResults: function(data) {
            var gradeColors = { green: 'success', yellow: 'warning', red: 'danger' };
            var gradeLabels = { green: '良好', yellow: '待优化', red: '需改进' };
            var grade = data.grade || 'yellow';

            var html = '';
            // 总评分
            html += '<div class="text-center mb-3">';
            html += '  <div class="display-4 fw-bold text-' + gradeColors[grade] + '">' + data.total_score + '</div>';
            html += '  <span class="badge bg-' + gradeColors[grade] + '">' + gradeLabels[grade] + '</span>';
            html += '</div>';

            // 三维度评分
            html += '<div class="row g-2 mb-3">';
            html += '  <div class="col-4 text-center"><div class="fw-bold">' + (data.readability.score || 0) + '</div><small class="text-muted">可读性</small></div>';
            html += '  <div class="col-4 text-center"><div class="fw-bold">' + (data.seo.score || 0) + '</div><small class="text-muted">SEO</small></div>';
            html += '  <div class="col-4 text-center"><div class="fw-bold">' + (data.sensitive.score || 0) + '</div><small class="text-muted">敏感词</small></div>';
            html += '</div>';

            // 可读性详情
            if (data.readability) {
                var r = data.readability;
                html += '<div class="border-top pt-2 mt-2">';
                html += '  <div class="fw-bold small mb-1"><i class="bi bi-book me-1"></i>可读性</div>';
                html += '  <div class="small text-muted">难度: ' + (r.level || '-') + ' | 阅读时长: ' + (r.min_read || 0) + '分钟 | 平均句长: ' + (r.avg_sentence_len || 0) + '字</div>';
                html += '</div>';
            }

            // SEO详情
            if (data.seo && data.seo.dimensions) {
                html += '<div class="border-top pt-2 mt-2">';
                html += '  <div class="fw-bold small mb-1"><i class="bi bi-search me-1"></i>SEO检测</div>';
                $.each(data.seo.dimensions, function(key, dim) {
                    var color = dim.score >= 80 ? 'success' : (dim.score >= 50 ? 'warning' : 'danger');
                    html += '<div class="d-flex justify-content-between small">';
                    html += '  <span>' + dim.label + '</span>';
                    html += '  <span class="text-' + color + '">' + dim.value + ' <small>(' + dim.score + '分)</small></span>';
                    html += '</div>';
                });
                html += '</div>';
            }

            // 敏感词详情
            if (data.sensitive && data.sensitive.count > 0) {
                html += '<div class="border-top pt-2 mt-2">';
                html += '  <div class="fw-bold small mb-1 text-danger"><i class="bi bi-shield-exclamation me-1"></i>敏感词</div>';
                $.each(data.sensitive.matched, function(i, word) {
                    html += '<span class="badge bg-danger me-1 mb-1">' + word + '</span>';
                });
                html += '</div>';
            }

            // 建议列表
            if (data.suggestions && data.suggestions.length > 0) {
                html += '<div class="border-top pt-2 mt-2">';
                html += '  <div class="fw-bold small mb-1"><i class="bi bi-lightbulb me-1"></i>优化建议</div>';
                $.each(data.suggestions, function(i, s) {
                    var icon = s.type === 'success' ? 'check-circle text-success' : (s.type === 'warning' ? 'exclamation-triangle text-warning' : (s.type === 'danger' ? 'x-circle text-danger' : 'info-circle text-info'));
                    html += '<div class="small mb-1"><i class="bi bi-' + icon + ' me-1"></i>' + s.msg + '</div>';
                });
                html += '</div>';
            }

            $('#quality-content').html(html);
        }
    };

    $(function() {
        // 仅在内容编辑页初始化
        if ($('input[name="title"]').length || $('textarea[name="content"]').length) {
            QualityChecker.init();
        }
    });
})();
