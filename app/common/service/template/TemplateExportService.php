<?php
declare(strict_types=1);
namespace app\common\service\template;

use app\common\model\TemplateStore;
use ZipArchive;

class TemplateExportService
{
    public static function export(int $templateId): array
    {
        $template = TemplateStore::find($templateId);
        if (!$template) return ['success' => false, 'msg' => '模板不存在'];
        $exportDir = runtime_path() . 'template_exports' . DIRECTORY_SEPARATOR;
        if (!is_dir($exportDir)) mkdir($exportDir, 0755, true);
        $filename = 'template_' . $templateId . '_' . date('YmdHis') . '.zip';
        $filepath = $exportDir . $filename;
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return ['success' => false, 'msg' => 'ZIP创建失败'];
        $themeJson = json_encode(['name' => $template->name, 'code' => $template->code, 'version' => $template->version ?? '1.0.0', 'description' => $template->description ?? '', 'author' => $template->author ?? ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $zip->addFromString('theme.json', $themeJson);
        $templatePath = root_path() . 'template/themes/' . ($template->code ?? '');
        if (is_dir($templatePath)) self::addDirToZip($zip, $templatePath, 'files/');
        $zip->close();
        return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
    }

    private static function addDirToZip(ZipArchive $zip, string $dir, string $prefix): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $relativePath = $prefix . substr($file->getPathname(), strlen($dir) + 1);
            if ($file->isDir()) $zip->addEmptyDir($relativePath);
            else $zip->addFile($file->getPathname(), $relativePath);
        }
    }
}
