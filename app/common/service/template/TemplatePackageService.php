<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\service\ThemeFileService;
use think\facade\Filesystem;

/**
 * 模板打包服务 (V2.9.29 D-2)
 */
class TemplatePackageService
{
    /**
     * 打包模板为ZIP
     */
    public function pack(int $templateId, string $outputPath): string|false
    {
        $template = \app\common\model\TemplateStore::find($templateId);
        if (!$template) return false;

        $manifest = [
            'name' => $template->name,
            'version' => $template->version ?? '1.0.0',
            'author' => ['name' => $template->author ?? ''],
            'description' => $template->description ?? '',
            'support_models' => json_decode($template->support_models ?? '[]', true) ?: [],
            'category' => $template->category ?? '',
            'compatibility' => ['ai-cms' => '>=2.9.29'],
            'files' => ['templates' => [], 'css' => [], 'js' => []],
        ];

        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // 写入manifest
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 收集模板文件（根据template的skin路径）
        $themePath = root_path('template/themes/' . $template->skin_name);
        if (is_dir($themePath)) {
            $this->addDirToZip($zip, $themePath, 'templates/');
        }

        $zip->close();
        return $outputPath;
    }

    /**
     * 导入模板ZIP包
     */
    public function import(string $zipPath): array
    {
        $validator = new TemplatePackageValidator();
        $validation = $validator->validate($zipPath);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'errors' => ['无法打开ZIP文件']];
        }

        $manifestJson = $zip->getFromName('manifest.json');
        $manifest = json_decode($manifestJson, true);
        $zip->close();

        // 安全检查
        $securityCheck = $validator->securityScan($zipPath);
        if (!$securityCheck['safe']) {
            return ['success' => false, 'errors' => $securityCheck['issues']];
        }

        return ['success' => true, 'manifest' => $manifest];
    }

    private function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $handle = opendir($dir);
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->addDirToZip($zip, $path, $prefix . $entry . '/');
            } else {
                $zip->addFile($path, $prefix . $entry);
            }
        }
        closedir($handle);
    }
}
