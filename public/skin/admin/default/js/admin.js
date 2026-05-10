/**
 * AI-CMS 后台管理核心脚本 - V2.9.2
 * 主题: default / corporate (通用)
 * 依赖: jQuery, Bootstrap 5.3, PJAX
 *
 * 功能: 页面加载进度条 / CSRF自动注入 / AJAX封装 / Toast提示 / 确认弹窗 / 清除缓存 / 通知轮询
 */
(function () {
    'use strict';

    // ==================== 页面加载进度条 ====================
    var $loader = $('#pageLoader');
    var $bar = $loader.find('div');

    window.showPageLoader = function () {
        $bar.css('width', '30%');
        $loader.css('opacity', '1');
        setTimeout(function () { $bar.css('width', '70%'); }, 100);
    };
    window.hidePageLoader = function () {
        $bar.css('width', '100%');
        setTimeout(function () { $loader.css('opacity', '0'); $bar.css('width', '0%'); }, 300);
    };
    $(window).on('load', hidePageLoader);

    // 非PJAX链接点击时显示进度条
    $(document).on('click', '.breadcrumb a, .dropdown-menu a', function (e) {
        var href = $(this).attr('href');
        if (href && href.indexOf('#') !== 0 && !$(this).attr('target')) {
            showPageLoader();
        }
    });

    // ==================== CSRF Token 自动管理 ====================
    // 表单注入
    var csrfToken = $('input[name="__token__"]').val();
    if (csrfToken) {
        $('form').each(function () {
            if (!$(this).find('input[name="__token__"]').length) {
                $(this).append('<input type="hidden" name="__token__" value="' + csrfToken + '">');
            }
        });
    }

    // AJAX POST 自动注入CSRF Header
    $(document).ajaxSend(function (event, jqXHR, settings) {
        var token = $('input[name="__token__"]').val();
        if (token && settings.type && settings.type.toUpperCase() === 'POST') {
            jqXHR.setRequestHeader('X-CSRF-TOKEN', token);
        }
    });

    // CSRF Token 过期自动恢复 (HTTP 419)
    $(document).ajaxError(function (event, jqXHR, settings) {
        if (jqXHR.status === 419) {
            $.get('/api/csrf/token', function (res) {
                if (res.token) {
                    $('input[name="__token__"]').val(res.token);
                    if (settings.type && settings.type.toUpperCase() !== 'POST') {
                        $.ajax(settings);
                    } else {
                        alert('页面会话已过期，请刷新页面后重试');
                    }
                }
            });
        }
    });

    // ==================== 侧边栏切换（移动端） ====================
    $('#sidebarToggle').on('click', function () {
        $('#sidebarWrapper').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    });
    $('#sidebarOverlay').on('click', function () {
        $('#sidebarWrapper').removeClass('show');
        $(this).removeClass('show');
    });

    // ==================== 通用AJAX封装（含CSRF自动恢复） ====================
    window.ajaxPost = function (url, data, callback, _retry) {
        var csrfToken = $('input[name="__token__"]').val();
        var ajaxOptions = {
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res.code === 0) {
                    if (typeof callback === 'function') {
                        callback(res);
                    } else {
                        showToast(res.msg || '操作成功', 'success');
                        setTimeout(function () {
                            if (res.data && res.data.redirect) {
                                location.href = res.data.redirect;
                            } else {
                                location.reload();
                            }
                        }, 800);
                    }
                } else if (res.code === 403 && !_retry) {
                    refreshCsrfToken(function () {
                        ajaxPost(url, data, callback, true);
                    });
                } else {
                    showToast(res.msg || '操作失败', 'danger');
                }
            },
            error: function (xhr) {
                if (xhr.status === 403 && !_retry) {
                    refreshCsrfToken(function () {
                        ajaxPost(url, data, callback, true);
                    });
                } else if (xhr.status === 403) {
                    showToast('CSRF验证失败，请刷新页面后重试', 'danger');
                } else {
                    showToast('网络错误，请重试', 'danger');
                }
            }
        };
        if (csrfToken) {
            ajaxOptions.headers = { 'X-CSRF-TOKEN': csrfToken };
        }
        $.ajax(ajaxOptions);
    };

    // ==================== CSRF Token 自动刷新 ====================
    window.refreshCsrfToken = function (callback) {
        $.get('/api/csrf/token', function (res) {
            if (res.code === 0 && res.data && res.data.token) {
                $('input[name="__token__"]').val(res.data.token);
                if (typeof callback === 'function') callback();
            } else {
                showToast('会话已过期，页面将刷新', 'warning');
                setTimeout(function () { location.reload(); }, 1500);
            }
        }).fail(function () {
            showToast('会话已过期，页面将刷新', 'warning');
            setTimeout(function () { location.reload(); }, 1500);
        });
    };

    // ==================== Toast 提示 ====================
    window.showToast = function (msg, type) {
        type = type || 'success';
        var html = '<div class="toast align-items-center text-bg-' + type + ' border-0 position-fixed top-0 end-0 m-3" role="alert" style="z-index:9999">' +
            '<div class="d-flex"><div class="toast-body">' + msg + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
        $('body').append(html);
        var $toast = $('body .toast').last();
        var toast = new bootstrap.Toast($toast[0], { delay: 2000 });
        toast.show();
        $toast.on('hidden.bs.toast', function () { $(this).remove(); });
    };
    $.toast = window.showToast;

    // ==================== 确认删除 ====================
    window.confirmDelete = function (url) {
        showConfirm('确定要删除吗？', '此操作不可恢复！', function () {
            ajaxPost(url, {});
        });
    };

    // ==================== 通用确认弹窗 ====================
    window.showConfirm = function (title, message, onConfirm) {
        var modalId = 'confirmModal' + Date.now();
        var html = '<div class="modal fade" id="' + modalId + '" tabindex="-1">' +
            '<div class="modal-dialog modal-dialog-centered">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">' + title + '</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body"><p>' + message + '</p></div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">取消</button>' +
            '<button type="button" class="btn btn-danger btn-sm" id="' + modalId + '_ok">确定</button>' +
            '</div></div></div></div>';
        $('body').append(html);
        var $modal = $('#' + modalId);
        var modal = new bootstrap.Modal($modal[0]);
        $modal.find('#' + modalId + '_ok').on('click', function () {
            modal.hide();
            if (typeof onConfirm === 'function') onConfirm();
        });
        $modal.on('hidden.bs.modal', function () { $modal.remove(); });
        modal.show();
    };

    // ==================== 清除全站缓存 ====================
    window.clearCache = function () {
        if (!confirm('确定要清除全部缓存吗？')) return;
        ajaxPost('/api/cache/clear', {});
    };

    // ==================== 通知轮询（60秒间隔） ====================
    (function pollNotification() {
        function fetchUnread() {
            $.ajax({
                url: '/admin/notification/index',
                type: 'GET',
                data: { is_read: 0, ajax: 1 },
                dataType: 'json',
                success: function (res) {
                    if (res.code === 0 && res.data && res.data.count !== undefined) {
                        var count = parseInt(res.data.count, 10);
                        var $badge = $('#notificationBadge');
                        if (count > 0) {
                            $badge.text(count > 99 ? '99+' : count).removeClass('d-none');
                        } else {
                            $badge.addClass('d-none');
                        }
                    }
                },
                error: function () { /* 静默失败 */ }
            });
        }
        fetchUnread();
        setInterval(fetchUnread, 60000);
    })();
})();
