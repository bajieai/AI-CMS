<?php

declare(strict_types=1);

namespace app\common\service;

/**
 * V2.9.35 PLUG-5: 插件打包服务
 */
class PluginPackService
{
    /**
     * 打包插件为ZIP
     */
    public function pack(string $identifier): array
    {
        $pluginPath = root_path() . 'plugin' . DIRECTORY_SEPARATOR . $identifier . DIRECTORY_SEPARATOR;
        if (!is_dir($pluginPath)) {
            return ['success' => false, 'message' => '插件目录不存在'];
        }

        $outputPath = runtime_path() . 'plugin_packs' . DIRECTORY_SEPARATOR;
        if (!is_dir($outputPath)) {
            @mkdir($outputPath, 0755, true);
        }

        $zipFile = $outputPath . $identifier . '_' . date('YmdHis') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'message' => 'ZIP创建失败'];
        }

        $this->addDirToZip($zip, $pluginPath, $identifier . '/');
        $zip->close();

        return ['success' => true, 'path' => $zipFile, 'size' => filesize($zipFile)];
    }

    /**
     * 递归添加目录到ZIP
     */
    protected function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $zipPath = $prefix . $file;

            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirToZip($zip, $filePath, $zipPath . '/');
            } else {
                $zip->addFile($filePath, $zipPath);
            }
        }
        closedir($handle);
    }
}
