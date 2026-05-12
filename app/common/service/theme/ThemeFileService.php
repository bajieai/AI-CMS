<?php
declare(strict_types=1);

namespace app\common\service\theme;

/**
 * 主题文件落盘服务 - V3.0 Phase 2
 *
 * 负责AI生成主题的文件系统操作：
 * - 目录安全创建
 * - 文件写入（白名单 + 路径安全）
 * - 文件回滚（失败时清理）
 * - 文件树结构生成
 */
class ThemeFileService
{
    /** @var array 允许写入的文件扩展名白名单 */
    protected array $allowedExtensions = [
        'html', 'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'
    ];

    /** @var array 本次操作已写入的文件路径（用于回滚） */
    protected array $writtenFiles = [];

    /** @var array 本次操作已创建的目录（用于回滚） */
    protected array $createdDirs = [];

    /**
     * 在指定主题目录下写入一组文件
     *
     * @param string $baseDir 目标主题根目录（如 template/themes/ai-theme-xxx）
     * @param array $files 文件列表 [['path'=>'pc/index.html', 'content'=>'...'], ...]
     * @return array ['success'=>bool, 'files_tree'=>array, 'written_count'=>int]
     * @throws \RuntimeException
     */
    public function writeThemeFiles(string $baseDir, array $files): array
    {
        $this->writtenFiles = [];
        $this->createdDirs = [];

        $baseDir = rtrim($baseDir, '/\\');
        $absBaseDir = $this->resolveRealPath($baseDir);

        if ($absBaseDir === false) {
            throw new \RuntimeException('主题目录路径不合法: ' . $baseDir);
        }

        // 确保根目录存在
        $this->ensureDir($absBaseDir);

        $filesTree = [];
        $writtenCount = 0;

        foreach ($files as $file) {
            $relativePath = $file['path'] ?? '';
            $content = $file['content'] ?? '';

            if (empty($relativePath)) {
                continue;
            }

            // 安全检查
            if (!$this->isPathSafe($relativePath)) {
                throw new \RuntimeException('不安全的文件路径: ' . $relativePath);
            }

            if (!$this->isAllowedExtension($relativePath)) {
                throw new \RuntimeException('不允许的文件类型: ' . $relativePath);
            }

            // 写入文件
            $targetPath = $absBaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetDir = dirname($targetPath);

            $this->ensureDir($targetDir);

            $result = file_put_contents($targetPath, $content, LOCK_EX);
            if ($result === false) {
                throw new \RuntimeException('文件写入失败: ' . $relativePath);
            }

            $this->writtenFiles[] = $targetPath;
            $writtenCount++;

            // 构建文件树
            $this->addToFilesTree($filesTree, $relativePath, strlen($content));
        }

        return [
            'success'       => true,
            'files_tree'    => $filesTree,
            'written_count' => $writtenCount,
            'base_dir'      => $absBaseDir,
        ];
    }

    /**
     * 回滚本次操作写入的所有文件和目录
     */
    public function rollback(): void
    {
        // 删除已写入的文件
        foreach ($this->writtenFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        // 删除已创建的目录（逆序，先删子目录）
        $dirs = array_reverse($this->createdDirs);
        foreach ($dirs as $dir) {
            if (is_dir($dir) && $this->isDirEmpty($dir)) {
                @rmdir($dir);
            }
        }

        $this->writtenFiles = [];
        $this->createdDirs = [];
    }

    /**
     * 确保目录存在
     */
    protected function ensureDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('目录创建失败: ' . $dir);
        }

        $this->createdDirs[] = $dir;
    }

    /**
     * 检查路径是否安全（防止路径穿越）
     */
    protected function isPathSafe(string $relativePath): bool
    {
        // 禁止 .. 穿越
        if (str_contains($relativePath, '..')) {
            return false;
        }

        // 禁止绝对路径
        if (str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
            return false;
        }

        // 禁止空路径
        if (empty(trim($relativePath))) {
            return false;
        }

        return true;
    }

    /**
     * 检查扩展名是否在白名单中
     */
    protected function isAllowedExtension(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, $this->allowedExtensions, true);
    }

    /**
     * 解析并验证真实路径
     */
    protected function resolveRealPath(string $path): string|false
    {
        $realPath = realpath($path);
        if ($realPath === false) {
            // 路径不存在，检查其父目录是否合法
            $parent = dirname($path);
            $parentReal = realpath($parent);
            if ($parentReal === false) {
                return false;
            }
            // 限制写入范围：必须在 template/themes/ 下
            $themeRoot = realpath(root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes');
            if ($themeRoot === false || !str_starts_with($parentReal, $themeRoot)) {
                return false;
            }
            return $path;
        }

        // 限制写入范围：必须在 template/themes/ 下
        $themeRoot = realpath(root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes');
        if ($themeRoot === false || !str_starts_with($realPath, $themeRoot)) {
            return false;
        }

        return $realPath;
    }

    /**
     * 检查目录是否为空
     */
    protected function isDirEmpty(string $dir): bool
    {
        $files = scandir($dir);
        return $files !== false && count($files) === 2; // 只有 . 和 ..
    }

    /**
     * 将文件路径添加到文件树结构
     */
    protected function addToFilesTree(array &$tree, string $path, int $size): void
    {
        $parts = explode('/', $path);
        $current = &$tree;

        foreach ($parts as $index => $part) {
            $isLast = $index === count($parts) - 1;

            if (!isset($current[$part])) {
                $current[$part] = $isLast ? ['type' => 'file', 'size' => $size] : ['type' => 'dir', 'children' => []];
            }

            if (!$isLast) {
                $current = &$current[$part]['children'];
            }
        }
    }

    /**
     * 扫描主题目录生成文件树（用于审核后台浏览）
     */
    public function scanFilesTree(string $themePath): array
    {
        $tree = [];

        if (!is_dir($themePath)) {
            return $tree;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relativePath = str_replace($themePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $this->addToFilesTree($tree, $relativePath, $file->getSize());
        }

        return $tree;
    }

    /**
     * 读取主题文件内容（用于审核详情页代码浏览）
     */
    public function readThemeFile(string $themePath, string $relativePath): string
    {
        if (!$this->isPathSafe($relativePath)) {
            throw new \RuntimeException('不安全的文件路径');
        }

        $fullPath = rtrim($themePath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $realPath = realpath($fullPath);
        $themeRealPath = realpath($themePath);

        if ($realPath === false || $themeRealPath === false || !str_starts_with($realPath, $themeRealPath)) {
            throw new \RuntimeException('文件路径超出主题目录范围');
        }

        if (!is_file($realPath)) {
            throw new \RuntimeException('文件不存在');
        }

        return file_get_contents($realPath) ?: '';
    }
}
