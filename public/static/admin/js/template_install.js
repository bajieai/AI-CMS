/**
 * V2.9.23 B-2: 模板安装交互增强（含步骤进度可视化）
 * 功能：安装进度动画、步骤提示、错误重试、状态提示
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
         * V2.9.23 B-2: 增强版安装（含步骤进度可视化）
         * @param {number} templateId 模板ID
         * @param {string} templateName 模板名称
         * @param {HTMLElement} btn 触发按钮
         */
        doInstallEnhanced: function(templateId, templateName, btn) {
            if (!templateId) return;

            var $btn = btn ? $(btn) : null;
            var steps = ['准备安装', '下载模板文件', '解压模板包', '注册到系统', '激活模板'];
            var $modal = TemplateInstall.showProgressModal(templateName, steps);

            if ($btn) {
                $btn.prop('disabled', true);
            }

            TemplateInstall.updateProgress($modal, 0, '准备安装...');

            setTimeout(function() {
                TemplateInstall.updateProgress($modal, 1, '下载模板文件...');

                $.post('/admin/template_install/doInstall/' + templateId, function(res) {
                    if (res.code === 0) {
                        TemplateInstall.updateProgress($modal, 2, '解压模板包...');
                        setTimeout(function() {
                            TemplateInstall.updateProgress($modal, 3, '注册到系统...');
                            setTimeout(function() {
                                TemplateInstall.updateProgress($modal, 4, '激活模板...');
                                setTimeout(function() {
                                    TemplateInstall.closeProgress($modal);
                                    TemplateInstall.showToast('模板「' + templateName + '」安装成功', 'success');
                                    setTimeout(function() { location.reload(); }, 800);
                                }, 500);
                            }, 500);
                        }, 500);
                    } else {
                        TemplateInstall.closeProgress($modal);
                        TemplateInstall.showToast(res.msg || '安装失败', 'error');
                        if ($btn) {
                            $btn.prop('disabled', false).html('<i class="bi bi-download me-1"></i>重新安装');
                        }
                    }
                }).fail(function() {
                    TemplateInstall.closeProgress($modal);
                    TemplateInstall.showToast('网络错误，请重试', 'error');
                    if ($btn) {
                        $btn.prop('disabled', false).html('<i class="bi bi-download me-1"></i>安装');
                    }
                });
            }, 300);
        },

        /**
         * 显示进度弹窗
         */
        showProgressModal: function(templateName, steps) {
            var html = '<div class="modal fade" id="installProgressModal" tabindex="-1" data-bs-backdrop="static">' +
                '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title"><i class="bi bi-download me-1"></i>安装「' + (templateName || '模板') + '」</h5>' +
                '</div>' +
                '<div class="modal-body">' +
                '<div class="progress mb-3" style="height:8px;">' +
                '<div class="progress-bar progress-bar-striped progress-bar-animated" id="installProgressBar" style="width:0%"></div>' +
                '</div>' +
                '<div id="installStepList">';

            for (var i = 0; i < steps.length; i++) {
                html += '<div class="d-flex align-items-center mb-2 step-item" data-step="' + i + '">' +
                    '<span class="badge bg-secondary me-2 step-badge">' + (i + 1) + '</span>' +
                    '<span class="step-text">' + steps[i] + '</span>' +
                    '</div>';
            }

            html += '</div></div></div></div></div>';

            $('body').append(html);
            var $modal = $('#installProgressModal');
            $modal.modal('show');
            return $modal;
        },

        /**
         * 更新进度
         */
        updateProgress: function($modal, stepIndex, message) {
            var totalSteps = $modal.find('.step-item').length;
            var percent = ((stepIndex + 1) / totalSteps) * 100;

            $modal.find('#installProgressBar').css('width', percent + '%');
            $modal.find('.step-item').each(function() {
                var idx = parseInt($(this).data('step'));
                var $badge = $(this).find('.step-badge');
                if (idx < stepIndex) {
                    $badge.removeClass('bg-secondary').addClass('bg-success');
                    $badge.html('<i class="bi bi-check-lg"></i>');
                } else if (idx === stepIndex) {
                    $badge.removeClass('bg-secondary').addClass('bg-primary');
                    $badge.html('<span class="spinner-border spinner-border-sm"></span>');
                }
            });
        },

        /**
         * 关闭进度弹窗
         */
        closeProgress: function($modal) {
            $modal.modal('hide');
            setTimeout(function() { $modal.remove(); }, 300);
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
