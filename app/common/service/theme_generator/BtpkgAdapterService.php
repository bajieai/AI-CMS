<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme_generator;

/**
 * BTPKG 读包服务（契约 0.2.0）
 *
 * 只做四件事：验整包 sha256、读 manifest 并做契约校验、解包到临时目录、从已解包目录读回 manifest。
 * 不碰数据库、不碰 public/skin/themes，落盘与登记由控制器编排。
 */
class BtpkgAdapterService
{
    /** manifest 允许出现的字段（契约内字段以外的任何键一律拒） */
    public const CONTRACT_FIELDS = [
        'name',
        'slug',
        'version',
        'description',
        'templates',
        'assets',
        'generated_at',
        'target_platform',
    ];

    /** manifest 必填字段 */
    public const REQUIRED_FIELDS = ['name', 'slug', 'version'];

    /** 契约 0.2.0 放行的目标平台白名单（未知值仍拒） */
    public const TARGET_PLATFORMS = ['site-static', 'ai-cms'];

    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const VERSION_PATTERN = '/^\d+\.\d+\.\d+$/';
    private const SHA256_PATTERN = '/^[a-f0-9]{64}$/i';

    /**
     * 校验整包 sha256
     *
     * @return array{package_path:string,sha256:string,size:int}
     */
    public function verifyPackage(string $packagePath, string $expectedSha256): array
    {
        if (!preg_match(self::SHA256_PATTERN, $expectedSha256)) {
            throw new ThemeGeneratorException('E-TG-011', '整包 sha256 格式非法（需 64 位十六进制）');
        }

        if (!is_file($packagePath)) {
            throw new ThemeGeneratorException('E-TG-001', '安装包不存在：' . $packagePath);
        }

        $actual = hash_file('sha256', $packagePath);
        if ($actual === false) {
            throw new ThemeGeneratorException('E-TG-002', '安装包无法读取：' . $packagePath);
        }

        if (!hash_equals(strtolower($expectedSha256), $actual)) {
            throw new ThemeGeneratorException('E-TG-010', '整包 sha256 与回执不一致（期望 ' . strtolower($expectedSha256) . '，实际 ' . $actual . '）');
        }

        return [
            'package_path' => $packagePath,
            'sha256'       => $actual,
            'size'         => (int) filesize($packagePath),
        ];
    }

    /**
     * 读 manifest 并做契约校验；返回的数组只包含包内实际出现的字段，不注入默认值。
     */
    public function readManifest(string $packagePath): array
    {
        $raw = $this->readManifestRaw($packagePath);

        $data = json_decode($raw, true);
        if (!is_array($data) || $data === [] || array_is_list($data)) {
            throw new ThemeGeneratorException('E-TG-004', 'manifest.json 不是合法的 JSON 对象');
        }

        // 1) 契约外字段：一律拒
        foreach (array_keys($data) as $key) {
            if (!in_array($key, self::CONTRACT_FIELDS, true)) {
                throw new ThemeGeneratorException('E-TG-005', 'manifest 含契约外字段：' . $key);
            }
        }

        // 2) target_platform：白名单放行，未知值/非字符串仍拒
        if (array_key_exists('target_platform', $data)) {
            $platform = $data['target_platform'];
            if (!is_string($platform) || !in_array($platform, self::TARGET_PLATFORMS, true)) {
                $shown = is_scalar($platform) ? (string) $platform : gettype($platform);
                throw new ThemeGeneratorException('E-TG-020', '不支持的 target_platform：' . $shown);
            }
        }

        // 3) 必填字段
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ThemeGeneratorException('E-TG-006', 'manifest 缺少必填字段：' . $field);
            }
        }

        $this->assertFieldTypes($data);

        return $data;
    }

    /**
     * 从已解包的目录读 manifest（预览、回滚时用），校验规则与包内一致。
     */
    public function readManifestFromDir(string $themeDir): array
    {
        $path = rtrim($themeDir, '/\\') . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!is_file($path)) {
            throw new ThemeGeneratorException('E-TG-003', '主题目录缺少 manifest.json');
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || $data === [] || array_is_list($data)) {
            throw new ThemeGeneratorException('E-TG-004', 'manifest.json 不是合法的 JSON 对象');
        }

        foreach (array_keys($data) as $key) {
            if (!in_array($key, self::CONTRACT_FIELDS, true)) {
                throw new ThemeGeneratorException('E-TG-005', 'manifest 含契约外字段：' . $key);
            }
        }

        if (array_key_exists('target_platform', $data)) {
            $platform = $data['target_platform'];
            if (!is_string($platform) || !in_array($platform, self::TARGET_PLATFORMS, true)) {
                throw new ThemeGeneratorException('E-TG-020', '不支持的 target_platform');
            }
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ThemeGeneratorException('E-TG-006', 'manifest 缺少必填字段：' . $field);
            }
        }

        $this->assertFieldTypes($data);

        return $data;
    }

    /**
     * 解包到目标目录（含 zip 滑动路径防护），返回解出的相对路径列表。
     *
     * @return string[]
     */
    public function extractTo(string $packagePath, string $targetDir): array
    {
        $zip = $this->openZip($packagePath);

        try {
            $dir = rtrim($targetDir, '/\\');
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new ThemeGeneratorException('E-TG-018', '无法创建解包目录：' . $dir);
            }

            $extracted = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                $relative = $this->normalizeEntryName($name);
                if ($relative === '') {
                    continue;
                }

                $destination = $dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if (substr($name, -1) === '/') {
                    if (!is_dir($destination)) {
                        mkdir($destination, 0755, true);
                    }
                    continue;
                }

                $parent = dirname($destination);
                if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                    throw new ThemeGeneratorException('E-TG-018', '无法创建目录：' . $parent);
                }

                $stream = $zip->getStream($name);
                if ($stream === false) {
                    throw new ThemeGeneratorException('E-TG-002', '包内文件无法读取：' . $name);
                }

                $out = fopen($destination, 'wb');
                if ($out === false) {
                    fclose($stream);
                    throw new ThemeGeneratorException('E-TG-018', '无法写入文件：' . $destination);
                }

                stream_copy_to_stream($stream, $out);
                fclose($out);
                fclose($stream);

                $extracted[] = $relative;
            }

            return $extracted;
        } finally {
            $zip->close();
        }
    }

    /**
     * 打开 zip；不是 zip / 打不开统一报 E-TG-002。
     */
    public function openZip(string $packagePath): \ZipArchive
    {
        if (!is_file($packagePath)) {
            throw new ThemeGeneratorException('E-TG-001', '安装包不存在：' . $packagePath);
        }

        $zip = new \ZipArchive();
        $ok = $zip->open($packagePath, \ZipArchive::RDONLY);
        if ($ok !== true) {
            throw new ThemeGeneratorException('E-TG-002', '安装包无法打开（不是有效 zip）：' . $packagePath);
        }

        return $zip;
    }

    /**
     * 包内 manifest.json 原文
     */
    private function readManifestRaw(string $packagePath): string
    {
        $zip = $this->openZip($packagePath);

        try {
            $index = $zip->locateName('manifest.json', \ZipArchive::FL_NOCASE);
            if ($index === false) {
                throw new ThemeGeneratorException('E-TG-003', '包内缺少 manifest.json');
            }

            $raw = $zip->getFromIndex($index);
            if (!is_string($raw) || trim($raw) === '') {
                throw new ThemeGeneratorException('E-TG-004', 'manifest.json 内容为空或无法读取');
            }

            return $raw;
        } finally {
            $zip->close();
        }
    }

    /**
     * 字段类型与格式校验
     */
    private function assertFieldTypes(array $data): void
    {
        if (!is_string($data['name']) || trim($data['name']) === '') {
            throw new ThemeGeneratorException('E-TG-009', 'manifest.name 必须是非空字符串');
        }

        if (!is_string($data['slug']) || !preg_match(self::SLUG_PATTERN, $data['slug'])) {
            throw new ThemeGeneratorException('E-TG-007', 'manifest.slug 只能是小写字母、数字和连字符');
        }

        if (!is_string($data['version']) || !preg_match(self::VERSION_PATTERN, $data['version'])) {
            throw new ThemeGeneratorException('E-TG-008', 'manifest.version 必须是 x.y.z 三段式版本号');
        }

        if (array_key_exists('description', $data) && !is_string($data['description'])) {
            throw new ThemeGeneratorException('E-TG-009', 'manifest.description 必须是字符串');
        }

        if (array_key_exists('generated_at', $data) && !is_string($data['generated_at'])) {
            throw new ThemeGeneratorException('E-TG-009', 'manifest.generated_at 必须是字符串');
        }

        foreach (['templates', 'assets'] as $listField) {
            if (!array_key_exists($listField, $data)) {
                continue;
            }
            if (!is_array($data[$listField]) || !array_is_list($data[$listField])) {
                throw new ThemeGeneratorException('E-TG-009', 'manifest.' . $listField . ' 必须是字符串数组');
            }
            foreach ($data[$listField] as $item) {
                if (!is_string($item)) {
                    throw new ThemeGeneratorException('E-TG-009', 'manifest.' . $listField . ' 必须是字符串数组');
                }
            }
        }
    }

    /**
     * 归一化并防护 zip 内条目名（拒绝绝对路径与 .. 越界）
     */
    private function normalizeEntryName(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $trimmed = ltrim($name, '/');

        if ($trimmed === '') {
            return '';
        }

        foreach (explode('/', $trimmed) as $segment) {
            if ($segment === '..') {
                throw new ThemeGeneratorException('E-TG-014', '包内含越界路径：' . $name);
            }
        }

        return $trimmed;
    }
}
