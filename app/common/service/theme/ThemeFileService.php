<?php


// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 All rights reserved.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
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

            // 写入文件前：UTF-8编码安全校验
            // 防止GBK双编码损坏（UTF-8中文被错误按GBK解码后重新UTF-8编码）
            $content = $this->ensureUtf8Encoding($content, $relativePath);

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
     * 删除主题中的单个文件
     *
     * @param string $baseDir 主题根目录
     * @param string $relativePath 相对路径
     * @return bool
     */
    public function deleteThemeFile(string $baseDir, string $relativePath): bool
    {
        if (!$this->isPathSafe($relativePath)) {
            return false;
        }

        $targetPath = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $realPath = realpath($targetPath);
        $themeRealPath = realpath($baseDir);

        if ($realPath === false || $themeRealPath === false || !str_starts_with($realPath, $themeRealPath)) {
            return false;
        }

        if (!is_file($realPath)) {
            return false;
        }

        return unlink($realPath);
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
     * 确保内容是有效的UTF-8编码，修复可能的GBK双编码损坏
     *
     * 问题背景：在Windows中文环境下，LLM返回的UTF-8中文可能被PHP的
     * mbstring/internal_encoding(GBK)错误解码，导致双编码损坏。
     * 例如："动态"的UTF-8字节E58AA8E68081被当作GBK解读为"鍔ㄦ€"
     *
     * 安全加固（V3.1编码根治）：
     * - 强制设置 mb_internal_encoding 确保后续 mb_* 操作安全
     * - 扩展文件类型覆盖
     * - 多重编码校验 + 修复
     *
     * @param string $content 文件内容
     * @param string $relativePath 文件相对路径（用于日志）
     * @return string 修复后的内容
     */
    protected function ensureUtf8Encoding(string $content, string $relativePath): string
    {
        // ===== 编码根治：确保当前环境编码安全 =====
        @mb_internal_encoding('UTF-8');

        // 只对文本文件进行编码校验（扩展文件类型覆盖）
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['html', 'css', 'js', 'json', 'xml', 'svg', 'php', 'tpl'], true)) {
            return $content;
        }

        // 检查内容是否为有效的UTF-8
        if (mb_check_encoding($content, 'UTF-8')) {
            // 即使是有效UTF-8，也检查是否包含GBK双编码损坏的特征
            $content = $this->fixGarbledChinese($content, $relativePath);
            // 额外规范化：规范化Unicode组合字符
            $content = \Normalizer::normalize($content, \Normalizer::FORM_C);
            return $content;
        }

        // 如果不是有效UTF-8，尝试从GBK转换
        $converted = @mb_convert_encoding($content, 'UTF-8', 'GBK');
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            Log::warning("[ThemeFileService] 文件编码非UTF-8，已从GBK转换: {$relativePath}");
            // 再次检查转换后的内容是否还有GBK双编码损坏
            $converted = $this->fixGarbledChinese($converted, $relativePath);
            return $converted;
        }

        // 尝试自动检测编码
        $detected = @mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'ISO-8859-1', 'ASCII'], true);
        if ($detected && $detected !== 'UTF-8') {
            $converted = @mb_convert_encoding($content, 'UTF-8', $detected);
            if ($converted !== false) {
                Log::warning("[ThemeFileService] 文件编码自动检测为{$detected}，已转换: {$relativePath}");
                return $converted;
            }
        }

        // 最后手段：强制标记为UTF-8
        Log::error("[ThemeFileService] 文件编码无法识别，强制标记UTF-8: {$relativePath}");
        return mb_convert_encoding($content, 'UTF-8', 'UTF-8');
    }

    /**
     * 修复GBK双编码损坏的中文
     *
     * 检测原理：如果一段文本的UTF-8字节按GBK解读后，再按UTF-8编码，
     * 得到的字符都是常用汉字，则原始文本很可能是GBK双编码损坏的。
     *
     * @param string $content 文件内容
     * @param string $relativePath 文件路径（用于日志）
     * @return string 修复后的内容
     */
    protected function fixGarbledChinese(string $content, string $relativePath): string
    {
        // 检测是否包含典型的乱码字符范围
        // GBK双编码产生的字符主要在 U+3400-U+9FFF 范围内的罕见字
        // 正常中文文本中极少出现这些字符的组合
        if (!preg_match('/[\x{3400}-\x{4DBF}\x{20000}-\x{2A6DF}]/u', $content)) {
            return $content; // 不包含可疑字符，直接返回
        }

        $fixed = '';
        $len = mb_strlen($content, 'UTF-8');
        $garbledBuf = '';
        $fixCount = 0;

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($content, $i, 1, 'UTF-8');
            $ord = mb_ord($char, 'UTF-8');

            if ($this->isLikelyGarbledChar($char, $ord)) {
                $garbledBuf .= $char;
            } else {
                if ($garbledBuf !== '') {
                    $reversed = $this->tryReverseGarbled($garbledBuf);
                    if ($reversed !== $garbledBuf) {
                        $fixCount++;
                    }
                    $fixed .= $reversed;
                    $garbledBuf = '';
                }
                $fixed .= $char;
            }
        }

        if ($garbledBuf !== '') {
            $reversed = $this->tryReverseGarbled($garbledBuf);
            if ($reversed !== $garbledBuf) {
                $fixCount++;
            }
            $fixed .= $reversed;
        }

        if ($fixCount > 0) {
            Log::info("[ThemeFileService] 修复GBK双编码损坏: {$relativePath}, 修复{$fixCount}处");
        }

        return $fixed;
    }

    /**
     * 判断字符是否可能是GBK双编码损坏产生的乱码
     */
    protected function isLikelyGarbledChar(string $char, int $ord): bool
    {
        // ASCII字符不会是乱码
        if ($ord < 0x80) return false;

        // 只检查可能产生乱码的Unicode范围
        // GBK双编码主要产生 CJK Extension A (U+3400-U+4DBF) 和部分罕见CJK字符
        if (!(($ord >= 0x3400 && $ord <= 0x4DBF) ||
              ($ord >= 0xF900 && $ord <= 0xFAFF) ||
              ($ord >= 0x20000 && $ord <= 0x2A6DF))) {
            return false;
        }

        // 尝试逆转：将当前UTF-8字符的字节按GBK重新解读
        $bytes = @mb_convert_encoding($char, 'GBK', 'UTF-8');
        if ($bytes === false || $bytes === '') return false;

        $reversed = @mb_convert_encoding($bytes, 'UTF-8', 'GBK');
        if ($reversed === false || $reversed === $char) return false;

        // 检查逆转后的字符是否为常用汉字
        $revOrd = mb_ord($reversed, 'UTF-8');
        return $revOrd >= 0x4E00 && $revOrd <= 0x9FFF;
    }

    /**
     * 尝试逆转GBK双编码损坏
     */
    protected function tryReverseGarbled(string $garbled): string
    {
        $bytes = @mb_convert_encoding($garbled, 'GBK', 'UTF-8');
        if ($bytes === false || $bytes === '') return $garbled;

        $reversed = @mb_convert_encoding($bytes, 'UTF-8', 'GBK');
        if ($reversed === false || $reversed === '') return $garbled;

        // 验证逆转后的文本是否全部为有效字符
        if (!$this->isValidReversedText($reversed)) {
            return $garbled;
        }

        return $reversed;
    }

    /**
     * 验证逆转后的文本是否全部为有效中文/标点/ASCII
     */
    protected function isValidReversedText(string $text): bool
    {
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($text, $i, 1, 'UTF-8');
            $ord = mb_ord($ch, 'UTF-8');

            $valid = (
                ($ord >= 0x4E00 && $ord <= 0x9FFF) ||   // CJK常用汉字
                ($ord >= 0x3000 && $ord <= 0x303F) ||   // CJK标点
                ($ord >= 0xFF00 && $ord <= 0xFFEF) ||   // 全角字符
                ($ord >= 0x0020 && $ord <= 0x007E) ||   // ASCII可打印
                ($ord >= 0x00A0 && $ord <= 0x00FF) ||   // Latin-1
                $ord === 0x000A || $ord === 0x000D || $ord === 0x0009 ||  // 换行/回车/制表
                in_array($ch, ['—', "\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9C", "\xE2\x80\x9D", '…', '·', '€', '£', '¥'])
            );

            if (!$valid) return false;
        }
        return true;
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
