/**
 * V2.9.21 D-5: 模板安装交互增强
 * 功能：安装进度动画、错误重试、状态提示
 */
(function() {
    'use strict';

    window.TemplateInstall = {
        /**
         * 执行模板安装（带进度动画）
         * @param {number} templateId 模板ID
         * @param {HTMLElement} btn 触发按钮（可选，用于显示加载状态）
         */
        doInstall: function(templateId, btn) {
            if (!templateId) return;

            var $btn = btn ? $(btn) : null;
            if ($btn) {
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>安装中...');
            }

            $.post('/admin/template_install/doInstall/' + templateId, function(res) {
                if (res.code === 0) {
                    TemplateInstall.showToast('安装成功', 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    TemplateInstall.showToast(res.msg || '安装失败', 'error');
                    if ($btn) {
                        $btn.prop('disabled', false).html('<i class="bi bi-download me-1"></i>重新安装');
                    }
                }
            }).fail(function() {
                TemplateInstall.showToast('网络错误，请重试', 'error');
                if ($btn) {
                    $btn.prop('disabled', false).html('<i class="bi bi-download me-1"></i>安装');
                }
            });
        },

        /**
         * 执行模板卸载
         * @param {number} templateId 模板ID
         * @param {string} templateName 模板名称（用于确认提示）
         */
        doUninstall: function(templateId, templateName) {
            if (!confirm('确定卸载模板「' + (templateName || '') + '」？此操作不可恢复。')) return;

            $.post('/admin/template_install/doUninstall/' + templateId, function(res) {
                TemplateInstall.showToast(res.msg, res.code === 0 ? 'success' : 'error');
                if (res.code === 0) setTimeout(function() { location.reload(); }, 800);
            });
        },

        /**
         * 执行模板激活
         * @param {number} templateId 模板ID
         */
        doActivate: function(templateId) {
            $.post('/admin/template_install/doActivate/' + templateId, function(res) {
                TemplateInstall.showToast(res.msg, res.code === 0 ? 'success' : 'error');
                if (res.code === 0) setTimeout(function() { location.reload(); }, 800);
            });
        },

        /**
         * 显示 Toast 提示
         * @param {string} msg 消息内容
         * @param {string} type 类型：success | error | warning | info
         */
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
            var toast = new bootstrap.Toast($toast[0], { delay: 3000 });
            toast.show();
            $toast.on('hidden.bs.toast', function() { $(this).remove(); });
        }
    };
})();
