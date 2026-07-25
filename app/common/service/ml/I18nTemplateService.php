<?php
declare(strict_types=1);

namespace app\common\service\ml;

/**
 * 国际化模板服务
 * V2.9.37 I18N-3
 */
class I18nTemplateService
{
    /**
     * 加载模板语言包
     */
    public function loadTemplateLangPack(int $templateId, string $langCode): array
    {
        $langFile = $this->getLangPackPath($templateId, $langCode);
        if (file_exists($langFile)) {
            $pack = include $langFile;
            return is_array($pack) ? $pack : [];
        }
        // 回退到默认语言
        $defaultFile = $this->getLangPackPath($templateId, 'zh-cn');
        if (file_exists($defaultFile) && $langCode !== 'zh-cn') {
            $pack = include $defaultFile;
            return is_array($pack) ? $pack : [];
        }
        return [];
    }

    /**
     * 获取多语言字段
     */
    public function getMultilingualFields(int $templateId): array
    {
        // 返回模板中支持多语言的字段列表
        return [
            ['field' => 'title', 'label' => '标题'],
            ['field' => 'description', 'label' => '描述'],
            ['field' => 'content', 'label' => '内容'],
            ['field' => 'seo_title', 'label' => 'SEO标题'],
            ['field' => 'seo_description', 'label' => 'SEO描述'],
        ];
    }

    /**
     * 渲染模板(根据语言)
     */
    public function renderTemplate(int $templateId, string $langCode): string
    {
        $langPack = $this->loadTemplateLangPack($templateId, $langCode);
        // 模板渲染逻辑(集成到ThinkPHP模板引擎)
        // 返回渲染后的HTML
        return '';
    }

    /**
     * 生成语言包文件(开发者工具)
     */
    public function generateLangPackFile(int $templateId): string
    {
        $fields = $this->getMultilingualFields($templateId);
        $template = [];
        foreach ($fields as $field) {
            $template[$field['field']] = $field['label'];
        }
        $content = "<?php\nreturn " . var_export($template, true) . ";\n";
        $path = $this->getLangPackPath($templateId, 'zh-cn');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * 检查语言包完整性
     */
    public function checkLangPackIntegrity(int $templateId): array
    {
        $langCodes = ['zh-cn', 'en'];
        $result = [];
        foreach ($langCodes as $code) {
            $pack = $this->loadTemplateLangPack($templateId, $code);
            $result[$code] = [
                'exists'    => !empty($pack),
                'entries'   => count($pack),
                'file_path' => $this->getLangPackPath($templateId, $code),
            ];
        }
        return $result;
    }

    private function getLangPackPath(int $templateId, string $langCode): string
    {
        // 模板语言包路径: template/{theme}/lang/{lang_code}.php
        // 实际路径根据模板系统动态获取
        return public_path() . 'template/' . $templateId . '/lang/' . $langCode . '.php';
    }
}
