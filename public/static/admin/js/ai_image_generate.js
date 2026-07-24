/**
 * AI智能配图交互JS — V2.9.30 AI2-3
 * 通过JS动态加载到内容编辑页
 */
$(function() {
    var contentId = $('#content-id').val() || window.location.pathname.match(/\/(\d+)$/);
    if (!contentId) return;
    contentId = typeof contentId === 'object' ? contentId[1] : contentId;

    // 在编辑器工具栏区域添加AI配图按钮
    var aiImageBtn = '<button type="button" class="btn btn-sm btn-outline-info ms-2" id="btn-ai-image" data-bs-toggle="modal" data-bs-target="#aiImageModal"><i class="bi bi-image"></i> AI配图</button>';
    $('.editor-toolbar, .tox-toolbar__group').first().after(aiImageBtn);

    // 添加配图弹窗
    var modalHtml = '<div class="modal fade" id="aiImageModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">';
    modalHtml += '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-image me-1"></i>AI智能配图</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>';
    modalHtml += '<div class="modal-body">';
    modalHtml += '<div class="mb-3"><label class="form-label">图片风格</label><select class="form-select" id="ai-image-style">';
    modalHtml += '<option value="auto">自动匹配</option><option value="tech">科技感</option><option value="business">商务风</option>';
    modalHtml += '<option value="nature">自然风</option><option value="creative">创意风</option></select></div>';
    modalHtml += '<div class="mb-3"><label class="form-label">图片尺寸</label><select class="form-select" id="ai-image-size">';
    modalHtml += '<option value="16:9">横图 16:9</option><option value="1:1">方形 1:1</option><option value="9:16">竖图 9:16</option></select></div>';
    modalHtml += '<div id="ai-image-result" class="text-center"></div>';
    modalHtml += '</div>';
    modalHtml += '<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>';
    modalHtml += '<button class="btn btn-primary" id="btn-generate-image"><i class="bi bi-magic"></i> 生成配图</button>';
    modalHtml += '<button class="btn btn-success d-none" id="btn-insert-image"><i class="bi bi-check-lg"></i> 插入编辑器</button></div>';
    modalHtml += '</div></div></div>';

    if ($('#aiImageModal').length === 0) {
        $('body').append(modalHtml);
    }

    $('#btn-generate-image').on('click', function() {
        var style = $('#ai-image-style').val();
        var size = $('#ai-image-size').val();
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> 生成中...';

        $.post('/admin/content/ai_generate_image/' + contentId, { style: style, size: size }, function(res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-magic"></i> 生成配图';
            if (res.code === 0) {
                var imageUrl = res.data.image_url;
                $('#ai-image-result').html('<img src="' + imageUrl + '" class="img-fluid rounded" style="max-height:300px"><p class="text-muted small mt-1">' + res.data.prompt + '</p>');
                $('#btn-insert-image').removeClass('d-none').data('image-url', imageUrl);
                window.ui.toast('配图生成成功', 'success');
            } else {
                window.ui.toast(res.msg || '生成失败', 'danger');
            }
        }).fail(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-magic"></i> 生成配图';
            window.ui.toast('请求失败', 'danger');
        });
    });

    $('#btn-insert-image').on('click', function() {
        var imageUrl = $(this).data('image-url');
        // 尝试多种编辑器的插入方式
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
            tinymce.activeEditor.insertContent('<img src="' + imageUrl + '" class="img-fluid" alt="">');
        } else if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.content) {
            CKEDITOR.instances.content.insertHtml('<img src="' + imageUrl + '" class="img-fluid" alt="">');
        } else {
            // 降级：追加到textarea
            var textarea = $('textarea[name="content"]');
            textarea.val(textarea.val() + '\n<img src="' + imageUrl + '" class="img-fluid" alt="">\n');
        }
        window.ui.toast('图片已插入编辑器', 'success');
        $('#aiImageModal').modal('hide');
    });
});
