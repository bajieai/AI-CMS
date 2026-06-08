// V2.9.9-R4: AI配图默认配置（后端注入，PJAX 并行加载兼容）
var AI_CMS_CONFIG = window.AI_CMS_CONFIG || {};
var AI_IMAGE_CANDIDATE_COUNT = AI_CMS_CONFIG.ai_image_candidate_count || 4;

// 全局CSRF Token自动刷新：当服务端返回新Token时自动更新表单字段
$(document).ajaxSuccess(function(event, xhr, settings) {
    var res = xhr.responseJSON;
    if (res && res.code === 403 && res.data && res.data.token) {
        $('input[name="__token__"]').val(res.data.token);
    }
});

// V2.9.5: 付费阅读开关联动
$('#is_paid').on('change', function() {
    $('#pay-config-group').toggle($(this).is(':checked'));
});

// TinyMCE编辑器（完全本地化）
if (document.getElementById('editor') && typeof tinymce !== 'undefined') {
    var existing = tinymce.get('editor');
    if (existing) existing.destroy();
    tinymce.init({
    selector: '#editor',
    height: 400,
    language: 'zh-Hans',
    menubar: 'edit view insert format table',
    plugins: 'link image table code fullscreen',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image table | code fullscreen',
    relative_urls: false,
    convert_urls: false,
    promotion: false,
    branding: false,
    setup: function(editor) {
        // AI快捷键：Ctrl+Shift+C 续写
        editor.addShortcut('ctrl+shift+c', 'AI续写', function() {
            var selected = editor.selection.getContent({format: 'text'});
            var text = selected || editor.getContent({format: 'text'}).substring(0, 500);
            $('#aiPrompt').val('请续写以下内容：\n' + text);
            aiGenerate('append');
        });
        // AI快捷键：Ctrl+Shift+X 改写（避免与Ctrl+Shift+R强制刷新冲突）
        editor.addShortcut('ctrl+shift+x', 'AI改写', function() {
            var selected = editor.selection.getContent({format: 'text'});
            var text = selected || editor.getContent({format: 'text'}).substring(0, 500);
            $('#aiPrompt').val('请改写以下内容，保持原意但使用不同的表达方式：\n' + text);
            aiGenerate(selected ? 'insert' : 'replace');
        });
        // AI快捷键：Ctrl+Shift+E 扩写
        editor.addShortcut('ctrl+shift+e', 'AI扩写', function() {
            var selected = editor.selection.getContent({format: 'text'});
            var text = selected || editor.getContent({format: 'text'}).substring(0, 500);
            $('#aiPrompt').val('请扩写以下内容，增加更多细节和描述：\n' + text);
            aiGenerate(selected ? 'insert' : 'append');
        });
        // AI快捷键：Ctrl+Shift+U 总结（避免与Ctrl+S冲突）
        editor.addShortcut('ctrl+shift+u', 'AI总结', function() {
            var text = editor.getContent({format: 'text'}).substring(0, 1000);
            $('#aiPrompt').val('请总结以下内容的要点：\n' + text);
            aiGenerate('append');
        });
        // V2.9.13: TinyMCE工具栏AI按钮已移除（window.AiImage/AiSeo从未定义，无实际功能）
    },
    // 图片上传
    images_upload_url: '/api/upload/image',
    images_upload_handler: function(blobInfo, success, failure) {
        var formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        var csrfToken = $('input[name="__token__"]').val();
        var ajaxOpts = {
            url: '/api/upload/image',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.code === 0 && res.data && res.data.url) {
                    success(res.data.url);
                } else {
                    failure(res.msg || '上传失败');
                }
            },
            error: function() { failure('上传失败'); }
        };
        if (csrfToken) {
            ajaxOpts.headers = { 'X-CSRF-TOKEN': csrfToken };
        }
        $.ajax(ajaxOpts);
    }
});
}

// 扩展字段动态渲染（PJAX 并行加载兼容）
var extData = AI_CMS_CONFIG.ext_data || {};

function renderExtFields(fields) {
    var $card = $('#extFieldsCard');
    if ($card.length === 0) {
        var $tagWrap = $('#tagList').closest('.mb-3');
        $tagWrap.after(
            '<div class="card border-info mb-3" id="extFieldsCard" style="display:none;">' +
            '<div class="card-header py-2 bg-info text-white"><i class="bi bi-sliders me-1"></i>扩展字段</div>' +
            '<div class="card-body py-2"></div></div>'
        );
        $card = $('#extFieldsCard');
    }
    var html = '';
    if (fields && fields.length > 0) {
        html += '<div class="card-header py-2 bg-info text-white"><i class="bi bi-sliders me-1"></i>扩展字段</div>';
        html += '<div class="card-body py-2">';
        for (var i = 0; i < fields.length; i++) {
            var f = fields[i];
            var val = extData[f.name] || '';
            // 清理 \\r\\n 控制字符：在 <input value> 中会导致显示为乱码
            var valClean = String(val).replace(/\r\n|\r|\n/g, ' ');
            html += '<div class="mb-2">';
            html += '<label class="form-label small">' + f.title + (f.required ? ' <span class="text-danger">*</span>' : '') + '</label>';
            if (f.type === 'textarea') {
                html += '<textarea name="ext[' + f.name + ']" class="form-control form-control-sm" rows="3" placeholder="' + (f.placeholder || '') + '"' + (f.required ? ' required' : '') + '>' + val + '</textarea>';
            } else if (f.type === 'number') {
                html += '<input type="number" name="ext[' + f.name + ']" class="form-control form-control-sm" value="' + valClean + '" placeholder="' + (f.placeholder || '') + '"' + (f.required ? ' required' : '') + '>';
            } else {
                html += '<input type="text" name="ext[' + f.name + ']" class="form-control form-control-sm" value="' + valClean + '" placeholder="' + (f.placeholder || '') + '"' + (f.required ? ' required' : '') + '>';
            }
            html += '</div>';
        }
        html += '</div>';
        $card.html(html).show();
    } else {
        $card.hide();
    }
}

$('#typeSelect').on('change', function() {
    var type = $(this).val();
    // 更新扩展字段
    $.ajax({
        url: '/admin/content/getExtFields',
        type: 'GET',
        data: { type: type },
        dataType: 'json',
        success: function(res) {
            if (res.code === 0) {
                renderExtFields(res.data.fields);
            }
        }
    });
    // 更新分类列表
    $.ajax({
        url: '/admin/content/getCates',
        type: 'GET',
        data: { type: type },
        dataType: 'json',
        success: function(res) {
            if (res.code === 0) {
                var options = '<option value="0">请选择分类</option>';
                for (var id in res.data.cates) {
                    options += '<option value="' + id + '">' + res.data.cates[id] + '</option>';
                }
                $('#cateSelect').html(options);
            }
        }
    });
});

// 封面上传
function uploadCover() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = function(e) {
        var file = e.target.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('file', file);
        var csrfToken = $('input[name="__token__"]').val();
        var ajaxOpts = {
            url: '/api/upload/image',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.code === 0 && res.data && res.data.url) {
                    $('#coverInput').val(res.data.url);
                    $('#coverPreview img').attr('src', res.data.url);
                    $('#coverPreview').show();
                } else {
                    showToast(res.msg || '上传失败', 'danger');
                }
            }
        };
        if (csrfToken) {
            ajaxOpts.headers = { 'X-CSRF-TOKEN': csrfToken };
        }
        $.ajax(ajaxOpts);
    };
    input.click();
}

// 封面预览
$('#coverInput').on('change', function() {
    var url = $(this).val();
    if (url) {
        $('#coverPreview img').attr('src', url);
        $('#coverPreview').show();
    } else {
        $('#coverPreview').hide();
    }
});

// AI生成
function aiGenerate(mode) {
    mode = mode || 'replace';
    var prompt = $('#aiPrompt').val().trim();
    if (!prompt) { showToast('请输入AI写作提示', 'warning'); return; }
    var $btn = $('#btnAi');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>生成中...');
    var csrfToken = $('input[name="__token__"]').val();
    var style = $('#aiWritingStyle').val();
    var ajaxOpts = {
        url: '/api/ai/generate',
        type: 'POST',
        data: { prompt: prompt, template: 'continue', style: style },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i>AI 生成内容');
            if (res.code === 0 && res.data && res.data.content) {
                var editor = tinymce.activeEditor;
                var content = res.data.content;
                if (mode === 'replace') {
                    editor.setContent(content);
                    showToast('AI内容已替换', 'success');
                } else if (mode === 'append') {
                    var current = editor.getContent();
                    editor.setContent(current + (current ? '<p></p>' : '') + content);
                    showToast('AI内容已追加到末尾', 'success');
                } else if (mode === 'insert') {
                    editor.insertContent(content);
                    showToast('AI内容已插入到光标位置', 'success');
                }
            } else {
                showToast(res.msg || '生成失败', 'danger');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i>AI 生成内容');
            showToast('网络错误', 'danger');
        }
    };
    if (csrfToken) {
        ajaxOpts.headers = { 'X-CSRF-TOKEN': csrfToken };
    }
    $.ajax(ajaxOpts);
}

// 草稿自动保存（60秒间隔）
var autoSaveTimer = null;
var lastSaveContent = '';
function startAutoSave() {
    if (!$('#contentForm').find('input[name="id"]').length && !window.location.pathname.match(/\/edit\//)) {
        return; // 新建内容不自动保存（无ID）
    }
    autoSaveTimer = setInterval(function() {
        var editor = tinymce.activeEditor;
        if (!editor) return;
        var currentContent = editor.getContent({format: 'text'});
        if (currentContent === lastSaveContent) return;
        lastSaveContent = currentContent;
        tinymce.triggerSave();
        var formData = $('#contentForm').serialize();
        var contentId = window.location.pathname.match(/\/edit\/(\d+)/);
        if (!contentId) return;
        $.ajax({
            url: '/admin/content/autoSave/' + contentId[1],
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.code === 0) {
                    $('#autoSaveStatus').html('<i class="bi bi-check-circle text-success me-1"></i>自动保存于 ' + (res.data.time || ''));
                } else {
                    // 降级到LocalStorage
                    try {
                        localStorage.setItem('cms_autosave_' + contentId[1], JSON.stringify({
                            title: $('[name="title"]').val(),
                            content: editor.getContent(),
                            time: new Date().toLocaleTimeString()
                        }));
                        $('#autoSaveStatus').html('<i class="bi bi-cloud-check text-info me-1"></i>已保存到本地 ' + new Date().toLocaleTimeString());
                    } catch(e) {}
                }
            },
            error: function() {
                // 网络错误降级到LocalStorage
                try {
                    localStorage.setItem('cms_autosave_' + contentId[1], JSON.stringify({
                        title: $('[name="title"]').val(),
                        content: editor.getContent(),
                        time: new Date().toLocaleTimeString()
                    }));
                    $('#autoSaveStatus').html('<i class="bi bi-cloud-check text-info me-1"></i>已保存到本地 ' + new Date().toLocaleTimeString());
                } catch(e) {}
            }
        });
    }, 60000);
}
// 编辑器就绪后启动自动保存
tinymce.activeEditor && startAutoSave();
setTimeout(startAutoSave, 2000);

// ================= V2.9.3 M28: 同步到平台 =================
function syncToPlatforms(contentId) {
    showConfirm('同步到平台', '确定要将此内容同步到所有已启用的发布平台吗？', function() {
        ajaxPost('/admin/publish/sync', { content_id: contentId }, function(res) {
            showToast(res.msg, 'success');
        });
    });
}

// ================= V2.8 SEO一键优化 + V3.1增强 =================
// SEO字段实时预览
$('#seoTitle, #seoKeywords, #seoDescription').on('input', function() {
    updateSeoPreview();
});

function updateSeoPreview() {
    var title = $('#seoTitle').val().trim() || $('[name="title"]').val().trim() || '标题';
    var description = $('#seoDescription').val().trim() || $('[name="excerpt"]').val().trim() || '描述';
    var url = window.location.origin + ($('[name="id"]').val() ? '/news/' + $('[name="id"]').val() : '/preview');

    $('#previewTitle').text(title);
    $('#previewUrl').text(url);
    $('#previewDescription').text(description.substring(0, 150));
    $('#seoPreview').show();
}

// V3.1: 存储优化前的SEO数据用于对比
var seoBeforeData = null;

// SEO一键优化
function seoOptimize() {
    var content = tinymce.activeEditor.getContent({format: 'text'});
    var title = $('[name="title"]').val().trim();

    if (!content) {
        showToast('请先输入内容', 'warning');
        return;
    }

    // 保存优化前数据
    seoBeforeData = {
        title: $('#seoTitle').val().trim() || title,
        keywords: $('#seoKeywords').val().trim(),
        description: $('#seoDescription').val().trim()
    };

    var $btn = $('#btnSeo');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>优化中...');

    var csrfToken = $('input[name="__token__"]').val();
    $.ajax({
        url: '/api/ai/seo',
        type: 'POST',
        data: {
            content: content.substring(0, 2000),
            title: title,
            keywords: ''
        },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i>一键优化');
            if (res.code === 0 && res.data) {
                // V3.1: 显示对比弹窗而非直接填充
                showSeoCompareModal(seoBeforeData, res.data);
            } else {
                showToast(res.msg || '优化失败', 'danger');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i>一键优化');
            showToast('网络错误', 'danger');
        }
    });
}

// V3.1: 显示SEO对比弹窗
function showSeoCompareModal(oldData, newData) {
    $('#seoCompareOldTitle').text(oldData.title || '（无）');
    $('#seoCompareOldKeywords').text(oldData.keywords || '（无）');
    $('#seoCompareOldDesc').text(oldData.description || '（无）');

    $('#seoCompareNewTitle').text(newData.title || '（无）');
    $('#seoCompareNewKeywords').text(
        Array.isArray(newData.keywords) ? newData.keywords.join(', ') : (newData.keywords || '（无）')
    );
    $('#seoCompareNewDesc').text(newData.description || '（无）');

    // 存储新数据供应用时使用
    window.seoNewData = newData;

    var modal = new bootstrap.Modal(document.getElementById('seoCompareModal'));
    modal.show();
}

// V3.1: 应用SEO优化结果
function applySeoOptimize() {
    var data = window.seoNewData;
    if (!data) return;

    if (data.title) $('#seoTitle').val(data.title);
    if (data.keywords) {
        $('#seoKeywords').val(Array.isArray(data.keywords) ? data.keywords.join(',') : data.keywords);
    }
    if (data.description) $('#seoDescription').val(data.description);

    updateSeoPreview();
    calculateSeoScore();

    var modalEl = document.getElementById('seoCompareModal');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    showToast('SEO优化结果已应用', 'success');
}

// V3.1: SEO评分纯算法
function calculateSeoScore() {
    var title = $('[name="title"]').val().trim();
    var content = tinymce.activeEditor.getContent();
    var seoTitle = $('#seoTitle').val().trim();
    var seoDesc = $('#seoDescription').val().trim();
    var seoKeywords = $('#seoKeywords').val().trim();

    var $btn = $('#btnSeoScore');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>评分中...');

    $.ajax({
        url: '/api/ai/seo_score',
        type: 'POST',
        data: {
            title: title,
            content: content,
            seo_title: seoTitle,
            seo_description: seoDesc,
            seo_keywords: seoKeywords
        },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).html('<i class="bi bi-bar-chart me-1"></i>评分');
            if (res.code === 0 && res.data) {
                renderSeoScore(res.data);
            } else {
                showToast(res.msg || '评分失败', 'danger');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-bar-chart me-1"></i>评分');
            showToast('网络错误', 'danger');
        }
    });
}

// V3.1: 渲染SEO评分UI
function renderSeoScore(data) {
    var score = data.score || 0;
    var barClass = score >= 80 ? 'bg-success' : (score >= 60 ? 'bg-warning' : 'bg-danger');
    var textClass = score >= 80 ? 'text-success' : (score >= 60 ? 'text-warning' : 'text-danger');

    $('#seoScoreValue').text(score + '分').attr('class', 'fw-bold ' + textClass);
    $('#seoScoreBar').attr('class', 'progress-bar ' + barClass).css('width', score + '%');

    var sugHtml = '';
    if (data.suggestions && data.suggestions.length > 0) {
        sugHtml += '<ul class="mb-0 ps-3 small">';
        data.suggestions.forEach(function(s) {
            sugHtml += '<li>' + escapeHtml(s) + '</li>';
        });
        sugHtml += '</ul>';
    } else {
        sugHtml = '<div class="small text-success"><i class="bi bi-check-circle me-1"></i>SEO表现优秀！</div>';
    }
    $('#seoScoreSuggestions').html(sugHtml);
    $('#seoScoreArea').show();
}

// 页面加载后初始化SEO预览
setTimeout(updateSeoPreview, 500);

// V3.1: 加载写作风格列表
$(function() {
    $.get('/api/ai/styles', function(res) {
        if (res.code === 0 && res.data && res.data.list) {
            var options = '<option value="">默认风格</option>';
            res.data.list.forEach(function(s) {
                options += '<option value="' + s.key + '">' + s.name + '</option>';
            });
            $('#aiWritingStyle').html(options);
        }
    });
});

// ================= V2.8 AI质量检测 =================
var qualityCheckTimer = null;
var lastQualityCheckWordCount = 0;

// 监听编辑器内容变化，>500字显示提示条
setTimeout(function() {
    var editor = tinymce.activeEditor;
    if (editor) {
        editor.on('keyup', function() {
            clearTimeout(qualityCheckTimer);
            qualityCheckTimer = setTimeout(function() {
                var content = editor.getContent({format: 'text'});
                var wordCount = content.length;
                if (wordCount > 500 && wordCount !== lastQualityCheckWordCount) {
                    $('#qualityTip').removeClass('d-none');
                    lastQualityCheckWordCount = wordCount;
                } else if (wordCount <= 500) {
                    $('#qualityTip').addClass('d-none');
                }
            }, 1000); // 防抖1秒
        });
    }
}, 2500);

// V2.9.9-R4: AI配图生成 — 支持4候选图串行调用+进度条+尺寸选择
function aiGenerateImage() {
    var content = tinymce.activeEditor.getContent({format: 'text'});
    var title = $('[name="title"]').val().trim();
    var prompt = title || content.substring(0, 100);
    
    if (!prompt) {
        showToast('请先输入标题或内容', 'warning');
        return;
    }
    
    callAiImageApiBatch(AI_IMAGE_CANDIDATE_COUNT);
}

function aiRegenerateImage() {
    var content = tinymce.activeEditor.getContent({format: 'text'});
    var title = $('[name="title"]').val().trim();
    var prompt = title || content.substring(0, 100);
    
    if (!prompt) {
        showToast('请先输入标题或内容', 'warning');
        return;
    }
    
    callAiImageApiBatch(AI_IMAGE_CANDIDATE_COUNT);
}

/**
 * 串行批量生成候选图 — R4新增
 * @param {number} count 生成数量(1-4)
 */
function callAiImageApiBatch(count) {
    var $btn = $('#btnAiImage');
    var style = $('#aiImageStyle').val() || 'realistic';
    var size = $('#aiImageSize').val() || '1024x1024';
    var content = tinymce.activeEditor.getContent({format: 'text'});
    var title = $('[name="title"]').val().trim();
    var prompt = title || content.substring(0, 100);
    
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>生成中...');
    
    // 显示进度条
    var $progressArea = $('#aiImageProgressArea');
    var $progressBar = $('#aiImageProgressBar');
    var $progressText = $('#aiImageProgressText');
    var $progressPercent = $('#aiImageProgressPercent');
    $progressArea.removeClass('d-none');
    $progressBar.css('width', '0%');
    $progressText.text('生成中 0/' + count + '...');
    $progressPercent.text('0%');
    
    $('#aiImageArea').show();
    $('#aiImageResults').html('');
    
    var results = [];
    var completed = 0;
    
    function doRequest(index) {
        if (index >= count) {
            // 全部完成
            $btn.prop('disabled', false).html('<i class="bi bi-image me-1"></i>AI配图');
            $progressArea.addClass('d-none');
            showAiImageResults(results);
            return;
        }
        
        $.ajax({
            url: '/api/ai/image',
            type: 'POST',
            data: { 
                prompt: prompt,
                style: style,
                size: size,
                regenerate: 1
            },
            dataType: 'json',
            success: function(res) {
                completed++;
                var percent = Math.round((completed / count) * 100);
                $progressBar.css('width', percent + '%');
                $progressText.text('生成中 ' + completed + '/' + count + '...');
                $progressPercent.text(percent + '%');
                
                if (res.code === 0 && res.data && res.data.url) {
                    results.push(res.data);
                }
                
                // 串行下一个
                doRequest(index + 1);
            },
            error: function() {
                completed++;
                var percent = Math.round((completed / count) * 100);
                $progressBar.css('width', percent + '%');
                $progressText.text('生成中 ' + completed + '/' + count + '...');
                $progressPercent.text(percent + '%');
                
                // 串行下一个（失败也继续）
                doRequest(index + 1);
            }
        });
    }
    
    doRequest(0);
}

/**
 * 单图生成API（兼容旧逻辑）
 */
function callAiImageApi(prompt, regenerate) {
    var $btn = $('#btnAiImage');
    var style = $('#aiImageStyle').val() || 'realistic';
    var size = $('#aiImageSize').val() || '1024x1024';
    
    $.ajax({
        url: '/api/ai/image',
        type: 'POST',
        data: { 
            prompt: prompt,
            style: style,
            size: size,
            regenerate: regenerate ? 1 : 0
        },
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).html('<i class="bi bi-image me-1"></i>AI配图');
            if (res.code === 0 && res.data && res.data.url) {
                showAiImageResults([res.data]);
            } else {
                showToast(res.msg || '生成失败', 'danger');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-image me-1"></i>AI配图');
            showToast('网络错误', 'danger');
        }
    });
}

/**
 * 展示候选图结果 — R4增强: 支持多张图网格+插入编辑器
 * @param {Array} results 图片结果数组
 */
function showAiImageResults(results) {
    if (!results || results.length === 0) {
        showToast('未能生成有效图片，请重试', 'warning');
        return;
    }
    
    var html = '';
    results.forEach(function(data, idx) {
        var sizeLabel = (data.width || 0) + 'x' + (data.height || 0);
        html += '<div class="col-6">';
        html += '<div class="card h-100">';
        html += '<div class="position-relative" style="height:100px;overflow:hidden;">';
        html += '<img src="' + data.url + '" class="card-img-top" style="width:100%;height:100%;object-fit:cover;cursor:pointer;" onclick="selectAiImage(\'' + data.url + '\')" title="点击设为封面">';
        html += '<span class="position-absolute top-0 start-0 badge bg-dark" style="font-size:9px;">' + (idx + 1) + '</span>';
        html += '<span class="position-absolute top-0 end-0 badge bg-primary" style="font-size:9px;">' + sizeLabel + '</span>';
        html += '</div>';
        html += '<div class="card-body p-1 d-flex gap-1">';
        html += '<button type="button" class="btn btn-xs btn-outline-primary flex-fill" style="font-size:11px;padding:2px 4px;" onclick="selectAiImage(\'' + data.url + '\')" title="设为封面图"><i class="bi bi-check-circle"></i> 封面</button>';
        html += '<button type="button" class="btn btn-xs btn-outline-success flex-fill" style="font-size:11px;padding:2px 4px;" onclick="insertImageToEditor(\'' + data.url + '\', \'' + escapeHtml($('[name=\"title\"]').val() || '配图') + '\')" title="插入正文"><i class="bi bi-body-text"></i> 正文</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    
    $('#aiImageResults').html(html);
    $('#aiImageArea').show();
}

function selectAiImage(url) {
    $('#coverInput').val(url);
    $('#coverPreview img').attr('src', url);
    $('#coverPreview').show();
    showToast('已选择为封面图', 'success');
}

/**
 * 插入图片到编辑器正文 — R4新增
 * @param {string} url 图片URL
 * @param {string} alt 替代文本
 */
function insertImageToEditor(url, alt) {
    var editor = tinymce.activeEditor;
    if (!editor) {
        showToast('编辑器未就绪', 'warning');
        return;
    }
    var imgHtml = '<img src="' + url + '" alt="' + (alt || '配图') + '" style="max-width:100%;height:auto;display:block;margin:12px auto;">';
    editor.insertContent(imgHtml);
    showToast('已插入到正文', 'success');
}

// AI质量检测
function aiQualityCheck() {
    var editor = tinymce.activeEditor;
    if (!editor) {
        showToast('编辑器未就绪', 'warning');
        return;
    }
    var content = editor.getContent({format: 'text'});
    if (!content.trim()) {
        showToast('请先输入内容', 'warning');
        return;
    }
    
    var $btn = $('#btnQuality');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>检测中...');
    
    var csrfToken = $('input[name="__token__"]').val();
    $.ajax({
        url: '/api/ai/quality',
        type: 'POST',
        data: { content: content.substring(0, 3000) }, // 限制长度
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>内容质量检测');
            if (res.code === 0 && res.data) {
                showQualityModal(res.data);
            } else {
                showToast(res.msg || '检测失败', 'danger');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>内容质量检测');
            showToast('网络错误', 'danger');
        }
    });
}

// 显示质量检测结果弹窗
function showQualityModal(data) {
    var html = '<div class="modal fade" id="qualityModal" tabindex="-1">';
    html += '<div class="modal-dialog modal-lg modal-dialog-centered">';
    html += '<div class="modal-content">';
    html += '<div class="modal-header">';
    html += '<h5 class="modal-title"><i class="bi bi-check-circle me-1"></i>内容质量检测结果</h5>';
    html += '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>';
    html += '</div>';
    html += '<div class="modal-body">';
    
    // 总分
    var score = data.overall_score || 0;
    var scoreClass = score >= 80 ? 'text-success' : (score >= 60 ? 'text-warning' : 'text-danger');
    html += '<div class="text-center mb-4">';
    html += '<h1 class="' + scoreClass + '">' + score + '分</h1>';
    html += '<p class="text-muted">综合质量评分（满分100）</p>';
    html += '</div>';
    
    // 维度评分
    html += '<div class="row g-3 mb-4">';
    var dimensions = data.dimensions || {};
    var dimNames = { readability: '可读性', seo: 'SEO优化', originality: '原创性', structure: '结构完整性', engagement: '吸引力' };
    for (var key in dimNames) {
        if (dimensions.hasOwnProperty(key)) {
            var val = dimensions[key] || 0;
            var barClass = val >= 80 ? 'bg-success' : (val >= 60 ? 'bg-warning' : 'bg-danger');
            html += '<div class="col-sm-6 col-md-4">';
            html += '<div class="border rounded p-2">';
            html += '<div class="d-flex justify-content-between mb-1">';
            html += '<small>' + dimNames[key] + '</small>';
            html += '<small class="' + scoreClass + '">' + val + '分</small>';
            html += '</div>';
            html += '<div class="progress" style="height:6px;">';
            html += '<div class="progress-bar ' + barClass + '" style="width:' + val + '%"></div>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }
    }
    html += '</div>';
    
    // 建议
    if (data.suggestions && data.suggestions.length > 0) {
        html += '<div class="alert alert-info">';
        html += '<h6><i class="bi bi-lightbulb me-1"></i>改进建议</h6>';
        html += '<ul class="mb-0 ps-3">';
        data.suggestions.forEach(function(s, idx) {
            html += '<li class="mb-1 d-flex justify-content-between align-items-start">';
            html += '<span>' + escapeHtml(s) + '</span>';
            html += '<button type="button" class="btn btn-xs btn-outline-primary ms-2" style="font-size:10px;padding:1px 4px;" onclick="applyQualitySuggestion(' + idx + ', \'' + escapeHtml(s).replace(/'/g, "\\'") + '\')">执行</button>';
            html += '</li>';
        });
        html += '</ul>';
        html += '</div>';
    }

    html += '</div>';
    html += '<div class="modal-footer">';
    html += '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">关闭</button>';
    html += '<button type="button" class="btn btn-primary btn-sm" onclick="aiQualityAutoFix()">';
    html += '<i class="bi bi-magic me-1"></i>一键优化全部';
    html += '</button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    // 移除旧弹窗
    $('#qualityModal').remove();
    // 添加新弹窗
    $('body').append(html);
    // 显示弹窗
    var modal = new bootstrap.Modal(document.getElementById('qualityModal'));
    modal.show();
}

// ================= V2.7 章节管理组件 =================
var chapterList = [];
var parentContentId = 0;
var contentIdMatch = window.location.pathname.match(/\/edit\/(\d+)/);
if (contentIdMatch) {
    parentContentId = parseInt(contentIdMatch[1]);
}

function loadChapters() {
    if (!parentContentId) return;
    $.get('/admin/content/getChapters/' + parentContentId, function(res) {
        if (res.code === 0) {
            chapterList = res.data.list || [];
            renderChapterTable();
        }
    });
}

function renderChapterTable() {
    var $tbody = $('#chapterTable tbody');
    $tbody.empty();
    if (chapterList.length === 0) {
        $('#chapterEmpty').show();
        return;
    }
    $('#chapterEmpty').hide();
    chapterList.forEach(function(ch, idx) {
        var html = '<tr data-id="' + (ch.id || 0) + '" data-sort="' + (ch.chapter_sort || idx) + '">';
        html += '<td class="text-center text-muted">' + (idx + 1) + '</td>';
        html += '<td><input type="text" class="form-control form-control-sm chapter-title" value="' + escapeHtml(ch.chapter_title || ch.title || '') + '" placeholder="章节标题"></td>';
        html += '<td><input type="number" class="form-control form-control-sm chapter-price" value="' + (ch.chapter_price || 0) + '" min="0" step="0.01"></td>';
        html += '<td class="text-center"><input type="checkbox" class="form-check-input chapter-free" ' + (ch.is_free_chapter ? 'checked' : '') + '></td>';
        html += '<td><input type="number" class="form-control form-control-sm chapter-sort" value="' + (ch.chapter_sort || idx) + '" min="0"></td>';
        html += '<td>';
        html += '<button type="button" class="btn btn-sm btn-success me-1" onclick="saveChapterRow(this)" title="保存"><i class="bi bi-check-lg"></i></button>';
        html += '<button type="button" class="btn btn-sm btn-danger" onclick="deleteChapterRow(this)" title="删除"><i class="bi bi-trash"></i></button>';
        html += '</td>';
        html += '</tr>';
        $tbody.append(html);
    });
}

function addChapterRow() {
    chapterList.push({
        id: 0,
        title: '',
        chapter_title: '',
        chapter_price: 0,
        is_free_chapter: 0,
        chapter_sort: chapterList.length,
        status: 2
    });
    renderChapterTable();
    // 滚动到新行
    var $rows = $('#chapterTable tbody tr');
    if ($rows.length) {
        $rows.last()[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        $rows.last().find('.chapter-title').focus();
    }
}

function saveChapterRow(btn) {
    var $tr = $(btn).closest('tr');
    var id = parseInt($tr.attr('data-id')) || 0;
    var data = {
        id: id,
        parent_id: parentContentId,
        title: $tr.find('.chapter-title').val().trim(),
        chapter_title: $tr.find('.chapter-title').val().trim(),
        chapter_price: parseFloat($tr.find('.chapter-price').val()) || 0,
        is_free_chapter: $tr.find('.chapter-free').is(':checked') ? 1 : 0,
        chapter_sort: parseInt($tr.find('.chapter-sort').val()) || 0,
        content: '',
        status: 2
    };
    if (!data.title) { showToast('章节标题不能为空', 'warning'); return; }
    $.ajax({
        url: '/admin/content/saveChapter',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            if (res.code === 0) {
                showToast('保存成功', 'success');
                if (id === 0 && res.data.id) {
                    $tr.attr('data-id', res.data.id);
                }
                loadChapters(); // 刷新以同步排序
            } else {
                showToast(res.msg || '保存失败', 'danger');
            }
        },
        error: function() { showToast('网络错误', 'danger'); }
    });
}

function deleteChapterRow(btn) {
    var $tr = $(btn).closest('tr');
    var id = parseInt($tr.attr('data-id')) || 0;
    if (!id) {
        $tr.remove();
        return;
    }
    if (!confirm('确定删除该章节？')) return;
    $.ajax({
        url: '/admin/content/deleteChapter/' + id,
        type: 'POST',
        dataType: 'json',
        success: function(res) {
            if (res.code === 0) {
                showToast('删除成功', 'success');
                loadChapters();
            } else {
                showToast(res.msg || '删除失败', 'danger');
            }
        },
        error: function() { showToast('网络错误', 'danger'); }
    });
}

// ================= V3.1: 批量配图功能 =================
function openBatchImageModal() {
    $('#batchImageResults').empty();
    $('#batchImageProgressArea').hide();
    $('#btnStartBatchImage').prop('disabled', false).show();
    var modal = new bootstrap.Modal(document.getElementById('batchImageModal'));
    modal.show();
}

function startBatchImage() {
    var editor = tinymce.activeEditor;
    if (!editor) { showToast('编辑器未就绪', 'warning'); return; }

    var title = $('[name="title"]').val().trim();
    var content = editor.getContent({format: 'text'});
    if (!title && !content) {
        showToast('请先输入标题或内容', 'warning'); return;
    }

    var strategy = $('#batchImageStrategy').val();
    var count = parseInt($('#batchImageCount').val()) || 3;
    var style = $('#batchImageStyle').val() || 'realistic';
    var maxCount = 5;
    if (count > maxCount) count = maxCount;

    // 自动策略：根据段落数计算
    if (strategy === 'auto') {
        var paragraphs = content.split(/\n+/).filter(function(p) { return p.trim().length > 20; });
        count = Math.min(Math.max(1, Math.floor(paragraphs.length / 3)), maxCount);
    }

    var $btn = $('#btnStartBatchImage');
    $btn.prop('disabled', true).hide();
    $('#batchImageProgressArea').show();
    $('#batchImageResults').empty();

    var results = [];
    var current = 0;

    function generateNext() {
        if (current >= count) {
            $('#batchImageProgressText').text('配图完成！共 ' + count + ' 张');
            $('#batchImageStatusText').html('<i class="bi bi-check-circle me-1"></i>全部生成完成，点击下方图片可插入编辑器');
            $('#batchImageProgressBar').removeClass('progress-bar-animated').css('width', '100%');
            $btn.prop('disabled', false).show().html('<i class="bi bi-check-lg me-1"></i>完成');
            return;
        }

        var progress = Math.round((current / count) * 100);
        $('#batchImageProgressBar').css('width', progress + '%');
        $('#batchImageProgressText').text('正在生成第 ' + (current + 1) + '/' + count + ' 张...');
        $('#batchImageStatusText').html('<i class="bi bi-hourglass-split me-1"></i>AI正在生成第 ' + (current + 1) + ' 张图片...');

        $.ajax({
            url: '/api/ai/batch_image',
            type: 'POST',
            data: {
                title: title,
                content: content,
                style: style,
                paragraph_index: current
            },
            dataType: 'json',
            success: function(res) {
                if (res.code === 0 && res.data && res.data.url) {
                    results.push(res.data);
                    appendBatchImageResult(res.data, current);
                    if (res.data.quota) {
                        $('#batchImageQuotaText').text('今日剩余 ' + res.data.quota.remaining + '/' + res.data.quota.limit);
                    }
                } else {
                    showToast('第' + (current + 1) + '张生成失败: ' + (res.msg || '未知错误'), 'warning');
                }
                current++;
                setTimeout(generateNext, 500);
            },
            error: function() {
                showToast('第' + (current + 1) + '张网络错误', 'warning');
                current++;
                setTimeout(generateNext, 500);
            }
        });
    }

    generateNext();
}

function appendBatchImageResult(data, index) {
    var html = '<div class="position-relative batch-img-item" style="cursor:pointer;" data-url="' + data.url + '" data-index="' + index + '">';
    html += '<img src="' + data.url + '" class="img-thumbnail" style="max-height:80px;">';
    html += '<span class="position-absolute top-0 start-0 badge bg-dark" style="font-size:9px;">' + (index + 1) + '</span>';
    html += '<div class="position-absolute bottom-0 end-0 btn-group btn-group-sm" style="display:none;">';
    html += '<button type="button" class="btn btn-primary" title="插入编辑器" onclick="insertImageToEditor(\'' + data.url + '\', ' + index + ')"><i class="bi bi-plus-lg"></i></button>';
    html += '<button type="button" class="btn btn-success" title="设为封面" onclick="selectAiImage(\'' + data.url + '\')"><i class="bi bi-image"></i></button>';
    html += '</div>';
    html += '</div>';

    $('#batchImageResults').append(html);

    // 悬停显示操作按钮
    $('#batchImageResults .batch-img-item').off('mouseenter mouseleave').hover(
        function() { $(this).find('.btn-group').show(); },
        function() { $(this).find('.btn-group').hide(); }
    );
}

// V3.1: 插入图片到编辑器指定段落位置
function insertImageToEditor(url, paragraphIndex) {
    var editor = tinymce.activeEditor;
    if (!editor) { showToast('编辑器未就绪', 'warning'); return; }

    var content = editor.getContent();
    var paragraphs = content.split(/<p>/i).filter(function(p) { return p.trim(); });

    var imgHtml = '<p style="text-align:center;"><img src="' + url + '" alt="配图" style="max-width:100%;height:auto;" /></p>';

    if (paragraphs.length > paragraphIndex + 1) {
        // 在对应段落后插入
        var insertPos = 0;
        var count = 0;
        var tempContent = content;
        var regex = /<p[^>]*>/gi;
        var match;
        while ((match = regex.exec(tempContent)) !== null) {
            if (count === paragraphIndex + 1) {
                insertPos = match.index;
                break;
            }
            count++;
        }
        if (insertPos > 0) {
            var newContent = content.substring(0, insertPos) + imgHtml + content.substring(insertPos);
            editor.setContent(newContent);
        } else {
            editor.setContent(content + imgHtml);
        }
    } else {
        editor.setContent(content + imgHtml);
    }
    showToast('图片已插入编辑器', 'success');
}

// ================= V3.1: 社交分享功能 =================
function openShareModal(contentId) {
    var title = $('[name="title"]').val().trim();
    var desc = $('[name="excerpt"]').val().trim();
    var cover = $('#coverInput').val().trim();
    var url = window.location.origin + '/content/' + contentId;

    $('#shareUrl').val(url);
    $('#shareCardTitle').text(title || '未命名内容');
    $('#shareCardDesc').text(desc || '暂无描述');
    if (cover) {
        $('#shareCardImage').attr('src', cover).show();
    } else {
        $('#shareCardImage').hide();
    }

    // 预生成分享链接
    $.ajax({
        url: '/api/ai/share',
        type: 'POST',
        data: { title: title, description: desc, url: url, cover: cover },
        dataType: 'json',
        success: function(res) {
            if (res.code === 0 && res.data) {
                $('#shareWeibo').attr('href', res.data.weibo || '#');
                $('#shareQQ').attr('href', res.data.qq || '#');
            }
        }
    });

    var modal = new bootstrap.Modal(document.getElementById('shareModal'));
    modal.show();
}

function copyShareUrl() {
    var url = $('#shareUrl').val();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            showToast('链接已复制到剪贴板', 'success');
        });
    } else {
        var input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast('链接已复制到剪贴板', 'success');
    }
}

// ================= V3.1: 质量检测建议一键执行 =================
function applyQualitySuggestion(index, suggestion) {
    var editor = tinymce.activeEditor;
    if (!editor) return;

    // 根据建议类型执行不同操作
    if (suggestion.indexOf('标题') !== -1 || suggestion.indexOf('关键词') !== -1) {
        showToast('请在SEO设置区手动调整标题和关键词', 'info');
        $('#seoTitle')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (suggestion.indexOf('图片') !== -1 || suggestion.indexOf('配图') !== -1 || suggestion.indexOf('ALT') !== -1) {
        showToast('请使用AI配图功能添加图片', 'info');
        $('#btnAiImage')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (suggestion.indexOf('段落') !== -1 || suggestion.indexOf('结构') !== -1) {
        // 自动添加小标题
        var content = editor.getContent({format: 'text'});
        var paragraphs = content.split('\n').filter(function(p) { return p.trim().length > 10; });
        if (paragraphs.length > 3) {
            var newContent = editor.getContent();
            // 简单处理：在每3个段落前加h3标签
            var parts = newContent.split(/<p>/i);
            var result = parts[0] || '';
            for (var i = 1; i < parts.length; i++) {
                if (i % 3 === 1 && i > 1) {
                    result += '<h3>第' + Math.ceil(i / 3) + '部分</h3><p>' + parts[i];
                } else {
                    result += '<p>' + parts[i];
                }
            }
            editor.setContent(result);
            showToast('已自动优化文章结构', 'success');
        }
    } else {
        showToast('该建议需要手动调整', 'info');
    }
}

// V3.1: 一键优化全部质量建议
function aiQualityAutoFix() {
    var editor = tinymce.activeEditor;
    if (!editor) return;

    if (confirm('将使用AI对内容进行自动优化，是否继续？')) {
        var content = editor.getContent({format: 'text'});
        $('#aiPrompt').val('请优化以下内容，使其更具可读性和SEO友好性，保持原有信息不变：\n\n' + content.substring(0, 1000));
        aiGenerate('replace');

        var modalEl = document.getElementById('qualityModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 初始化加载章节
if ($('#chapterManager').length && parentContentId) {
    loadChapters();
}

// V2.9.9: AI-GEO评分
function calculateGeoScore() {
    var contentId = $('input[name="id"]').val();
    if (!contentId) {
        showToast('请先保存内容后再检测', 'warning');
        return;
    }
    var $btn = $('#btnGeoScore');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>检测中...');
    $.ajax({
        url: '/admin/content/geoScore/' + contentId,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i>检测');
            if (res.code === 0 && res.data) {
                renderGeoScore(res.data);
            } else {
                showToast(res.msg || '检测失败', 'danger');
            }
        },
        error: function() {
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i>检测');
            showToast('网络错误', 'danger');
        }
    });
}

function renderGeoScore(data) {
    var total = data.total || 0;
    var barClass = total >= 80 ? 'bg-success' : (total >= 60 ? 'bg-warning' : 'bg-danger');
    var textClass = total >= 80 ? 'text-success' : (total >= 60 ? 'text-warning' : 'text-danger');
    $('#geoScoreValue').text(total + '分').attr('class', 'fw-bold ' + textClass);
    $('#geoScoreBar').attr('class', 'progress-bar ' + barClass).css('width', total + '%');

    var dimHtml = '';
    var dimNames = { structure: '段落结构', citations: '事实引用', authority: '权威完整', entities: '实体密度' };
    var dims = data.dimensions || {};
    for (var key in dimNames) {
        if (dims.hasOwnProperty(key)) {
            var val = dims[key] || 0;
            var dBarClass = val >= 20 ? 'bg-success' : (val >= 15 ? 'bg-warning' : 'bg-danger');
            dimHtml += '<div class="d-flex justify-content-between align-items-center py-1 border-bottom">';
            dimHtml += '<span class="small text-muted">' + dimNames[key] + '</span>';
            dimHtml += '<span class="small fw-bold ' + (val >= 20 ? 'text-success' : (val >= 15 ? 'text-warning' : 'text-danger')) + '">' + val + '/25</span>';
            dimHtml += '</div>';
            dimHtml += '<div class="progress mb-1" style="height:3px;"><div class="progress-bar ' + dBarClass + '" style="width:' + (val * 4) + '%"></div></div>';
        }
    }
    $('#geoScoreDimensions').html(dimHtml);

    var sugHtml = '';
    var suggestions = data.suggestions || [];
    if (suggestions.length > 0) {
        sugHtml = '<div class="small text-muted mt-1"><i class="bi bi-lightbulb me-1"></i>优化建议：<ul class="mb-0 ps-3">';
        suggestions.forEach(function(s) {
            sugHtml += '<li>' + s + '</li>';
        });
        sugHtml += '</ul></div>';
    } else if (total >= 80) {
        sugHtml = '<div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i>AI友好度优秀！</div>';
    }
    $('#geoScoreSuggestions').html(sugHtml);
    $('#geoScoreEmpty').hide();
    $('#geoScoreArea').show();
}

// 保存按钮绑定
var saveBtn = document.getElementById('saveBtn');
if (saveBtn) {
    saveBtn.onclick = function() {
        if (typeof tinymce !== 'undefined') tinymce.triggerSave();
        var b = this;
        b.disabled = true;
        b.innerHTML = '保存中...';
        var form = document.getElementById('contentForm');
        window.ajaxPost(form.getAttribute('action'), $(form).serialize(), function(res) {
            b.disabled = false;
            b.innerHTML = '<i class="bi bi-check-lg me-1"></i>保存';
            if (res.code === 0) {
                window.showToast('保存成功', 'success');
                if (typeof window.doPjax === 'function') {
                    setTimeout(function() { window.doPjax(window.location.href); }, 600);
                } else {
                    setTimeout(function() { location.reload(); }, 600);
                }
            } else {
                window.showToast(res.msg || '保存失败', 'danger');
            }
        });
    };
}