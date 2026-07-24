/**
 * 快捷键支持JS — V2.9.30 UX-3
 */
$(function() {
    var shortcuts = {
        'ctrl+s': { label: '保存', handler: function() { $('form').first().submit(); } },
        'ctrl+f': { label: '搜索', handler: function() { $('#globalSearchModal').modal('show'); } },
        'ctrl+k': { label: '全局搜索', handler: function() { $('#globalSearchModal').modal('show'); } },
        'escape': { label: '关闭弹窗', handler: function() { $('.modal.show').modal('hide'); } }
    };

    $(document).on('keydown', function(e) {
        var key = '';
        if (e.ctrlKey || e.metaKey) key += 'ctrl+';
        key += e.key.toLowerCase();

        if (shortcuts[key]) {
            e.preventDefault();
            shortcuts[key].handler();
        }
        if (e.key === 'Escape' && shortcuts.escape) {
            shortcuts.escape.handler();
        }
    });

    // Ctrl+S 阻止浏览器默认保存
    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            var $form = $('form').first();
            if ($form.length) {
                $form.submit();
                window.ui.toast('正在保存...', 'info');
            }
        }
    });
});
