/**
 * AI编辑器快捷键 — V2.9.28 A-6
 * 小扣v2审核问题5: Ctrl+Space被中文输入法占用，改用Alt键
 * Alt+Space=唤起AI菜单 / Alt+Shift+O=优化段落 / Alt+Shift+T=翻译
 */
(function() {
    'use strict';

    const AiEditorShortcuts = {
        shortcuts: {
            'alt+space': 'menu',
            'alt+shift+o': 'optimize',
            'alt+shift+t': 'translate'
        },

        init: function() {
            document.addEventListener('keydown', (e) => this.handleKey(e));
        },

        handleKey: function(e) {
            const parts = [];
            if (e.altKey) parts.push('alt');
            if (e.ctrlKey) parts.push('ctrl');
            if (e.shiftKey) parts.push('shift');
            parts.push(e.key.toLowerCase());
            const combo = parts.join('+');

            const action = this.shortcuts[combo];
            if (action) {
                e.preventDefault();
                this.executeAction(action);
            }
        },

        executeAction: function(action) {
            const sel = window.getSelection();
            const text = sel.toString().trim();

            switch (action) {
                case 'menu':
                    if (window.AiEditorEnhance) {
                        if (text.length >= 2) {
                            window.AiEditorEnhance.showFloat(sel);
                        }
                    }
                    break;
                case 'optimize':
                    if (text.length >= 2 && window.AiEditorEnhance) {
                        window.AiEditorEnhance.processAction('optimize');
                    }
                    break;
                case 'translate':
                    if (text.length >= 2 && window.AiEditorEnhance) {
                        window.AiEditorEnhance.processAction('translate');
                    }
                    break;
            }
        },

        updateShortcuts: function(newShortcuts) {
            this.shortcuts = newShortcuts;
        }
    };

    window.AiEditorShortcuts = AiEditorShortcuts;
    document.addEventListener('DOMContentLoaded', () => AiEditorShortcuts.init());
})();
