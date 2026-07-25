/**
 * AI批量改写交互JS — V2.9.30 AI2-1
 */
$(function() {
    var selectedIds = [];

    // 内容列表多选
    $('.content-checkbox').on('change', function() {
        var id = parseInt(this.value);
        if (this.checked) {
            if (selectedIds.indexOf(id) === -1) selectedIds.push(id);
        } else {
            selectedIds = selectedIds.filter(function(v) { return v !== id; });
        }
        updateSelectionCount();
    });

    $('#select-all-content').on('change', function() {
        $('.content-checkbox').prop('checked', this.checked);
        if (this.checked) {
            selectedIds = $('.content-checkbox').map(function() { return parseInt(this.value); }).get();
        } else {
            selectedIds = [];
        }
        updateSelectionCount();
    });

    function updateSelectionCount() {
        $('#selection-count').text(selectedIds.length);
        $('#btn-batch-rewrite').prop('disabled', selectedIds.length === 0);
    }

    // 批量改写
    $('#btn-batch-rewrite').on('click', function() {
        if (selectedIds.length === 0) return;
        $('#aiRewriteModal').modal('show');
    });

    // 执行改写
    $('#btn-execute-rewrite').on('click', function() {
        var mode = $('#rewrite-mode').val();
        var intensity = $('#rewrite-intensity').val();
        var style = $('#rewrite-style').val();
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> 改写中...';

        $.post('/admin/content/ai_batch_rewrite', {
            content_ids: selectedIds,
            mode: mode,
            intensity: intensity,
            style: style
        }, function(res) {
            btn.disabled = false;
            btn.innerHTML = '开始改写';
            if (res.code === 0) {
                window.ui.toast(res.msg, 'success');
                renderRewriteResults(res.data);
            } else {
                window.ui.toast(res.msg, 'danger');
            }
        }).fail(function() {
            btn.disabled = false;
            btn.innerHTML = '开始改写';
            window.ui.toast('请求失败', 'danger');
        });
    });

    function renderRewriteResults(results) {
        var html = '';
        results.forEach(function(r) {
            if (r.success) {
                html += '<div class="card mb-2"><div class="card-body p-2">';
                html += '<div class="row"><div class="col-6"><small class="text-muted">原文</small><p class="small">' + escapeHtml(r.original) + '</p></div>';
                html += '<div class="col-6"><small class="text-muted">改写后</small><p class="small">' + escapeHtml(r.rewritten) + '</p></div></div>';
                html += '<button class="btn btn-sm btn-success btn-confirm" data-log-id="' + r.log_id + '">确认</button> ';
                html += '<button class="btn btn-sm btn-outline-secondary btn-discard" data-log-id="' + r.log_id + '">放弃</button>';
                html += '</div></div>';
            }
        });
        $('#rewrite-results').html(html);
    }

    $(document).on('click', '.btn-confirm', function() {
        var logId = $(this).data('log-id');
        $.post('/admin/content/ai_rewrite/confirm/' + logId, {}, function(res) {
            if (res.code === 0) {
                window.ui.toast('已确认', 'success');
            } else {
                window.ui.toast(res.msg, 'danger');
            }
        });
    });

    $(document).on('click', '.btn-discard', function() {
        var logId = $(this).data('log-id');
        $.post('/admin/content/ai_rewrite/discard/' + logId, {}, function(res) {
            if (res.code === 0) {
                window.ui.toast('已放弃', 'info');
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
