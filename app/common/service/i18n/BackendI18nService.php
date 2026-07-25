<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use app\common\service\ml\LangSwitchService;
use think\facade\Cache;
use think\facade\Lang;

/**
 * 后台界面国际化服务
 * V2.9.39 I18N-V2-2
 *
 * 后台语言包管理 + 按模块加载 + 缓存 + 三级回退(模块→通用→默认语言)
 */
class BackendI18nService
{
    private const CACHE_TAG = 'backend_i18n';
    private const CACHE_TTL = 3600;

    /** @var LangSwitchService */
    private LangSwitchService $langSwitchService;

    /** 后台支持的语言包模块 */
    public const MODULES = ['common', 'content', 'system', 'user', 'media', 'seo', 'plugin', 'setting', 'dashboard'];

    public function __construct()
    {
        $this->langSwitchService = new LangSwitchService();
    }

    /**
     * 获取后台当前语言
     */
    public function getCurrentLang(): string
    {
        // 1. Session中的后台语言偏好
        $sessionLang = session('admin_lang');
        if ($sessionLang && $this->isSupportedLang($sessionLang)) {
            return $sessionLang;
        }
        // 2. 系统默认语言
        return $this->langSwitchService->getDefaultLang();
    }

    /**
     * 设置后台语言
     */
    public function setLang(string $langCode): bool
    {
        if (!$this->isSupportedLang($langCode)) {
            return false;
        }
        session('admin_lang', $langCode);
        return true;
    }

    /**
     * 加载模块语言包（带缓存）
     *
     * @param string $module 模块名(content/system/user等)
     * @param string|null $langCode 语言代码(null=当前语言)
     * @return array [key => value, ...]
     */
    public function loadModulePack(string $module, ?string $langCode = null): array
    {
        $langCode = $langCode ?? $this->getCurrentLang();
        $cacheKey = 'backend_i18n_' . $langCode . '_' . $module;

        return Cache::remember($cacheKey, function () use ($module, $langCode) {
            // 1. 优先从数据库加载
            $dbPack = $this->loadFromDatabase($langCode, $module);
            // 2. 从文件加载
            $filePack = $this->loadFromFile($langCode, $module);
            // 3. 合并（数据库优先）
            return array_merge($filePack, $dbPack);
        }, self::CACHE_TTL);
    }

    /**
     * 加载通用语言包（所有模块共享的key）
     */
    public function loadCommonPack(?string $langCode = null): array
    {
        return $this->loadModulePack('common', $langCode);
    }

    /**
     * 翻译（三级回退: 模块 → 通用 → 默认语言）
     *
     * @param string $key 翻译键
     * @param string $module 模块名
     * @param array $params 替换参数
     * @param string|null $langCode 语言代码
     * @return string
     */
    public function translate(string $key, string $module = 'common', array $params = [], ?string $langCode = null): string
    {
        $langCode = $langCode ?? $this->getCurrentLang();

        // 1. 模块语言包
        $modulePack = $this->loadModulePack($module, $langCode);
        if (isset($modulePack[$key])) {
            return $this->replaceParams($modulePack[$key], $params);
        }

        // 2. 通用语言包
        if ($module !== 'common') {
            $commonPack = $this->loadCommonPack($langCode);
            if (isset($commonPack[$key])) {
                return $this->replaceParams($commonPack[$key], $params);
            }
        }

        // 3. 默认语言回退
        $defaultLang = $this->langSwitchService->getDefaultLang();
        if ($langCode !== $defaultLang) {
            $defaultPack = $this->loadModulePack($module, $defaultLang);
            if (isset($defaultPack[$key])) {
                return $this->replaceParams($defaultPack[$key], $params);
            }
        }

        // 4. 返回key本身
        return $key;
    }

    /**
     * 批量翻译
     *
     * @param array $keys 翻译键列表
     * @param string $module 模块名
     * @param string|null $langCode
     * @return array [key => translated_value, ...]
     */
    public function translateBatch(array $keys, string $module = 'common', ?string $langCode = null): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->translate($key, $module, [], $langCode);
        }
        return $results;
    }

    /**
     * 获取所有模块的语言包（用于前端注入）
     */
    public function getAllModulesPack(?string $langCode = null): array
    {
        $langCode = $langCode ?? $this->getCurrentLang();
        $result = [];
        foreach (self::MODULES as $module) {
            $result[$module] = $this->loadModulePack($module, $langCode);
        }
        return $result;
    }

    /**
     * 获取模块完成度统计
     *
     * @return array [{module, total_keys, translated_keys, rate}, ...]
     */
    public function getModuleStats(): array
    {
        $langCode = $this->getCurrentLang();
        $defaultLang = $this->langSwitchService->getDefaultLang();
        $result = [];
        foreach (self::MODULES as $module) {
            $pack = $this->loadModulePack($module, $langCode);
            $defaultPack = $this->loadModulePack($module, $defaultLang);
            $total = count($defaultPack);
            $translated = count($pack);
            $result[] = [
                'module'           => $module,
                'total_keys'       => $total,
                'translated_keys'  => $translated,
                'rate'             => $total > 0 ? round($translated / $total * 100, 2) : 0,
            ];
        }
        return $result;
    }

    /**
     * 扫描并提取后台模板中的翻译键
     *
     * @param string $templateDir 模板目录
     * @return array 提取到的key列表
     */
    public function scanTemplateKeys(string $templateDir): array
    {
        $keys = [];
        $files = glob($templateDir . '*.html');
        if (!$files) return $keys;
        foreach ($files as $file) {
            $content = file_get_contents($file);
            // 匹配 {:lang('xxx')} 或 lang('xxx') 模式
            if (preg_match_all("/lang\(['\"]([^'\"]+)['\"]\)/", $content, $matches)) {
                foreach ($matches[1] as $key) {
                    $keys[$key] = true;
                }
            }
            // 匹配 {:trans('xxx')} 模式
            if (preg_match_all("/trans\(['\"]([^'\"]+)['\"]\)/", $content, $matches)) {
                foreach ($matches[1] as $key) {
                    $keys[$key] = true;
                }
            }
        }
        return array_keys($keys);
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Cache::clear();
    }

    // ===== 内部方法 =====

    /**
     * 检查语言是否受支持
     */
    private function isSupportedLang(string $langCode): bool
    {
        $list = $this->langSwitchService->getLanguageList();
        foreach ($list as $lang) {
            if ($lang['code'] === $langCode) {
                return true;
            }
        }
        return false;
    }

    /**
     * 从数据库加载语言包
     */
    private function loadFromDatabase(string $langCode, string $module): array
    {
        try {
            $rows = \think\facade\Db::name('lang_pack')
                ->where('lang_code', $langCode)
                ->where('module', 'backend_' . $module)
                ->where('is_translated', 1)
                ->select()
                ->toArray();
            $pack = [];
            foreach ($rows as $row) {
                $pack[$row['entry_key']] = $row['entry_value'];
            }
            return $pack;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 从文件加载语言包
     */
    private function loadFromFile(string $langCode, string $module): array
    {
        $filePath = app_path() . '/admin/lang/' . $langCode . '/' . $module . '.php';
        if (!file_exists($filePath)) {
            return [];
        }
        $pack = include $filePath;
        return is_array($pack) ? $pack : [];
    }

    /**
     * 替换参数
     */
    private function replaceParams(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace(':' . $key, (string) $value, $text);
        }
        return $text;
    }
}
