/* AI-CMS 在线升级页面脚本 - V2.9.44 */

(function () {
    'use strict';

    var latestInfo = null;
    var currentLogId = 0;
    var pollTimer = null;

    function initPage() {
        checkVersion();

        $(document).on('click', '#btnRefreshCheck', function () {
            clearLatestCacheAndCheck();
        });

        $(document).on('click', '#btnCheckEnv', function () {
            checkEnvironment();
        });

        $(document).on('click', '#btnStartUpgrade', function () {
            startUpgrade();
        });
    }

    function checkVersion() {
        $('#versionInfo').html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status"></div>' +
            '<p class="text-muted mt-2 mb-0">正在检查最新版本...</p></div>'
        );
        $('#upgradeAction').addClass('d-none');

        $.ajax({
            url: '/admin/online_upgrade/check',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.code === 0 && res.data) {
                    latestInfo = res.data;
                    renderVersionInfo(res.data);
                    $('#upgradeAction').removeClass('d-none');
                } else {
                    $('#versionInfo').html(
                        '<div class="alert alert-warning mb-0">' +
                        '<i class="bi bi-exclamation-triangle me-2"></i>' + escapeHtml(res.msg || '检查失败') +
                        '</div>'
                    );
                    $('#upgradeAction').removeClass('d-none');
                }
            },
            error: function (xhr) {
                $('#versionInfo').html(
                    '<div class="alert alert-danger mb-0">' +
                    '<i class="bi bi-x-circle me-2"></i>请求失败: ' + xhr.statusText +
                    '</div>'
                );
                $('#upgradeAction').removeClass('d-none');
            }
        });
    }

    function clearLatestCacheAndCheck() {
        $.ajax({
            url: '/admin/online_upgrade/clearCache',
            type: 'POST',
            dataType: 'json',
            success: function () {
                checkVersion();
            },
            error: function () {
                checkVersion();
            }
        });
    }

    function renderVersionInfo(data) {
        var html = '';
        if (data.has_update) {
            html += '<div class="alert alert-info mb-0">';
            html += '<div class="d-flex align-items-center mb-2">';
            html += '<i class="bi bi-info-circle-fill fs-4 me-3 text-primary"></i>';
            html += '<div><h5 class="mb-1">发现新版本 V' + escapeHtml(data.latest_version) + '</h5>';
            html += '<p class="mb-0 text-muted">当前版本 V' + escapeHtml(data.current_version) + '，建议立即升级</p></div>';
            html += '</div>';
            if (data.published_at) {
                html += '<p class="small text-muted mb-2">发布时间: ' + escapeHtml(data.published_at) + '</p>';
            }
            if (data.body) {
                html += '<div class="changelog-body">' + nl2br(escapeHtml(data.body)) + '</div>';
            }
            html += '</div>';
        } else {
            html += '<div class="alert alert-success mb-0">';
            html += '<i class="bi bi-check-circle-fill me-2"></i>';
            html += '当前已是最新版本 V' + escapeHtml(data.current_version);
            html += '</div>';
        }
        $('#versionInfo').html(html);
    }

    function checkEnvironment() {
        var $btn = $('#btnCheckEnv');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>检查中...');

        $.ajax({
            url: '/admin/online_upgrade/environment',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                $btn.prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i>环境检查');
                renderEnvResult(res);
                if (res.code === 0 && latestInfo && latestInfo.has_update) {
                    $('#btnStartUpgrade').removeClass('d-none');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-shield-check me-1"></i>环境检查');
                showToast('环境检查请求失败: ' + xhr.statusText, 'danger');
            }
        });
    }

    function renderEnvResult(res) {
        var $box = $('#envResult').removeClass('d-none');
        var html = '';
        if (res.code === 0) {
            html += '<div class="alert alert-success mb-0">';
            html += '<i class="bi bi-check-circle-fill me-2"></i>环境检查通过，可以升级';
            if (res.data && res.data.warnings && res.data.warnings.length > 0) {
                html += '<hr><ul class="mb-0 ps-3">';
                $.each(res.data.warnings, function (i, w) {
                    html += '<li>' + escapeHtml(w) + '</li>';
                });
                html += '</ul>';
            }
            html += '</div>';
        } else {
            html += '<div class="alert alert-danger mb-0">';
            html += '<i class="bi bi-x-circle-fill me-2"></i>环境检查未通过，请先修复以下问题：';
            html += '<ul class="mb-0 ps-3 mt-2">';
            $.each(res.data.errors || [], function (i, e) {
                html += '<li>' + escapeHtml(e) + '</li>';
            });
            html += '</ul></div>';
        }
        $box.html(html);
    }

    function startUpgrade() {
        if (!latestInfo || !latestInfo.has_update) {
            showToast('暂无可升级版本', 'warning');
            return;
        }
        if (!latestInfo.download_url) {
            showToast('未找到升级包下载地址', 'danger');
            return;
        }

        if (!confirm('确定要升级到 V' + latestInfo.latest_version + ' 吗？\n升级过程可能需要几分钟，请不要关闭页面。')) {
            return;
        }

        $('#progressCard').show();
        $('#upgradeSteps').empty();
        $('#upgradeResult').empty();
        updateProgressBar(0);

        // 禁用操作按钮
        $('#btnStartUpgrade, #btnCheckEnv, #btnRefreshCheck').prop('disabled', true);

        $.ajax({
            url: '/admin/online_upgrade/execute',
            type: 'POST',
            dataType: 'json',
            data: {
                download_url: latestInfo.download_url,
                to_version: latestInfo.latest_version
            },
            timeout: 0,
            success: function (res) {
                if (res.code === 0 && res.data && res.data.log_id) {
                    currentLogId = res.data.log_id;
                    pollProgress(currentLogId, true);
                } else {
                    $('#upgradeResult').html('<div class="alert alert-danger">' + escapeHtml(res.msg || '升级失败') + '</div>');
                    enableActionButtons();
                }
            },
            error: function (xhr, status, error) {
                $('#upgradeResult').html('<div class="alert alert-danger">请求异常: ' + escapeHtml(error || status) + '</div>');
                enableActionButtons();
            }
        });
    }

    function pollProgress(logId, isFinal) {
        if (pollTimer) {
            clearTimeout(pollTimer);
        }

        $.ajax({
            url: '/admin/online_upgrade/progress?log_id=' + logId,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.code === 0 && res.data) {
                    renderSteps(res.data.steps || []);
                    if (res.data.status === 1) {
                        updateProgressBar(100);
                        $('#upgradeResult').html('<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>升级成功！请刷新页面查看最新版本。</div>');
                        enableActionButtons();
                    } else if (res.data.status === 2) {
                        $('#upgradeResult').html('<div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i>' + escapeHtml(res.data.error_message || '升级失败') + '</div>');
                        enableActionButtons();
                    } else if (res.data.status === 3) {
                        $('#upgradeResult').html('<div class="alert alert-warning"><i class="bi bi-arrow-counterclockwise me-2"></i>已回滚到升级前状态。</div>');
                        enableActionButtons();
                    } else {
                        pollTimer = setTimeout(function () {
                            pollProgress(logId);
                        }, 2000);
                    }
                } else {
                    pollTimer = setTimeout(function () {
                        pollProgress(logId);
                    }, 3000);
                }
            },
            error: function () {
                pollTimer = setTimeout(function () {
                    pollProgress(logId);
                }, 5000);
            }
        });
    }

    function renderSteps(steps) {
        var html = '';
        var doneCount = 0;
        $.each(steps, function (i, step) {
            var iconClass = getStepIconClass(step.status);
            var icon = getStepIcon(step.status);
            html += '<div class="upgrade-step">';
            html += '<div class="upgrade-step-icon ' + step.status + '"><i class="bi ' + icon + '"></i></div>';
            html += '<div class="upgrade-step-content">';
            html += '<div class="upgrade-step-title">' + escapeHtml(step.message || step.step) + '</div>';
            html += '<div class="upgrade-step-time">' + escapeHtml(step.time || '') + '</div>';
            html += '</div></div>';
            if (step.status === 'done' || step.status === 'skipped') {
                doneCount++;
            }
        });
        $('#upgradeSteps').html(html || '<div class="text-muted text-center py-3">等待开始...</div>');

        var total = steps.length || 1;
        var pct = Math.min(100, Math.round(doneCount / total * 100));
        updateProgressBar(pct);
    }

    function getStepIconClass(status) {
        return status || 'pending';
    }

    function getStepIcon(status) {
        switch (status) {
            case 'done': return 'bi-check-lg';
            case 'running': return 'bi-arrow-repeat spin';
            case 'failed': return 'bi-x-lg';
            case 'warning': return 'bi-exclamation-triangle';
            case 'skipped': return 'bi-skip-forward';
            default: return 'bi-circle';
        }
    }

    function updateProgressBar(pct) {
        $('#upgradeProgressBar').css('width', pct + '%').text(pct + '%');
    }

    function enableActionButtons() {
        $('#btnStartUpgrade, #btnCheckEnv, #btnRefreshCheck').prop('disabled', false);
    }

    window.rollbackUpgrade = function (logId) {
        if (!confirm('确定要回滚到该升级记录之前的状态吗？\n回滚会恢复数据库和文件，请谨慎操作。')) {
            return;
        }
        ajaxPost('/admin/online_upgrade/rollback', { log_id: logId }, function (res) {
            showToast(res.msg || '回滚成功', 'success');
            setTimeout(function () { location.reload(); }, 1200);
        });
    };

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function nl2br(str) {
        return String(str).replace(/\n/g, '<br>');
    }

    function showToast(msg, type) {
        if (typeof window.showToast === 'function' && window.showToast !== showToast) {
            window.showToast(msg, type);
            return;
        }
        alert(msg);
    }

    // 页面初始化（兼容 PJAX）
    if (typeof jQuery !== 'undefined') {
        if (document.readyState === 'loading') {
            $(document).ready(initPage);
        } else {
            initPage();
        }
        $(document).on('pjax:complete', initPage);
    }
})();
