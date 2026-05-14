<?php
declare(strict_types=1);

namespace app\common\service\theme;

use think\facade\Log;

/**
 * 主题 ZIP 打包/解包服务 - V3.0 Phase 3
 *
 * 职责：
 * - 主题目录打包为 ZIP
 * - ZIP 解压到临时目录
 * - 安全校验（文件数量/大小/白名单/路径穿越）
 * - theme.json 验证
 */
class ThemePackageService
{
    /** 最大文件数量 */
    protected int $maxFileCount = 500;
    /** 最大 ZIP 大小（MB） */
    protected int $maxSizeMB = 50;
    /** 允许的文件扩展名 */
    protected array $allowedExtensions = [
        'html', 'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'md', 'txt',
    ];

    /**
     * 将主题打包为 ZIP
     *
     * @param string $themeName 主题名称
     * @param bool   $includeCustomization 是否包含定制数据（V2.9.7 Phase 2）
     * @return array ['success'=>bool, 'path'=>string, 'message'=>string]
     */
    public function exportTheme(string $themeName, bool $includeCustomization = true): array
    {
        $themePath = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;
        if (!is_dir($themePath)) {
            return ['success' => false, 'path' => '', 'message' => '主题目录不存在'];
        }

        $tempDir = runtime_path() . 'temp';
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $themeName . '_' . date('YmdHis') . '.zip';

        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return ['success' => false, 'path' => '', 'message' => '创建ZIP失败'];
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $count = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                    $zip->addFile($file->getPathname(), $relativePath);
                    $count++;
                }
            }

            // V2.9.7 Phase 2: 包含定制数据
            if ($includeCustomization) {
                $customData = $this->getCustomizationData($themeName);
                if (!empty($customData)) {
                    $zip->addFromString('_customization.json', json_encode($customData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $count++;
                }
            }

            $zip->close();

            Log::info("[ThemePackage] 导出成功: {$themeName}, {$count} 个文件" . ($includeCustomization ? '(含定制)' : '') . " -> {$zipPath}");
            return ['success' => true, 'path' => $zipPath, 'message' => "打包完成，共 {$count} 个文件"];

        } catch (\Throwable $e) {
            Log::error("[ThemePackage] 导出失败: {$themeName}, error=" . $e->getMessage());
            if (file_exists($zipPath)) @unlink($zipPath);
            return ['success' => false, 'path' => '', 'message' => '导出失败: ' . $e->getMessage()];
        }
    }

    /**
     * 导入主题 ZIP
     *
     * 先解压到临时目录，校验通过后再移动到 template/themes/
     *
     * @param string $zipPath 上传的 ZIP 文件路径
     * @return array ['success'=>bool, 'theme_name'=>string, 'message'=>string]
     */
    public function importTheme(string $zipPath): array
    {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'theme_name' => '', 'message' => 'ZIP文件不存在'];
        }

        $fileSize = filesize($zipPath);
        if ($fileSize > $this->maxSizeMB * 1024 * 1024) {
            @unlink($zipPath);
            return ['success' => false, 'theme_name' => '', 'message' => 'ZIP文件超过' . $this->maxSizeMB . 'MB限制'];
        }

        $tempDir = runtime_path() . 'temp' . DIRECTORY_SEPARATOR . 'theme-import-' . uniqid();
        mkdir($tempDir, 0755, true);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                $this->cleanup($tempDir, $zipPath);
                return ['success' => false, 'theme_name' => '', 'message' => '无法打开ZIP文件'];
            }

            // 安全检查：文件数量
            if ($zip->numFiles > $this->maxFileCount) {
                $zip->close();
                $this->cleanup($tempDir, $zipPath);
                return ['success' => false, 'theme_name' => '', 'message' => 'ZIP内文件数量超过' . $this->maxFileCount . '个限制'];
            }

            // 安全检查：路径穿越 + 扩展名白名单 + ZIP Slip防护
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);

                // ZIP Slip防护：解压后的真实路径必须在目标目录内
                $extractedPath = realpath($tempDir) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entryName);
                $extractedReal = realpath(dirname($extractedPath));
                $tempReal = realpath($tempDir);
                if ($extractedReal === false || $tempReal === false || !str_starts_with($extractedReal, $tempReal)) {
                    $zip->close();
                    $this->cleanup($tempDir, $zipPath);
                    return ['success' => false, 'theme_name' => '', 'message' => 'ZIP包含不安全的文件路径(Slip攻击): ' . $entryName];
                }

                if (str_contains($entryName, '..')) {
                    $zip->close();
                    $this->cleanup($tempDir, $zipPath);
                    return ['success' => false, 'theme_name' => '', 'message' => 'ZIP包含不安全的文件路径: ' . $entryName];
                }

                if (!$zip->getFromIndex($i)) {
                    continue; // 目录
                }

                $ext = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->allowedExtensions, true)) {
                    $zip->close();
                    $this->cleanup($tempDir, $zipPath);
                    return ['success' => false, 'theme_name' => '', 'message' => '不允许的文件类型: ' . $entryName];
                }
            }

            // 解压到临时目录
            $zip->extractTo($tempDir);
            $zip->close();

            // 校验 theme.json
            $themeInfo = $this->validateThemeJson($tempDir);
            if (!$themeInfo['valid']) {
                $this->cleanup($tempDir, $zipPath);
                return ['success' => false, 'theme_name' => '', 'message' => $themeInfo['message']];
            }

            $themeName = $themeInfo['name'];
            $targetDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;

            // 如果目标目录已存在，先备份
            if (is_dir($targetDir)) {
                $backupDir = $targetDir . '_backup_' . date('YmdHis');
                rename($targetDir, $backupDir);
            }

            // 移动临时目录到目标位置
            rename($tempDir, $targetDir);

            // V2.9.7 Phase 2: 还原定制数据
            $customizationFile = $targetDir . DIRECTORY_SEPARATOR . '_customization.json';
            if (file_exists($customizationFile)) {
                $this->restoreCustomizationData($themeName, $customizationFile);
                @unlink($customizationFile); // 还原后删除，不留在主题目录中
            }

            // 清理上传的 ZIP
            @unlink($zipPath);

            Log::info("[ThemePackage] 导入成功: {$themeName}");
            return ['success' => true, 'theme_name' => $themeName, 'message' => '导入成功'];

        } catch (\Throwable $e) {
            $this->cleanup($tempDir, $zipPath);
            Log::error("[ThemePackage] 导入失败: error=" . $e->getMessage());
            return ['success' => false, 'theme_name' => '', 'message' => '导入失败: ' . $e->getMessage()];
        }
    }

    /**
     * 校验 theme.json
     */
    protected function validateThemeJson(string $dir): array
    {
        $jsonPath = $dir . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($jsonPath)) {
            return ['valid' => false, 'message' => '缺少 theme.json 文件', 'name' => ''];
        }

        $content = file_get_contents($jsonPath);
        $info = json_decode($content, true);
        if (!is_array($info)) {
            return ['valid' => false, 'message' => 'theme.json 格式错误', 'name' => ''];
        }

        if (empty($info['name'])) {
            return ['valid' => false, 'message' => 'theme.json 缺少 name 字段', 'name' => ''];
        }

        // 主题名安全检查
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $info['name']);
        if (empty($name)) {
            return ['valid' => false, 'message' => 'theme.json name 字段不合法', 'name' => ''];
        }

        return ['valid' => true, 'message' => 'ok', 'name' => $name];
    }

    /**
     * 清理临时文件
     */
    protected function cleanup(string $dir, string $zipPath = ''): void
    {
        if (is_dir($dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
            }
            @rmdir($dir);
        }
        if ($zipPath && file_exists($zipPath)) @unlink($zipPath);
    }

    /**
     * V2.9.7 Phase 2: 获取主题的定制数据（所有变体）
     */
    protected function getCustomizationData(string $themeName): array
    {
        $variants = \app\common\model\ThemeCustomization::where('theme_id', $themeName)
            ->select()
            ->toArray();

        if (empty($variants)) {
            return [];
        }

        return [
            'version' => '2.9.7',
            'theme_id' => $themeName,
            'exported_at' => date('Y-m-d H:i:s'),
            'variants' => $variants,
        ];
    }

    /**
     * V2.9.7 Phase 2: 还原定制数据
     */
    protected function restoreCustomizationData(string $themeName, string $jsonPath): void
    {
        $content = file_get_contents($jsonPath);
        $data = json_decode($content, true);

        if (empty($data) || empty($data['variants'])) {
            Log::warning("[ThemePackage] 无定制数据可还原: {$themeName}");
            return;
        }

        foreach ($data['variants'] as $variant) {
            $exists = \app\common\model\ThemeCustomization::where('theme_id', $themeName)
                ->where('variant_name', $variant['variant_name'] ?? 'default')
                ->find();

            if ($exists) {
                // 跳过已存在的变体（不覆盖）
                Log::info("[ThemePackage] 跳过已存在变体: {$themeName}/{$variant['variant_name']}");
                continue;
            }

            \app\common\model\ThemeCustomization::create([
                'theme_id'     => $themeName,
                'variant_name' => $variant['variant_name'] ?? 'default',
                'custom_data'  => $variant['custom_data'] ?? [],
                'is_active'    => $variant['is_active'] ?? 0,
            ]);

            Log::info("[ThemePackage] 还原变体: {$themeName}/{$variant['variant_name']}");
        }
    }

    /**
     * V2.9.7 Phase 2: 检查导入冲突
     */
    public function checkImportConflict(string $themeName): array
    {
        $targetDir = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName;
        $themeExists = is_dir($targetDir);
        $customExists = \app\common\model\ThemeCustomization::where('theme_id', $themeName)->count() > 0;

        return [
            'theme_exists'      => $themeExists,
            'custom_exists'     => $customExists,
            'has_conflict'      => $themeExists,
            'suggestion'        => $themeExists ? '同名主题已存在，可选择覆盖或重命名' : '无冲突',
        ];
    }
}
