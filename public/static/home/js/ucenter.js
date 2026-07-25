/**
 * AI-CMS V2.9.10 用户中心交互脚本
 * 包含：签到、收藏、兑换、Tab切换、复制邀请码等通用交互
 */
(function(window) {
    'use strict';

    var Ucenter = {
        /**
         * 每日签到
         */
        signin: function() {
            $.post('/member/signin/do', {}, function(res) {
                if (res.success) {
                    showToast('签到成功！获得 ' + (res.points || 0) + ' 积分', 'success');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast(res.msg || '签到失败', 'warning');
                }
            }).fail(function() {
                showToast('网络错误，请重试', 'danger');
            });
        },

        /**
         * 切换收藏状态
         * @param {number} contentId 内容ID
         * @param {HTMLElement} btn 按钮元素（可选）
         */
        toggleFavorite: function(contentId, btn) {
            if (!contentId) return;
            var $btn = btn ? $(btn) : null;
            if ($btn) $btn.prop('disabled', true);

            $.post('/member/favoriteRemove', { content_id: contentId }, function(res) {
                if (res.success) {
                    showToast('已取消收藏', 'success');
                    if ($btn) {
                        $btn.closest('.favorite-item').fadeOut(300, function() { $(this).remove(); });
                    }
                } else {
                    showToast(res.msg || '操作失败', 'warning');
                }
            }).fail(function() {
                showToast('网络错误', 'danger');
            }).always(function() {
                if ($btn) $btn.prop('disabled', false);
            });
        },

        /**
         * 积分兑换
         * @param {number} productId 商品ID
         */
        exchange: function(productId) {
            if (!productId) return;
            if (!confirm('确定使用积分兑换该商品吗？')) return;

            $.post('/points/exchange', { product_id: productId }, function(res) {
                if (res.success) {
                    showToast('兑换成功！', 'success');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast(res.msg || '兑换失败', 'warning');
                }
            }).fail(function() {
                showToast('网络错误', 'danger');
            });
        },

        /**
         * Tab切换（通用）
         * @param {string} tabId Tab内容区ID
         * @param {HTMLElement} nav 导航元素
         */
        switchTab: function(tabId, nav) {
            // 隐藏所有Tab内容
            $(nav).closest('.ucenter-tabs').find('.ucenter-tab-pane').removeClass('active');
            // 显示目标Tab
            $('#' + tabId).addClass('active');
            // 切换导航激活态
            $(nav).closest('.ucenter-tabs').find('.ucenter-tab-nav').removeClass('active');
            $(nav).addClass('active');
        },

        /**
         * 复制邀请码到剪贴板
         * @param {string} code 邀请码
         */
        copyInviteCode: function(code) {
            if (!code) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function() {
                    showToast('邀请码已复制', 'success');
                }).catch(function() {
                    Ucenter._fallbackCopy(code);
                });
            } else {
                Ucenter._fallbackCopy(code);
            }
        },

        _fallbackCopy: function(text) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showToast('邀请码已复制', 'success');
            } catch (e) {
                showToast('复制失败，请手动复制', 'warning');
            }
            document.body.removeChild(textarea);
        },

        /**
         * 头像上传触发
         */
        triggerAvatarUpload: function() {
            $('#avatarUploadInput').trigger('click');
        },

        /**
         * 处理头像上传
         * @param {HTMLInputElement} input
         */
        handleAvatarUpload: function(input) {
            var file = input.files[0];
            if (!file) return;

            var formData = new FormData();
            formData.append('file', file);

            $.ajax({
                url: '/member/uploadAvatar',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.code === 0 && res.data && res.data.url) {
                        $('.ucenter-avatar img').attr('src', res.data.url);
                        showToast('头像更新成功', 'success');
                    } else {
                        showToast(res.msg || '上传失败', 'danger');
                    }
                },
                error: function() {
                    showToast('网络错误', 'danger');
                }
            });
            input.value = '';
        },

        /**
         * 标记通知已读
         * @param {number} id 通知ID
         */
        markRead: function(id) {
            $.post('/member/notificationRead', { id: id }, function(res) {
                if (res.success) {
                    $('#notify-' + id).removeClass('unread').find('.unread-dot').remove();
                }
            });
        },

        /**
         * 标记所有通知已读
         */
        markAllRead: function() {
            $.post('/member/notificationReadAll', {}, function(res) {
                if (res.success) {
                    showToast('全部已读', 'success');
                    setTimeout(function() { location.reload(); }, 800);
                }
            });
        }
    };

    // 暴露到全局
    window.Ucenter = Ucenter;

    // 简单的toast提示（如果页面未加载admin.js等含showToast的脚本）
    if (typeof showToast !== 'function') {
        window.showToast = function(message, type) {
            type = type || 'info';
            var $toast = $('<div class="ucenter-toast toast-' + type + '">' + message + '</div>');
            $('body').append($toast);
            $toast.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                padding: '12px 20px',
                borderRadius: '8px',
                color: '#fff',
                background: type === 'success' ? '#52c41a' : type === 'danger' ? '#ff4d4f' : type === 'warning' ? '#faad14' : '#1890ff',
                boxShadow: '0 4px 12px rgba(0,0,0,.15)',
                fontSize: '14px',
                transition: 'all .3s ease'
            }).hide().fadeIn(200);
            setTimeout(function() {
                $toast.fadeOut(300, function() { $(this).remove(); });
            }, 2500);
        };
    }
})(window);
