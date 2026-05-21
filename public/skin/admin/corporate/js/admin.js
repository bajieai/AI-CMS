/**
 * AI-CMS 后台管理核心脚本 - V2.9.4
 * 主题: default / corporate (通用)
 * 依赖: jQuery, Bootstrap 5.3, PJAX
 *
 * 功能: 页面加载进度条 / CSRF自动注入 / AJAX封装 / Toast提示 / 确认弹窗 / 清除缓存 / 通知轮询 / Loading遮罩
 */
(function () {
    'use strict';

    // ==================== 页面加载进度条 ====================
    var $loader = $('#pageLoader');
    var $bar = $loader.find('div');

    window.showPageLoader = function () {
        $bar.css({'width': '0%', 'transition': 'none'});
        $loader.css('opacity', '1');
        // 强制重排确保0%生效，然后从0%平滑动画到70%，动画时间延长减少"卡顿"感知
        $bar[0].offsetHeight;
        $bar.css({'width': '70%', 'transition': 'width .8s ease-out'});
    };
    window.hidePageLoader = function () {
        $bar.css({'width': '100%', 'transition': 'width .15s linear'});
        setTimeout(function () {
            $loader.css('opacity', '0');
            setTimeout(function () {
                $bar.css({'width': '0%', 'transition': 'none'});
            }, 200);
        }, 200);
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
                        showToast('页面会话已过期，请刷新页面后重试', 'warning');
                    }
                }
            });
        }
    });

    // ==================== 侧边栏切换（移动端）====================
    $('#sidebarToggle').on('click', function () {
        $('#sidebarWrapper').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    });
    $('#sidebarOverlay').on('click', function () {
        $('#sidebarWrapper').removeClass('show');
        $(this).removeClass('show');
    });

    // ==================== 通用AJAX封装（含CSRF自动恢复 + Loading）====================
    window.ajaxPost = function (url, data, callback, _retry) {
        var csrfToken = $('input[name="__token__"]').val();
        var loadingTimer = setTimeout(function () { showLoading('处理中...'); }, 300);
        var ajaxOptions = {
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                clearTimeout(loadingTimer);
                hideLoading();
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
                clearTimeout(loadingTimer);
                hideLoading();
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

    // ==================== Toast 提示 (V2.9.4 增强版) ====================
    var toastIcons = {
        success: 'bi-check-circle-fill',
        danger: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill',
        loading: 'bi-arrow-repeat spin'
    };
    var toastStackOffset = 0;
    window.showToast = function (msg, type, duration) {
        type = type || 'success';
        duration = duration || (type === 'loading' ? 0 : 2500);
        var icon = toastIcons[type] || toastIcons.info;
        var id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        var style = 'z-index:9999;transform:translateY(' + toastStackOffset + 'px);';
        var html = '<div class="toast ui-toast align-items-center border-0 position-fixed top-0 end-0 m-3" role="alert" id="' + id + '" style="' + style + '">' +
            '<div class="d-flex align-items-center"><i class="bi ' + icon + ' fs-5 me-2"></i>' +
            '<div class="toast-body">' + msg + '</div>' +
            (duration !== 0 ? '<button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast"></button>' : '') +
            '</div></div>';
        $('body').append(html);
        var $toast = $('#' + id);
        var toast = new bootstrap.Toast($toast[0], { delay: duration || 2500, autohide: duration !== 0 });
        toast.show();
        // 堆叠偏移：计算已有toast的高度
        toastStackOffset += $toast.outerHeight() + 12;
        $toast.on('hidden.bs.toast', function () {
            toastStackOffset -= ($(this).outerHeight() + 12);
            if (toastStackOffset < 0) toastStackOffset = 0;
            $(this).remove();
        });
        return { el: $toast, hide: function () { toast.hide(); } };
    };
    $.toast = window.showToast;

    // ==================== Loading 遮罩 (V2.9.4) ====================
    var $loadingOverlay = null;
    window.showLoading = function (text) {
        text = text || '加载中...';
        if ($loadingOverlay) $loadingOverlay.remove();
        var html = '<div id="uiLoadingOverlay" class="ui-loading-overlay">' +
            '<div class="ui-loading-box">' +
            '<div class="spinner-border text-primary mb-2" role="status"></div>' +
            '<div class="ui-loading-text">' + text + '</div>' +
            '</div></div>';
        $('body').append(html);
        $loadingOverlay = $('#uiLoadingOverlay');
    };
    window.hideLoading = function () {
        if ($loadingOverlay) {
            $loadingOverlay.fadeOut(200, function () { $(this).remove(); });
            $loadingOverlay = null;
        }
    };

    // ==================== 快捷确认弹窗（替代原生alert）====================
    window.showAlert = function (msg, type) {
        type = type || 'info';
        showToast(msg, type, 3000);
    };

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

    // ==================== 清除缓存（支持分类清除）====================
    // 一键清除全部缓存
    window.clearCache = function () {
        showConfirm('一键清除缓存', '确定要清除全部缓存吗？清除后所有类型的缓存都将重建。', function () {
            ajaxPost('/api/cache/clear', {});
        });
    };

    // 按类型清除缓存
    window.clearCacheByType = function (type, label) {
        showConfirm('清除' + label, '确定要清除' + label + '吗？', function () {
            ajaxPost('/api/cache/clearByType', { type: type });
        });
    };

    // 清除浏览器缓存（纯前端）
    window.clearBrowserCache = function () {
        showConfirm('清除浏览器缓存', '确定要清除浏览器缓存吗？将刷新页面并强制重新加载资源。', function () {
            if ('caches' in window) {
                caches.keys().then(function (names) {
                    for (var i = 0; i < names.length; i++) {
                        caches.delete(names[i]);
                    }
                });
            }
            if (typeof $.pjax !== 'undefined' && pjaxCache) {
                pjaxCache = {};
            }
            location.reload(true);
        });
    };

    // ==================== 通知轮询（60秒间隔）====================
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

    // ==================== GET 搜索表单 → PJAX 局部刷新 ====================
    // 拦截 PJAX 容器内的 GET 表单提交，改为 doPjax() 局部刷新，避免整页刷新
    $(document).on('submit', '#pjax-container form[method="get"]', function (e) {
        var $form = $(this);
        var action = $form.attr('action') || window.location.pathname;
        // 过滤掉 CSRF token，只保留业务参数（token对GET请求无意义）
        var queryString = $form.find(':input[name!="__token__"]').serialize();
        var url = queryString ? action + '?' + queryString : action;
        e.preventDefault();
        if (typeof window.doPjax === 'function') {
            window.doPjax(url);
        } else {
            // 降级：doPjax 未就绪时用原生提交
            $form.off('submit').submit();
        }
    });
})();
