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
use app\common\service\theme_generator\BtpkgAdapterService;
use app\common\service\theme_generator\ThemeBundleValidator;
use app\common\service\theme_generator\ThemeGeneratorException;
use app\common\service\theme_generator\ThemeOriginRepository;
use app\common\service\theme_generator\ThemePreviewRenderer;
use think\facade\Config;
use think\facade\Log;

/**
 * AI-THEME 适配器后台控制器
 *
 * 只做四件事：读包并验包、落盘到 public/skin/themes/<slug>、登记 i8j_theme_info、预览与回滚。
 * 不接管前台：不写 template/themes/，不改 frontend_theme。
 * 总开关见 config/theme_generator.php，关闭时所有动作 404。
 */
class ThemeGeneratorController extends AdminBaseController
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const RECEIPT_FILE = '.ai-theme-install.json';

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
            $target = $this->themesRoot() . DIRECTORY_SEPARATOR . $slug;
            $previous = $target . '@prev';

            $staging = runtime_path() . 'temp' . DIRECTORY_SEPARATOR . 'tg-' . bin2hex(random_bytes(6));
            $adapter->extractTo($packagePath, $staging);

            if (!is_dir($this->themesRoot()) && !mkdir($this->themesRoot(), 0755, true) && !is_dir($this->themesRoot())) {
                throw new ThemeGeneratorException('E-TG-018', '无法创建主题目录：' . $this->themesRoot());
            }

            if (is_dir($previous)) {
                $this->removeDirectory($previous);
            }

            if (is_dir($target) && !rename($target, $previous)) {
                throw new ThemeGeneratorException('E-TG-018', '无法备份现有主题目录：' . $target);
            }

            if (!rename($staging, $target)) {
                throw new ThemeGeneratorException('E-TG-018', '无法落盘主题目录：' . $target);
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
                $target . DIRECTORY_SEPARATOR . self::RECEIPT_FILE,
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
                'target'       => $target,
                'prev'         => is_dir($previous) ? $previous : null,
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

        $target = $this->themesRoot() . DIRECTORY_SEPARATOR . $slug;
        $previous = $target . '@prev';

        if (!is_dir($previous)) {
            return $this->fail('E-TG-016', '没有可回滚的上一版本：' . $slug);
        }

        if (!is_dir($target)) {
            return $this->fail('E-TG-016', '当前主题目录不存在：' . $slug);
        }

        $snapshot = $this->readReceipt($previous);

        $swap = $target . '@rollback-' . bin2hex(random_bytes(4));

        try {
            if (!rename($target, $swap)) {
                throw new ThemeGeneratorException('E-TG-018', '回滚失败：无法移出当前版本');
            }

            if (!rename($previous, $target)) {
                rename($swap, $target);
                throw new ThemeGeneratorException('E-TG-018', '回滚失败：无法切回上一版本');
            }

            if (!rename($swap, $previous)) {
                throw new ThemeGeneratorException('E-TG-018', '回滚失败：无法保留回滚前的版本');
            }

            (new ThemeOriginRepository())->restore($slug, $snapshot + [
                'source' => ThemeOriginRepository::SOURCE,
            ]);

            return $this->success('回滚完成', [
                'slug'    => $slug,
                'version' => (string) ($snapshot['version'] ?? ''),
                'prev'    => $previous,
            ]);
        } catch (ThemeGeneratorException $e) {
            if (is_dir($swap) && !is_dir($target)) {
                rename($swap, $target);
            }
            return $this->fail($e->errorCode(), $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('theme_generator rollback failed: ' . $e->getMessage());
            if (is_dir($swap) && !is_dir($target)) {
                rename($swap, $target);
            }
            return $this->fail('E-TG-019', '回滚失败：' . $e->getMessage());
        }
    }

    /**
     * 预览：沙箱 iframe 渲染包内真实 HTML
     */
    public function preview()
    {
        $this->guard();

        $slug = trim((string) $this->request->get('slug', ''));
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            abort(404, '主题标识非法');
        }

        $themeDir = $this->themesRoot() . DIRECTORY_SEPARATOR . $slug;
        if (!is_dir($themeDir)) {
            abort(404, '主题未安装：' . $slug);
        }

        try {
            $manifest = (new BtpkgAdapterService())->readManifestFromDir($themeDir);
            $srcdoc = (new ThemePreviewRenderer())->render($themeDir, $slug, $manifest);
        } catch (ThemeGeneratorException $e) {
            abort(404, $e->getMessage());
        }

        $receipt = $this->readReceipt($themeDir);

        $this->app->view->assign([
            'slug'           => $slug,
            'manifest'       => $manifest,
            'receipt'        => [
                'source'         => (string) ($receipt['source'] ?? ThemeOriginRepository::SOURCE),
                'task_id'        => (string) ($receipt['task_id'] ?? ''),
                'installed_at'   => (string) ($receipt['installed_at'] ?? ''),
                'package_sha256' => (string) ($receipt['package_sha256'] ?? ''),
            ],
            'preview_srcdoc' => $srcdoc,
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
     * 主题落盘根目录：public/skin/themes
     */
    private function themesRoot(): string
    {
        return public_path() . 'skin' . DIRECTORY_SEPARATOR . 'themes';
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
