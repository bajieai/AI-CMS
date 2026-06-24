<?php
declare(strict_types=1);
namespace app\common\service\template;

/**
 * 模板包格式校验服务 (V2.9.29 D-2)
 */
class TemplatePackageValidator
{
    public function validate(string $zipPath): array
    {
        $errors = [];
        if (!is_file($zipPath)) {
            $errors[] = 'ZIP文件不存在';
            return ['valid' => false, 'errors' => $errors];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $errors[] = '无法打开ZIP文件';
            return ['valid' => false, 'errors' => $errors];
        }

        // 检查manifest.json
        $manifest = $zip->getFromName('manifest.json');
        if (!$manifest) {
            $errors[] = '缺少manifest.json';
        } else {
            $data = json_decode($manifest, true);
            if (!$data) {
                $errors[] = 'manifest.json格式无效';
            } elseif (empty($data['name'])) {
                $errors[] = 'manifest.json缺少name字段';
            }
        }

        $zip->close();
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public function securityScan(string $zipPath): array
    {
        $issues = [];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['safe' => false, 'issues' => ['无法打开ZIP文件']];
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            // 检查路径穿越
            if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
                $issues[] = "检测到路径穿越: {$filename}";
            }
            // 检查PHP文件（模板不应包含可执行PHP）
            if (preg_match('/\.php$/', $filename)) {
                $issues[] = "检测到PHP文件: {$filename}";
            }
        }

        $zip->close();
        return ['safe' => empty($issues), 'issues' => $issues];
    }
}
