/**
 * 全局搜索JS — V2.9.30 UX-3
 * Ctrl+K 触发全局搜索
 */
$(function() {
    var searchModal = '<div class="modal fade" id="globalSearchModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">';
    searchModal += '<div class="modal-header"><h5 class="modal-title"><i class="bi bi-search me-1"></i>全局搜索</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>';
    searchModal += '<div class="modal-body"><div class="mb-3"><input type="text" class="form-control form-control-lg" id="global-search-input" placeholder="搜索内容、模板、插件、设置..." autofocus></div>';
    searchModal += '<div id="global-search-results" style="max-height:400px;overflow-y:auto"></div></div>';
    searchModal += '</div></div></div>';
    $('body').append(searchModal);

    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            $('#globalSearchModal').modal('show');
            setTimeout(function() { $('#global-search-input').focus(); }, 300);
        }
    });

    var searchTimer;
    $('#global-search-input').on('input', function() {
        var keyword = $(this).val().trim();
        clearTimeout(searchTimer);
        if (keyword.length < 2) {
            $('#global-search-results').html('');
            return;
        }
        searchTimer = setTimeout(function() {
            $.post('/admin/search/global', { keyword: keyword }, function(res) {
                if (res.code === 0) {
                    renderResults(res.data);
                }
            }).fail(function() {});
        }, 300);
    });

    function renderResults(data) {
        var html = '';
        if (data.contents && data.contents.length) {
            html += '<h6 class="text-muted"><i class="bi bi-file-text"></i> 内容</h6>';
            data.contents.forEach(function(item) {
                html += '<a href="/admin/content/edit/' + item.id + '" class="search-result-item d-block p-2 border-bottom text-decoration-none">' + item.title + '</a>';
            });
        }
        if (data.templates && data.templates.length) {
            html += '<h6 class="text-muted mt-2"><i class="bi bi-layout-text-window-reverse"></i> 模板</h6>';
            data.templates.forEach(function(item) {
                html += '<a href="/admin/template_store/edit/' + item.id + '" class="search-result-item d-block p-2 border-bottom text-decoration-none">' + item.name + '</a>';
            });
        }
        if (data.plugins && data.plugins.length) {
            html += '<h6 class="text-muted mt-2"><i class="bi bi-plugin"></i> 插件</h6>';
            data.plugins.forEach(function(item) {
                html += '<a href="/admin/plugin/detail/' + item.id + '" class="search-result-item d-block p-2 border-bottom text-decoration-none">' + item.name + '</a>';
            });
        }
        if (!html) html = '<p class="text-center text-muted py-3">未找到相关结果</p>';
        $('#global-search-results').html(html);
    }
});
