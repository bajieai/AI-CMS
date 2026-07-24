<?php
declare(strict_types=1);

namespace app\common\service\i18n;

use app\common\service\ml\LangSwitchService;
use think\facade\Cache;

/**
 * 插件国际化服务
 * V2.9.39 I18N-V2-2
 *
 * 插件语言包规范 + 加载 + 管理
 * 插件语言包目录: plugins/{plugin_id}/lang/{lang_code}.php
 */
class PluginI18nService
{
    private const CACHE_TAG = 'plugin_i18n';
    private const CACHE_TTL = 3600;

    /** @var LangSwitchService */
    private LangSwitchService $langSwitchService;

    public function __construct()
    {
        $this->langSwitchService = new LangSwitchService();
    }

    /**
     * 加载插件语言包
     *
     * @param string $pluginId 插件ID
     * @param string|null $langCode 语言代码(null=当前语言)
     * @return array [key => value, ...]
     */
    public function loadPluginPack(string $pluginId, ?string $langCode = null): array
    {
        $langCode = $langCode ?? $this->langSwitchService->getDefaultLang();
        $cacheKey = 'plugin_i18n_' . $pluginId . '_' . $langCode;

        return Cache::remember($cacheKey, function () use ($pluginId, $langCode) {
            // 1. 从数据库加载
            $dbPack = $this->loadFromDatabase($pluginId, $langCode);
            // 2. 从插件文件加载
            $filePack = $this->loadFromFile($pluginId, $langCode);
            // 3. 合并（数据库优先）
            return array_merge($filePack, $dbPack);
        }, self::CACHE_TTL);
    }

    /**
     * 翻译插件文本
     *
     * @param string $pluginId 插件ID
     * @param string $key 翻译键
     * @param array $params 替换参数
     * @param string|null $langCode 语言代码
     * @return string
     */
    public function translate(string $pluginId, string $key, array $params = [], ?string $langCode = null): string
    {
        $langCode = $langCode ?? $this->langSwitchService->getDefaultLang();

        // 1. 插件语言包
        $pack = $this->loadPluginPack($pluginId, $langCode);
        if (isset($pack[$key])) {
            return $this->replaceParams($pack[$key], $params);
        }

        // 2. 默认语言回退
        $defaultLang = $this->langSwitchService->getDefaultLang();
        if ($langCode !== $defaultLang) {
            $defaultPack = $this->loadPluginPack($pluginId, $defaultLang);
            if (isset($defaultPack[$key])) {
                return $this->replaceParams($defaultPack[$key], $params);
            }
        }

        return $key;
    }

    /**
     * 获取插件语言包文件路径
     */
    public function getPluginLangPath(string $pluginId, string $langCode): string
    {
        return public_path() . 'plugins/' . $pluginId . '/lang/' . $langCode . '.php';
    }

    /**
     * 获取插件支持的语言列表
     *
     * @param string $pluginId 插件ID
     * @return array [{code, name, file_exists}, ...]
     */
    public function getPluginLanguages(string $pluginId): array
    {
        $allLangs = $this->langSwitchService->getLanguageList();
        $result = [];
        foreach ($allLangs as $lang) {
            $filePath = $this->getPluginLangPath($pluginId, $lang['code']);
            $result[] = [
                'code'        => $lang['code'],
                'name'        => $lang['name'],
                'file_exists' => file_exists($filePath),
            ];
        }
        return $result;
    }

    /**
     * 保存插件语言包到文件
     *
     * @param string $pluginId 插件ID
     * @param string $langCode 语言代码
     * @param array $data 语言包数据
     * @return bool
     */
    public function savePluginPackToFile(string $pluginId, string $langCode, array $data): bool
    {
        $dir = public_path() . 'plugins/' . $pluginId . '/lang/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        $content = "<?php\nreturn " . var_export($data, true) . ";\n";
        $path = $this->getPluginLangPath($pluginId, $langCode);
        $result = file_put_contents($path, $content);
        if ($result !== false) {
            Cache::clear();
            return true;
        }
        return false;
    }

    /**
     * 导入插件语言包到数据库
     *
     * @param string $pluginId 插件ID
     * @param string $langCode 语言代码
     * @return int 导入条目数
     */
    public function importToDatabase(string $pluginId, string $langCode): int
    {
        $filePack = $this->loadFromFile($pluginId, $langCode);
        if (empty($filePack)) return 0;

        $count = 0;
        foreach ($filePack as $key => $value) {
            try {
                $existing = \think\facade\Db::name('lang_pack')
                    ->where('lang_code', $langCode)
                    ->where('module', 'plugin_' . $pluginId)
                    ->where('entry_key', $key)
                    ->find();
                if ($existing) {
                    \think\facade\Db::name('lang_pack')
                        ->where('id', $existing['id'])
                        ->update([
                            'entry_value'   => $value,
                            'is_translated' => 1,
                        ]);
                } else {
                    \think\facade\Db::name('lang_pack')->insert([
                        'lang_code'      => $langCode,
                        'module'         => 'plugin_' . $pluginId,
                        'group_name'      => 'plugin',
                        'entry_key'      => $key,
                        'entry_value'    => $value,
                        'entry_original' => $value,
                        'is_translated'  => 1,
                    ]);
                }
                $count++;
            } catch (\Throwable $e) {
                continue;
            }
        }
        Cache::clear();
        return $count;
    }

    /**
     * 获取插件翻译完成度
     *
     * @param string $pluginId 插件ID
     * @param string $langCode 语言代码
     * @return array [total, translated, rate]
     */
    public function getPluginCompletion(string $pluginId, string $langCode): array
    {
        $defaultLang = $this->langSwitchService->getDefaultLang();
        $defaultPack = $this->loadFromFile($pluginId, $defaultLang);
        $targetPack = $this->loadPluginPack($pluginId, $langCode);

        $total = count($defaultPack);
        $translated = 0;
        foreach ($defaultPack as $key => $value) {
            if (isset($targetPack[$key]) && $targetPack[$key] !== $value) {
                $translated++;
            }
        }
        return [
            'total'       => $total,
            'translated'  => $translated,
            'rate'        => $total > 0 ? round($translated / $total * 100, 2) : 0,
        ];
    }

    /**
     * 验证插件语言包格式
     *
     * @param string $pluginId 插件ID
     * @param string $langCode 语言代码
     * @return array [valid => bool, errors => []]
     */
    public function validatePack(string $pluginId, string $langCode): array
    {
        $filePath = $this->getPluginLangPath($pluginId, $langCode);
        if (!file_exists($filePath)) {
            return ['valid' => false, 'errors' => ['语言包文件不存在']];
        }

        $data = include $filePath;
        $errors = [];

        if (!is_array($data)) {
            $errors[] = '语言包必须返回数组';
            return ['valid' => false, 'errors' => $errors];
        }

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                $errors[] = "键必须是字符串: " . var_export($key, true);
            }
            if (!is_string($value)) {
                $errors[] = "值必须是字符串: key=" . $key;
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * 清除插件语言包缓存
     */
    public function clearCache(string $pluginId = ''): void
    {
        if ($pluginId) {
            $langCode = $this->langSwitchService->getDefaultLang();
            $langs = $this->langSwitchService->getLanguageList();
            foreach ($langs as $lang) {
                Cache::delete('plugin_i18n_' . $pluginId . '_' . $lang['code']);
            }
        } else {
            Cache::clear();
        }
    }

    // ===== 内部方法 =====

    /**
     * 从数据库加载
     */
    private function loadFromDatabase(string $pluginId, string $langCode): array
    {
        try {
            $rows = \think\facade\Db::name('lang_pack')
                ->where('lang_code', $langCode)
                ->where('module', 'plugin_' . $pluginId)
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
     * 从文件加载
     */
    private function loadFromFile(string $pluginId, string $langCode): array
    {
        $filePath = $this->getPluginLangPath($pluginId, $langCode);
        if (!file_exists($filePath)) {
            // 尝试默认语言
            $defaultLang = $this->langSwitchService->getDefaultLang();
            if ($langCode !== $defaultLang) {
                $filePath = $this->getPluginLangPath($pluginId, $defaultLang);
                if (!file_exists($filePath)) {
                    return [];
                }
            } else {
                return [];
            }
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
