/**
 * SEO预览面板交互JS — V2.9.30 AI2-2
 * 通过JS动态加载到内容编辑页，避免修改大文件content_edit.html
 */
$(function() {
    // 检测是否在内容编辑页
    var contentId = $('#content-id').val() || window.location.pathname.match(/\/(\d+)$/);
    if (!contentId) return;
    contentId = typeof contentId === 'object' ? contentId[1] : contentId;

    // 创建SEO面板容器
    var seoPanelHtml = '<div id="seo-panel-container" class="mt-3"></div>';
    $('.card-body .form-group, .card-body .mb-3').last().after(seoPanelHtml);

    // 动态加载SEO面板
    $.get('/admin/content/ai_seo_panel/' + contentId, function(html) {
        $('#seo-panel-container').html(html);
        initSeoPanel(contentId);
    }).fail(function() {
        // 如果加载失败，使用内嵌面板
        renderInlineSeoPanel(contentId);
    });

    function renderInlineSeoPanel(contentId) {
        var html = '<div class="card mt-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-search me-1"></i>SEO预览面板</h6></div>';
        html += '<div class="card-body"><div class="seo-preview"><div class="google-result mb-2">';
        html += '<div class="text-success small" id="seo-preview-url">https://example.com/...</div>';
        html += '<div class="text-primary fw-bold" id="seo-preview-title" style="font-size:18px">SEO标题预览</div>';
        html += '<div class="text-muted small" id="seo-preview-desc">SEO描述预览，显示在搜索结果中的描述文本...</div>';
        html += '</div><div class="d-flex align-items-center gap-2 mb-2">';
        html += '<div class="seo-score"><span class="badge bg-secondary" id="seo-score-badge">评分: --</span></div>';
        html += '<button class="btn btn-sm btn-primary" onclick="aiGenerateSeoTitle(' + contentId + ')">AI生成标题</button>';
        html += '<button class="btn btn-sm btn-primary" onclick="aiGenerateSeoDesc(' + contentId + ')">AI生成描述</button>';
        html += '<button class="btn btn-sm btn-info" onclick="aiExtractKeywords(' + contentId + ')">提取关键词</button>';
        html += '</div><div id="seo-keywords-display"></div><div id="seo-suggestions" class="mt-2"></div>';
        html += '</div></div></div>';
        $('#seo-panel-container').html(html);
        loadSeoScore(contentId);
    }

    function initSeoPanel(contentId) {
        loadSeoScore(contentId);
        // 监听标题和描述输入框变化，实时更新预览
        $('input[name="title"], input[name="seo_title"]').on('input', function() {
            $('#seo-preview-title').text($(this).val() || 'SEO标题预览');
        });
        $('textarea[name="description"], input[name="seo_description"]').on('input', function() {
            $('#seo-preview-desc').text($(this).val() || 'SEO描述预览...');
        });
    }

    function loadSeoScore(contentId) {
        $.post('/admin/content/ai_seo_optimize/' + contentId, {}, function(res) {
            if (res.code === 0) {
                var score = res.data.score.score;
                var badge = $('#seo-score-badge');
                badge.text('评分: ' + score);
                badge.removeClass('bg-secondary bg-danger bg-warning bg-success');
                if (score >= 80) badge.addClass('bg-success');
                else if (score >= 60) badge.addClass('bg-warning');
                else badge.addClass('bg-danger');

                if (res.data.score.suggestions && res.data.score.suggestions.length) {
                    var sugHtml = '<small class="text-muted">建议:</small><ul class="small">';
                    res.data.score.suggestions.forEach(function(s) {
                        sugHtml += '<li>' + s + '</li>';
                    });
                    sugHtml += '</ul>';
                    $('#seo-suggestions').html(sugHtml);
                }
            }
        }).fail(function() {});
    }
});

// AI生成SEO标题
function aiGenerateSeoTitle(contentId) {
    $.post('/admin/content/ai_seo_optimize/' + contentId, {}, function(res) {
        if (res.code === 0) {
            $('input[name="seo_title"]').val(res.data.title);
            $('#seo-preview-title').text(res.data.title);
            window.ui.toast('SEO标题已生成', 'success');
        } else {
            window.ui.toast(res.msg, 'danger');
        }
    });
}

// AI生成SEO描述
function aiGenerateSeoDesc(contentId) {
    $.post('/admin/content/ai_seo_optimize/' + contentId, {}, function(res) {
        if (res.code === 0) {
            $('textarea[name="description"], input[name="seo_description"]').val(res.data.description);
            $('#seo-preview-desc').text(res.data.description);
            window.ui.toast('SEO描述已生成', 'success');
        }
    });
}

// AI提取关键词
function aiExtractKeywords(contentId) {
    $.post('/admin/content/ai_seo_optimize/' + contentId, {}, function(res) {
        if (res.code === 0) {
            var keywords = res.data.keywords;
            $('input[name="seo_keywords"]').val(keywords.join(','));
            var html = keywords.map(function(k) {
                return '<span class="badge bg-info me-1">' + k + '</span>';
            }).join('');
            $('#seo-keywords-display').html(html);
            window.ui.toast('关键词已提取', 'success');
        }
    });
}
