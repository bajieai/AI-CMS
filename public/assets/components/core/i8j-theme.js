/**
 * I8JTheme - 主题适配器
 * V3.0 Phase 2 UI组件库
 *
 * 提供CSS变量读取、暗色模式检测/切换等主题相关工具
 */
class I8JTheme {
    constructor() {
        this.darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this.isDarkMode = this.darkModeMediaQuery.matches;
        this.listeners = [];

        // 监听系统暗色模式变化
        this.darkModeMediaQuery.addEventListener('change', (e) => {
            this.isDarkMode = e.matches;
            this.notifyListeners();
        });
    }

    /**
     * 获取CSS变量值（从document.documentElement读取）
     */
    static getCssVar(name, fallback = '') {
        const value = getComputedStyle(document.documentElement)
            .getPropertyValue(name)
            .trim();
        return value || fallback;
    }

    /**
     * 设置CSS变量值（设置到document.documentElement）
     */
    static setCssVar(name, value) {
        document.documentElement.style.setProperty(name, value);
    }

    /**
     * 批量设置CSS变量
     */
    static setCssVars(vars) {
        Object.entries(vars).forEach(([name, value]) => {
            document.documentElement.style.setProperty(name, value);
        });
    }

    /**
     * 当前是否为暗色模式
     */
    get isDark() {
        return this.isDarkMode;
    }

    /**
     * 手动切换暗色模式（覆盖系统设置）
     */
    setDarkMode(enabled) {
        document.documentElement.classList.toggle('dark-mode', enabled);
        this.isDarkMode = enabled;
        this.notifyListeners();
    }

    /**
     * 添加暗色模式变化监听器
     */
    onChange(callback) {
        this.listeners.push(callback);
        return () => {
            const idx = this.listeners.indexOf(callback);
            if (idx !== -1) this.listeners.splice(idx, 1);
        };
    }

    notifyListeners() {
        this.listeners.forEach(cb => {
            try {
                cb(this.isDarkMode);
            } catch (e) {
                console.warn('I8JTheme listener error:', e);
            }
        });
    }

    /**
     * 获取颜色亮度（0-255）
     */
    static getLuminance(color) {
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        return (0.299 * r + 0.587 * g + 0.114 * b);
    }

    /**
     * 判断颜色是否为深色
     */
    static isDarkColor(color) {
        return this.getLuminance(color) < 128;
    }
}

// 全局单例
window.I8JTheme = new I8JTheme();
