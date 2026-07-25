/**
 * 操作反馈JS — V2.9.30 UX-3
 * 统一loading态和Toast提示
 */
$(function() {
    // 所有表单提交时增加loading态
    $('form').on('submit', function() {
        var $btn = $(this).find('button[type="submit"]');
        if ($btn.length && !$btn.hasClass('no-loading')) {
            var originalText = $btn.html();
            $btn.data('original-text', originalText);
            $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat"></i> 处理中...');
            setTimeout(function() {
                $btn.prop('disabled', false).html(originalText);
            }, 10000);
        }
    });

    // AJAX全局loading
    $(document).on('ajaxStart', function() {
        $('#global-loading').removeClass('d-none');
    }).on('ajaxStop', function() {
        $('#global-loading').addClass('d-none');
    });

    // 空状态引导
    $('table tbody').each(function() {
        if ($(this).find('tr').length === 0 && !$(this).find('.empty-state').length) {
            var colspan = $(this).closest('table').find('th').length;
            $(this).html('<tr><td colspan="' + colspan + '" class="text-center py-5"><i class="bi bi-inbox fs-1 text-muted"></i><p class="text-muted mt-2">暂无数据</p><a href="javascript:history.back()" class="btn btn-sm btn-outline-primary">返回</a></td></tr>');
        }
    });
});
