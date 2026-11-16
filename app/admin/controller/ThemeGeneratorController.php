<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\middleware\ThemePreviewMiddleware;
use app\common\service\theme_generator\BtpkgAdapterService;
use app\common\service\theme_generator\ThemeBundleValidator;
use app\common\service\theme_generator\ThemeGeneratorException;
use app\common\service\theme_generator\ThemeOriginRepository;
use think\facade\Config;
use think\facade\Log;

/**
 * AI-THEME 适配器后台控制器
 *
 * 只做四件事：读包并验包、按 CMS 原生形态落盘、登记 i8j_theme_info、预览与回滚。
 * 落点：模板与 theme.json 进 template/themes/<slug>/，静态资源进 public/skin/themes/<slug>/assets/。
 * 补齐：与引擎 ai_cms.py::install() 同源同规则，从 default 主题补 <slug>/pc/ 与 skin/<slug>/pc/（只补缺）。
 * 预览交给 CMS 前台按 X-Preview-Theme 通路渲染；适配器不自行解释模板。不改 frontend_theme。
 * 总开关见 config/theme_generator.php，关闭时所有动作 404。
 */
class ThemeGeneratorController extends AdminBaseController
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const RECEIPT_FILE = '.ai-theme-install.json';

    /** 补齐来源：与引擎 ai_cms.py::install(default_theme) 同一源 */
    private const DEFAULT_THEME = 'default';

    /**
     * 列表页
     */
    public function index()
    {
        $this->guard();

        $repository = new ThemeOriginRepository();

        $this->app->view->assign([
            'list' => $repository->listInstalled(),
        ]);

        return $this->app->view->fetch('theme_generator/index');
    }

    /**
     * 装包：验 sha256 → 读 manifest → 体量校验 → 解包 → 目录对换 → 写回执 → 登记
     */
    public function install(): \think\Response
    {
        $this->guard();

        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $packagePath = trim((string) $this->request->post('package_path', ''));
        $sha256      = strtolower(trim((string) $this->request->post('package_sha256', '')));
        $taskId      = trim((string) $this->request->post('task_id', ''));

        if ($packagePath === '') {
            return $this->error('请提供安装包路径');
        }

        if ($sha256 === '') {
            return $this->error('请提供整包 sha256');
        }

        $staging = '';

        try {
            $adapter = new BtpkgAdapterService();
            $adapter->verifyPackage($packagePath, $sha256);
            $manifest = $adapter->readManifest($packagePath);

            $zip = $adapter->openZip($packagePath);
            try {
                (new ThemeBundleValidator())->validateZip($zip);
            } finally {
                $zip->close();
            }

            $slug = (string) $manifest['slug'];

            $staging = runtime_path() . 'temp' . DIRECTORY_SEPARATOR . 'tg-' . bin2hex(random_bytes(6));
            $adapter->extractTo($packagePath, $staging);

            $packageTemplate = $staging . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $slug;
            $packageSkin     = $staging . DIRECTORY_SEPARATOR . 'skin' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $slug;
            $this->assertPackageLayout($packageTemplate, $packageSkin, $slug);

            $templateTarget = $this->templateThemesRoot() . DIRECTORY_SEPARATOR . $slug;
            $templatePrev   = $templateTarget . '@prev';
            $skinTarget     = $this->skinThemesRoot() . DIRECTORY_SEPARATOR . $slug;
            $skinPrev       = $skinTarget . '@prev';

            $this->ensureDirectory($this->templateThemesRoot());
            $this->ensureDirectory($this->skinThemesRoot());

            // 同版本重装不得顶掉真正的上一版：@prev 保持不动，用临时名换出当前版本。
            $currentVersion = $this->installedVersion($templateTarget, $skinTarget);
            $sameVersion    = $currentVersion !== '' && $currentVersion === (string) $manifest['version'];
            $swapSuffix     = $sameVersion ? '@reinstall-' . bin2hex(random_bytes(4)) : '@prev';
            $templateSwap   = $templateTarget . $swapSuffix;
            $skinSwap       = $skinTarget . $swapSuffix;

            if (!$sameVersion) {
                if (is_dir($templatePrev)) {
                    $this->removeDirectory($templatePrev);
                }
                if (is_dir($skinPrev)) {
                    $this->removeDirectory($skinPrev);
                }
            }

            $templateBackedUp = is_dir($templateTarget) && rename($templateTarget, $templateSwap);
            if (is_dir($templateTarget)) {
                throw new ThemeGeneratorException('E-TG-018', '无法备份现有模板目录：' . $templateTarget);
            }

            $skinBackedUp = is_dir($skinTarget) && rename($skinTarget, $skinSwap);
            if (is_dir($skinTarget)) {
                throw new ThemeGeneratorException('E-TG-018', '无法备份现有皮肤目录：' . $skinTarget);
            }

            try {
                if (!rename($packageTemplate, $templateTarget)) {
                    throw new ThemeGeneratorException('E-TG-018', '无法落盘模板目录：' . $templateTarget);
                }

                $this->ensureDirectory($skinTarget);
                if (!rename($packageSkin . DIRECTORY_SEPARATOR . 'assets', $skinTarget . DIRECTORY_SEPARATOR . 'assets')) {
                    throw new ThemeGeneratorException('E-TG-018', '无法落盘皮肤资源：' . $skinTarget . DIRECTORY_SEPARATOR . 'assets');
                }

                // 契约补齐：与引擎 ai_cms.py::install() 同源同规则（default → <slug>，只补缺、不覆盖）
                // 模板侧补 <slug>/pc/，皮肤侧补 public/skin/themes/<slug>/pc/（layout 的 {$skin}css/... 依赖后者）。
                $filledTemplates = $this->copyMissing(
                    $this->templateThemesRoot() . DIRECTORY_SEPARATOR . self::DEFAULT_THEME . DIRECTORY_SEPARATOR . 'pc',
                    $templateTarget . DIRECTORY_SEPARATOR . 'pc'
                );
                $filledSkins = $this->copyMissing(
                    $this->skinThemesRoot() . DIRECTORY_SEPARATOR . self::DEFAULT_THEME . DIRECTORY_SEPARATOR . 'pc',
                    $skinTarget . DIRECTORY_SEPARATOR . 'pc'
                );
            } catch (\Throwable $e) {
                if (is_dir($templateTarget)) {
                    $this->removeDirectory($templateTarget);
                }
                if ($templateBackedUp && is_dir($templateSwap)) {
                    rename($templateSwap, $templateTarget);
                }
                if (is_dir($skinTarget)) {
                    $this->removeDirectory($skinTarget);
                }
                if ($skinBackedUp && is_dir($skinSwap)) {
                    rename($skinSwap, $skinTarget);
                }
                throw $e;
            }

            // 同版本重装：临时换出的旧目录用完即删，@prev 未被触碰
            if ($sameVersion) {
                if (is_dir($templateSwap)) {
                    $this->removeDirectory($templateSwap);
                }
                if (is_dir($skinSwap)) {
                    $this->removeDirectory($skinSwap);
                }
            }
            $staging = '';

            $installedAt = date('Y-m-d H:i:s');
            $receipt = [
                'source'         => ThemeOriginRepository::SOURCE,
                'task_id'        => $taskId,
                'installed_at'   => $installedAt,
                'package_sha256' => $sha256,
                'version'        => (string) $manifest['version'],
                'manifest'       => $manifest,
            ];

            file_put_contents(
                $skinTarget . DIRECTORY_SEPARATOR . self::RECEIPT_FILE,
                (string) json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );

            $row = (new ThemeOriginRepository())->register($slug, $manifest + [
                'task_id'        => $taskId,
                'installed_at'   => $installedAt,
                'package_sha256' => $sha256,
            ]);

            return $this->success('安装完成', [
                'success'      => true,
                'slug'         => $slug,
                'version'      => (string) $manifest['version'],
                'sha256'       => $sha256,
                'task_id'      => $taskId,
                'installed_at' => $installedAt,
                'template_dir' => 'template/themes/' . $slug,
                'skin_dir'     => 'public/skin/themes/' . $slug,
                'prev'         => is_dir($templatePrev) ? 'template/themes/' . $slug . '@prev' : null,
                'same_version_reinstall'    => $sameVersion,
                'filled_from_default'       => $filledTemplates,
                'skin_filled_from_default'  => $filledSkins,
                'theme_id'     => (int) ($row['id'] ?? 0),
            ]);
        } catch (ThemeGeneratorException $e) {
            return $this->fail($e->errorCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('theme_generator install failed: ' . $e->getMessage());
            return $this->fail('E-TG-019', '安装失败：' . $e->getMessage());
        } finally {
            if ($staging !== '') {
                $this->removeDirectory($staging);
            }
        }
    }

    /**
     * 回滚：<slug> 与 <slug>@prev 目录对换，并按上一版回执复原登记
     */
    public function rollback(): \think\Response
    {
        $this->guard();

        if (!$this->request->isPost()) {
            return $this->error('请求方式错误');
        }

        $slug = trim((string) $this->request->post('slug', ''));
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            return $this->fail('E-TG-007', '主题标识非法');
        }

        $templateTarget = $this->templateThemesRoot() . DIRECTORY_SEPARATOR . $slug;
        $templatePrev   = $templateTarget . '@prev';
        $skinTarget     = $this->skinThemesRoot() . DIRECTORY_SEPARATOR . $slug;
        $skinPrev       = $skinTarget . '@prev';

        if (!is_dir($templatePrev) || !is_dir($skinPrev)) {
            return $this->fail('E-TG-016', '没有可回滚的上一版本：' . $slug);
        }

        if (!is_dir($templateTarget) || !is_dir($skinTarget)) {
            return $this->fail('E-TG-016', '当前主题目录不完整：' . $slug);
        }

        $snapshot = $this->readReceipt($skinPrev);

        try {
            $this->swapWithPrev($templateTarget, $templatePrev);

            try {
                $this->swapWithPrev($skinTarget, $skinPrev);
            } catch (\Throwable $e) {
                $this->swapWithPrev($templateTarget, $templatePrev);
                throw $e;
            }

            // 回执里主题名在 manifest.name 下；取不到时保留当前登记值，不能把 name 清空
            $restore = $snapshot + ['source' => ThemeOriginRepository::SOURCE];
            $restoreName = (string) ($snapshot['manifest']['name'] ?? $snapshot['name'] ?? '');
            if ($restoreName === '') {
                $current = (new ThemeOriginRepository())->findBySlug($slug);
                $restoreName = (string) ($current['name'] ?? $slug);
            }
            $restore['name'] = $restoreName;

            (new ThemeOriginRepository())->restore($slug, $restore);

            return $this->success('回滚完成', [
                'slug'         => $slug,
                'version'      => (string) ($snapshot['version'] ?? ''),
                'template_dir' => 'template/themes/' . $slug,
                'skin_dir'     => 'public/skin/themes/' . $slug,
                'prev'         => 'template/themes/' . $slug . '@prev',
            ]);
        } catch (ThemeGeneratorException $e) {
            return $this->fail($e->errorCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('theme_generator rollback failed: ' . $e->getMessage());
            return $this->fail('E-TG-019', '回滚失败：' . $e->getMessage());
        }
    }

    /**
     * 预览：交由 CMS 前台按 X-Preview-Theme 通路渲染已安装主题
     */
    public function preview()
    {
        $this->guard();

        $slug = trim((string) $this->request->get('slug', ''));
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            abort(404, '主题标识非法');
        }

        $themeDir = $this->templateThemesRoot() . DIRECTORY_SEPARATOR . $slug;
        if (!is_dir($themeDir)) {
            abort(404, '主题未安装：' . $slug);
        }

        $configFile = $themeDir . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($configFile)) {
            abort(404, '主题缺少 theme.json：' . $slug);
        }

        $themeConfig = json_decode((string) file_get_contents($configFile), true);
        $themeConfig = is_array($themeConfig) ? $themeConfig : [];

        $receipt = $this->readReceipt($this->skinThemesRoot() . DIRECTORY_SEPARATOR . $slug);

        $manifest = [
            'name'        => (string) ($themeConfig['name'] ?? ($receipt['manifest']['name'] ?? $slug)),
            'slug'        => $slug,
            'version'     => (string) ($themeConfig['version'] ?? ($receipt['version'] ?? '')),
            'description' => (string) ($themeConfig['description'] ?? ''),
        ];

        $hash = ThemePreviewMiddleware::generateHash($slug);

        $this->app->view->assign([
            'slug'        => $slug,
            'manifest'    => $manifest,
            'receipt'     => [
                'source'         => (string) ($receipt['source'] ?? ThemeOriginRepository::SOURCE),
                'task_id'        => (string) ($receipt['task_id'] ?? ''),
                'installed_at'   => (string) ($receipt['installed_at'] ?? ''),
                'package_sha256' => (string) ($receipt['package_sha256'] ?? ''),
            ],
            'preview_url' => (string) url('/') . '?preview=' . $hash,
            'preview_hash' => $hash,
        ]);

        return $this->app->view->fetch('theme_generator/preview');
    }

    /**
     * 总开关：关闭时统一 404（与菜单过滤配合）
     */
    private function guard(): void
    {
        if (!(bool) Config::get('theme_generator.enabled', false)) {
            abort(404, 'AI 主题安装功能未启用');
        }
    }

    /**
     * CMS 模板落盘根目录：template/themes
     */
    private function templateThemesRoot(): string
    {
        return root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes';
    }

    /**
     * CMS 皮肤落盘根目录：public/skin/themes
     */
    private function skinThemesRoot(): string
    {
        return public_path() . 'skin' . DIRECTORY_SEPARATOR . 'themes';
    }

    /**
     * 当前已安装版本：优先读皮肤目录回执，其次读模板 theme.json
     */
    private function installedVersion(string $templateTarget, string $skinTarget): string
    {
        $receipt = $this->readReceipt($skinTarget);
        if (!empty($receipt['version'])) {
            return (string) $receipt['version'];
        }

        $configFile = $templateTarget . DIRECTORY_SEPARATOR . 'theme.json';
        if (is_file($configFile)) {
            $config = json_decode((string) file_get_contents($configFile), true);
            if (is_array($config) && !empty($config['version'])) {
                return (string) $config['version'];
            }
        }

        return '';
    }

    /**
     * 补齐缺失文件：与引擎 ai_cms.py::_copy_missing 同一规则——只补缺、不覆盖，目录按需创建
     *
     * @return string[] 实际补齐的相对路径
     */
    private function copyMissing(string $source, string $target): array
    {
        if (!is_dir($source)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        $copied = [];
        foreach ($files as $from) {
            $relative = str_replace('\\', '/', substr($from, strlen($source) + 1));
            $to = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if (file_exists($to)) {
                continue;
            }

            $this->ensureDirectory(dirname($to));
            if (!@copy($from, $to)) {
                throw new ThemeGeneratorException('E-TG-018', '补齐文件失败：' . $relative);
            }
            @touch($to, (int) filemtime($from));
            $copied[] = $relative;
        }

        return $copied;
    }

    /**
     * 校验包内是否为 CMS 原生形态：template/themes/<slug>/theme.json + pc/index.html + skin/themes/<slug>/assets
     */
    private function assertPackageLayout(string $packageTemplate, string $packageSkin, string $slug): void
    {
        if (!is_dir($packageTemplate) || !is_file($packageTemplate . DIRECTORY_SEPARATOR . 'theme.json')) {
            throw new ThemeGeneratorException('E-TG-015', '包内缺少 template/themes/' . $slug . '/theme.json');
        }

        if (!is_dir($packageTemplate . DIRECTORY_SEPARATOR . 'pc') || !is_file($packageTemplate . DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR . 'index.html')) {
            throw new ThemeGeneratorException('E-TG-015', '包内缺少 template/themes/' . $slug . '/pc/index.html');
        }

        if (!is_dir($packageSkin . DIRECTORY_SEPARATOR . 'assets')) {
            throw new ThemeGeneratorException('E-TG-015', '包内缺少 skin/themes/' . $slug . '/assets/');
        }
    }

    /**
     * 目录不存在则创建；失败报 E-TG-018
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new ThemeGeneratorException('E-TG-018', '无法创建目录：' . $dir);
        }
    }

    /**
     * 回滚用目录对换：target ↔ prev，中间态保留一份完整目录
     */
    private function swapWithPrev(string $target, string $prev): void
    {
        $swap = $target . '@rollback-' . bin2hex(random_bytes(4));

        if (!rename($target, $swap)) {
            throw new ThemeGeneratorException('E-TG-018', '回滚失败：无法移出当前版本');
        }

        if (!rename($prev, $target)) {
            rename($swap, $target);
            throw new ThemeGeneratorException('E-TG-018', '回滚失败：无法切回上一版本');
        }

        if (!rename($swap, $prev)) {
            throw new ThemeGeneratorException('E-TG-018', '回滚失败：无法保留回滚前的版本');
        }
    }

    /**
     * 读安装回执；不存在返回空数组
     */
    private function readReceipt(string $dir): array
    {
        $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . self::RECEIPT_FILE;
        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * 统一失败回执：保留稳定错误码
     */
    private function fail(string $errorCode, string $message): \think\Response
    {
        return json([
            'code'       => 1,
            'msg'        => $message,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * 递归删除目录（仅用于本适配器自己的临时目录与 @prev 备份）
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
