/**
 * 插件市场前端组件 - V2.9.3 M25
 * 支持：一键安装、本地上传、搜索实时提示
 */
(function(window, $) {
    'use strict';

    var PluginStore = {
        init: function() {
            this.bindInstall();
            this.bindUpload();
            this.bindSearch();
        },

        bindInstall: function() {
            $(document).on('click', '.btn-install', function() {
                var $btn = $(this);
                var code = $btn.data('code');
                var url = $btn.data('url');

                if (!url) {
                    alert('缺少下载地址');
                    return;
                }

                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> 安装中...');

                $.post('/admin/plugin_market/install', {
                    code: code,
                    download_url: url
                }, function(res) {
                    if (res.code === 0) {
                        alert(res.msg);
                        location.reload();
                    } else {
                        alert(res.msg);
                        $btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> 重试');
                    }
                }, 'json').fail(function() {
                    alert('网络错误，请重试');
                    $btn.prop('disabled', false).html('<i class="bi bi-cloud-download"></i> 重试');
                });
            });
        },

        bindUpload: function() {
            $('#btnDoUpload').on('click', function() {
                var form = document.getElementById('uploadForm');
                var fileInput = form.querySelector('input[type="file"]');
                if (!fileInput.files.length) {
                    alert('请选择文件');
                    return;
                }

                var $btn = $(this);
                $('#uploadSpin').removeClass('d-none');
                $btn.prop('disabled', true);

                var formData = new FormData(form);
                $.ajax({
                    url: '/admin/plugin_market/upload',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#uploadSpin').addClass('d-none');
                        $btn.prop('disabled', false);
                        alert(res.msg);
                        if (res.code === 0) {
                            $('#uploadModal').modal('hide');
                            location.reload();
                        }
                    },
                    error: function() {
                        $('#uploadSpin').addClass('d-none');
                        $btn.prop('disabled', false);
                        alert('上传失败，请重试');
                    }
                });
            });
        },

        bindSearch: function() {
            var $searchInput = $('input[name="keyword"]');
            var debounceTimer = null;

            $searchInput.on('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    // 实时搜索：如果输入超过2字符且无分类筛选，可自动提交
                    var val = $searchInput.val().trim();
                    if (val.length >= 2) {
                        $searchInput.closest('form').submit();
                    }
                }, 800);
            });
        }
    };

    $(function() {
        PluginStore.init();
    });

    window.PluginStore = PluginStore;
})(window, jQuery);
