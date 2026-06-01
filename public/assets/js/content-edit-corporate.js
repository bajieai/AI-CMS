// 全局CSRF Token自动刷新：当服务端返回新Token时自动更新表单字段
$(document).ajaxSuccess(function(event, xhr, settings) {
    var res = xhr.responseJSON;
    if (res && res.code === 403 && res.data && res.data.token) {
        $('input[name="__token__"]').val(res.data.token);
    }
});

$('#is_paid').on('change', function() { $('#pay-config-group').toggle($(this).is(':checked')); });

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
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image table | code fullscreen | aiimage aiseo aistyle',
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
        // V2.9.13: TinyMCE工具栏AI按钮
        editor.ui.registry.addButton('aiimage', {
            icon: 'image',
            tooltip: 'AI配图',
            onAction: function() { if (window.AiImage) AiImage.generate(); }
        });
        editor.ui.registry.addButton('aiseo', {
            icon: 'preview',
            tooltip: 'AI SEO对比',
            onAction: function() { if (window.AiSeo) AiSeo.open(); }
        });
        editor.ui.registry.addButton('aistyle', {
            icon: 'spell-check',
            tooltip: 'AI写作风格',
            onAction: function() { var m = document.getElementById('ai-style-modal'); if (m) m.classList.add('show'); }
        });
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
var AI_CMS_CONFIG = window.AI_CMS_CONFIG || {};
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
    var ajaxOpts = {
        url: '/api/ai/generate',
        type: 'POST',
        data: { prompt: prompt, template: 'continue' },
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

// ================== V2.7 章节管理 ==================
var parentId = AI_CMS_CONFIG.info_id || 0;
var chapterList = [];

function loadChapters() {
    if (!parentId) return;
    $.get('/admin/content/getChapters/' + parentId, function(res) {
        if (res.code === 0) {
            chapterList = res.data.list || [];
            renderChapterList();
        }
    }, 'json');
}

function renderChapterList() {
    var $box = $('#chapterListBox');
    if (!chapterList.length) {
        $box.html('<div class="text-muted small text-center py-2">暂无章节，点击上方按钮添加</div>');
        return;
    }
    var html = '<div class="list-group list-group-flush" id="chapterSortable">';
    for (var i = 0; i < chapterList.length; i++) {
        var ch = chapterList[i];
        html += '<div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-center" data-id="' + ch.id + '">';
        html += '<div class="d-flex align-items-center" style="cursor:move"><i class="bi bi-grip-vertical text-muted me-2"></i>';
        html += '<div><div class="small fw-bold">' + (ch.chapter_title || ch.title) + '</div>';
        html += '<div class="small text-muted">' + (ch.is_free_chapter ? '<span class="badge bg-success">免费</span>' : '<span class="badge bg-primary">付费</span>');
        if (ch.chapter_price > 0) html += ' ' + ch.chapter_price + '积分';
        html += '</div></div></div>';
        html += '<div class="btn-group btn-group-sm">';
        html += '<button class="btn btn-outline-secondary" onclick="editChapter(' + ch.id + ')"><i class="bi bi-pencil"></i></button>';
        html += '<button class="btn btn-outline-danger" onclick="delChapter(' + ch.id + ')"><i class="bi bi-trash"></i></button>';
        html += '</div></div>';
    }
    html += '</div>';
    $box.html(html);
    var dragSrcEl = null;
    $('#chapterSortable .list-group-item').each(function() {
        $(this).attr('draggable', true);
        $(this).on('dragstart', function(e) { dragSrcEl = this; e.originalEvent.dataTransfer.effectAllowed = 'move'; });
        $(this).on('dragover', function(e) { e.preventDefault(); e.originalEvent.dataTransfer.dropEffect = 'move'; return false; });
        $(this).on('drop', function(e) {
            e.stopPropagation();
            if (dragSrcEl !== this) {
                $(this).before(dragSrcEl);
                saveChapterSort();
            }
            return false;
        });
    });
}

function saveChapterSort() {
    var orders = [];
    $('#chapterSortable .list-group-item').each(function(idx) {
        orders.push({id: $(this).data('id'), sort: idx});
    });
    $.post('/admin/content/sortChapters', {orders: orders}, function(res) {
        if (res.code !== 0) showToast(res.msg, 'danger');
    }, 'json');
}

function openChapterModal() {
    $('#chapterModal').find('input,textarea').val('');
    $('#chapterModal [name="chapter_id"]').val(0);
    $('#chapterModal [name="is_free_chapter"]').prop('checked', false);
    $('#chapterModal [name="chapter_price"]').val(0);
    bootstrap.Modal.getInstance(document.getElementById('chapterModal')) || new bootstrap.Modal(document.getElementById('chapterModal'));
    bootstrap.Modal.getInstance(document.getElementById('chapterModal')).show();
}

function editChapter(id) {
    var ch = chapterList.find(function(c){return c.id == id;});
    if (!ch) return;
    $('#chapterModal [name="chapter_id"]').val(ch.id);
    $('#chapterModal [name="chapter_title"]').val(ch.chapter_title || ch.title);
    $('#chapterModal [name="chapter_content"]').val(ch.content || '');
    $('#chapterModal [name="chapter_sort"]').val(ch.chapter_sort || 0);
    $('#chapterModal [name="is_free_chapter"]').prop('checked', ch.is_free_chapter == 1);
    $('#chapterModal [name="chapter_price"]').val(ch.chapter_price || 0);
    var modal = bootstrap.Modal.getInstance(document.getElementById('chapterModal')) || new bootstrap.Modal(document.getElementById('chapterModal'));
    modal.show();
}

function saveChapter() {
    var data = {
        id: parseInt($('#chapterModal [name="chapter_id"]').val()) || 0,
        parent_id: parentId,
        title: $('#chapterModal [name="chapter_title"]').val(),
        chapter_title: $('#chapterModal [name="chapter_title"]').val(),
        content: $('#chapterModal [name="chapter_content"]').val(),
        chapter_sort: parseInt($('#chapterModal [name="chapter_sort"]').val()) || 0,
        is_free_chapter: $('#chapterModal [name="is_free_chapter"]').is(':checked') ? 1 : 0,
        chapter_price: parseFloat($('#chapterModal [name="chapter_price"]').val()) || 0,
    };
    if (!data.title) { showToast('请输入章节标题', 'warning'); return; }
    $.post('/admin/content/saveChapter', data, function(res) {
        if (res.code === 0) {
            showToast('保存成功', 'success');
            bootstrap.Modal.getInstance(document.getElementById('chapterModal')).hide();
            loadChapters();
        } else {
            showToast(res.msg, 'danger');
        }
    }, 'json');
}

function delChapter(id) {
    if (!confirm('确定删除该章节？')) return;
    $.post('/admin/content/deleteChapter/' + id, function(res) {
        if (res.code === 0) { showToast('删除成功', 'success'); loadChapters(); }
        else { showToast(res.msg, 'danger'); }
    }, 'json');
}

// V2.8 AI配图 / V2.9.9-R4增强: 支持尺寸选择
function aiGenerateCover() {
    var title = $('[name="title"]').val();
    if(!title){ showToast('请先填写标题','warning'); return; }
    var size = $('#aiImageSize').val() || '1024x1024';
    var csrfToken = $('input[name="__token__"]').val();
    $.ajax({
        url: '/api/ai/image',
        type: 'POST',
        data: { prompt: title, style: 'realistic', count: 1, size: size },
        dataType: 'json',
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        success: function(res) {
            if(res.code === 0 && res.data && res.data.url) {
                $('#aiImagePreview').attr('src', res.data.url);
                $('#aiImageResult').show();
                showToast('AI配图生成成功', 'success');
            } else {
                showToast(res.msg || '配图生成失败', 'danger');
            }
        },
        error: function(){ showToast('网络错误', 'danger'); }
    });
}
function useAiImage() {
    var url = $('#aiImagePreview').attr('src');
    if(url) {
        $('#coverInput').val(url);
        $('#coverPreview img').attr('src', url);
        $('#coverPreview').show();
        $('#aiImageResult').hide();
        showToast('封面已应用', 'success');
    }
}

// V2.8 SEO一键优化
function seoOptimize() {
    var title = $('[name="title"]').val();
    var content = tinymce.activeEditor ? tinymce.activeEditor.getContent({format:'text'}) : '';
    if(!title || !content){ showToast('标题和内容不能为空','warning'); return; }
    var csrfToken = $('input[name="__token__"]').val();
    $.ajax({
        url: '/api/ai/seo',
        type: 'POST',
        data: { title: title, content: content },
        dataType: 'json',
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
        success: function(res) {
            if(res.code === 0 && res.data) {
                if(res.data.seo_title) $('#seoTitle').val(res.data.seo_title);
                if(res.data.seo_keywords) $('#seoKeywords').val(res.data.seo_keywords);
                if(res.data.seo_description) $('#seoDescription').val(res.data.seo_description);
                updateSeoPreview();
                showToast('SEO优化完成', 'success');
            } else {
                showToast(res.msg || 'SEO优化失败', 'danger');
            }
        },
        error: function(){ showToast('网络错误', 'danger'); }
    });
}
function updateSeoPreview() {
    var title = $('#seoTitle').val() || $('[name="title"]').val() || '标题预览';
    var desc = $('#seoDescription').val() || '描述预览...';
    var slug = ($('[name="title"]').val() || 'post').replace(/\s+/g,'-');
    $('#previewTitle').text(title + ' - ' + ($('#site_name').text() || '站点'));
    $('#previewSlug').text(slug);
    $('#previewDesc').text(desc);
    $('#seoPreview').show();
}
$('#seoTitle, #seoDescription').on('input', updateSeoPreview);

// V3.1: SEO评分
function calculateSeoScore() {
    var title = $('[name="title"]').val().trim();
    var content = tinymce.activeEditor ? tinymce.activeEditor.getContent() : '';
    var seoTitle = $('#seoTitle').val().trim();
    var seoDesc = $('#seoDescription').val().trim();
    var seoKeywords = $('#seoKeywords').val().trim();

    var $btn = $('#btnSeoScore');
    if ($btn.length) {
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>评分中...');
    }

    var csrfToken = $('input[name="__token__"]').val();
    var ajaxOpts = {
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
            if ($btn.length) {
                $btn.prop('disabled', false).html('<i class="bi bi-bar-chart me-1"></i>评分');
            }
            if (res.code === 0 && res.data) {
                renderSeoScore(res.data);
            } else {
                showToast(res.msg || '评分失败', 'danger');
            }
        },
        error: function() {
            if ($btn.length) {
                $btn.prop('disabled', false).html('<i class="bi bi-bar-chart me-1"></i>评分');
            }
            showToast('网络错误', 'danger');
        }
    };
    if (csrfToken) { ajaxOpts.headers = { 'X-CSRF-TOKEN': csrfToken }; }
    $.ajax(ajaxOpts);
}

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
            sugHtml += '<li>' + s + '</li>';
        });
        sugHtml += '</ul>';
    } else {
        sugHtml = '<div class="small text-success"><i class="bi bi-check-circle me-1"></i>SEO表现优秀！</div>';
    }
    $('#seoScoreSuggestions').html(sugHtml);
    $('#seoScoreArea').show();
}

loadChapters();

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
            }, 1000);
        });
    }
}, 2500);

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
        data: { content: content.substring(0, 3000) },
        dataType: 'json',
        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
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
    
    var score = data.overall_score || 0;
    var scoreClass = score >= 80 ? 'text-success' : (score >= 60 ? 'text-warning' : 'text-danger');
    html += '<div class="text-center mb-4">';
    html += '<h1 class="' + scoreClass + '">' + score + '分</h1>';
    html += '<p class="text-muted">综合质量评分（满分100）</p>';
    html += '</div>';
    
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
    
    if (data.suggestions && data.suggestions.length > 0) {
        html += '<div class="alert alert-info">';
        html += '<h6><i class="bi bi-lightbulb me-1"></i>改进建议</h6>';
        html += '<ul class="mb-0 ps-3">';
        data.suggestions.forEach(function(s) {
            html += '<li>' + escapeHtml(s) + '</li>';
        });
        html += '</ul>';
        html += '</div>';
    }
    
    html += '</div>';
    html += '<div class="modal-footer">';
    html += '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">关闭</button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    $('#qualityModal').remove();
    $('body').append(html);
    var modal = new bootstrap.Modal(document.getElementById('qualityModal'));
    modal.show();
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
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