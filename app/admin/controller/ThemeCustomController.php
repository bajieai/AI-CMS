<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\service\theme\ThemeCustomService;
use app\common\service\TemplateService;
use think\facade\Log;

/**
 * 主题定制面板API控制器 - V2.9.7 Phase 1
 *
 * 提供8条API路由：
 * - GET  defaults       获取主题默认定制参数
 * - GET  customization  获取当前定制数据
 * - POST save           保存定制数据
 * - POST activate       激活变体
 * - POST reset          重置为默认
 * - POST saveAs         另存为新变体
 * - GET  variants       获取变体列表
 * - GET  presets        获取字体/布局预设
 */
class ThemeCustomController extends AdminBaseController
{
    protected ThemeCustomService $customService;

    public function initialize(): void
    {
        parent::initialize();
        $this->customService = new ThemeCustomService();
    }

    /**
     * 获取主题默认定制参数（从theme.json读取design_tokens）
     * GET /admin/theme_custom/defaults?theme=xxx
     */
    public function defaults()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $defaults = $this->customService->getDefaults($themeId);
        $customization = $this->customService->getActiveCustomization($themeId);

        return json([
            'code' => 0,
            'data' => [
                'defaults'       => $defaults,
                'customization'  => $customization,
                'theme_id'       => $themeId,
            ],
        ]);
    }

    /**
     * 获取当前定制数据
     * GET /admin/theme_custom/customization?theme=xxx
     */
    public function customization()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $data = $this->customService->getActiveCustomization($themeId);

        return json([
            'code' => 0,
            'data' => $data,
        ]);
    }

    /**
     * 保存定制数据
     * POST /admin/theme_custom/save
     * Body: {theme: "xxx", variant: "default", data: {"--primary": "#xxx", ...}}
     */
    public function save()
    {
        $themeId = $this->request->post('theme', '');
        $variant = $this->request->post('variant', 'default');
        $data = $this->request->post('data', []);

        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        $result = $this->customService->saveCustomization($themeId, $data, $variant);

        return json($result);
    }

    /**
     * 激活变体
     * POST /admin/theme_custom/activate
     * Body: {theme: "xxx", variant: "default"}
     */
    public function activate()
    {
        $themeId = $this->request->post('theme', '');
        $variant = $this->request->post('variant', 'default');

        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $success = \app\common\model\ThemeCustomization::activateVariant($themeId, $variant);

        // 清除缓存
        \app\common\middleware\ThemeCustomMiddleware::clearCache($themeId);

        return json([
            'success' => $success,
            'message' => $success ? '变体已激活' : '变体不存在',
        ]);
    }

    /**
     * 重置为默认值
     * POST /admin/theme_custom/reset
     * Body: {theme: "xxx"}
     */
    public function reset()
    {
        $themeId = $this->request->post('theme', '');

        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $result = $this->customService->resetToDefault($themeId);

        return json($result);
    }

    /**
     * 另存为新变体
     * POST /admin/theme_custom/saveAs
     * Body: {theme: "xxx", name: "变体名"}
     */
    public function saveAs()
    {
        $themeId = $this->request->post('theme', '');
        $name = $this->request->post('name', '');

        if (empty($themeId) || empty($name)) {
            return json(['code' => 1, 'msg' => '缺少参数']);
        }

        $result = $this->customService->saveAsVariant($themeId, $name);

        return json($result);
    }

    /**
     * 获取变体列表
     * GET /admin/theme_custom/variants?theme=xxx
     */
    public function variants()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $variants = $this->customService->getVariants($themeId);

        return json([
            'code' => 0,
            'data' => $variants,
        ]);
    }

    /**
     * 获取预设列表（字体/布局）
     * GET /admin/theme_custom/presets
     */
    public function presets()
    {
        return json([
            'code' => 0,
            'data' => [
                'fonts'  => $this->customService->getFontPresets(),
                'layout' => $this->customService->getLayoutPresets(),
            ],
        ]);
    }

    /**
     * 生成预览CSS（不保存，仅用于预览）
     * POST /admin/theme_custom/preview
     * Body: {data: {"--primary": "#xxx", ...}}
     */
    public function preview()
    {
        $data = $this->request->post('data', []);
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        $css = $this->customService->generatePreviewCss($data);

        return json([
            'code' => 0,
            'data' => ['css' => $css],
        ]);
    }

    /**
     * 定制面板页面
     * GET /admin/theme_custom/panel?theme=xxx
     */
    public function panel()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return redirect('/admin/theme_market/index')->with('error', '缺少主题参数');
        }

        return view('theme_custom_panel', [
            'theme_id' => $themeId,
        ]);
    }

    /**
     * 上传Logo图片
     * POST /admin/theme_custom/uploadLogo
     */
    public function uploadLogo()
    {
        $themeId = $this->request->post('theme', '');
        $file = $this->request->file('file');

        if (empty($themeId) || empty($file)) {
            return json(['code' => 1, 'msg' => '缺少参数']);
        }

        try {
            // 验证文件
            $ext = strtolower($file->getOriginalExtension());
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                return json(['code' => 1, 'msg' => '仅支持图片格式']);
            }

            if ($file->getSize() > 2 * 1024 * 1024) {
                return json(['code' => 1, 'msg' => '图片不能超过2MB']);
            }

            // 保存到主题资源目录
            $saveDir = root_path() . 'public' . DIRECTORY_SEPARATOR . 'skin' .
                DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeId .
                DIRECTORY_SEPARATOR . 'pc' . DIRECTORY_SEPARATOR . 'images';

            if (!is_dir($saveDir)) {
                mkdir($saveDir, 0755, true);
            }

            $filename = 'logo_' . time() . '.' . $ext;
            $file->move($saveDir, $filename);

            $url = '/skin/themes/' . $themeId . '/pc/images/' . $filename;

            return json([
                'code' => 0,
                'data' => ['url' => $url],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '上传失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 导出主题（含定制数据）
     * GET /admin/theme_custom/export?theme=xxx
     */
    public function export()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $packageService = new \app\common\service\theme\ThemePackageService();
        $result = $packageService->exportTheme($themeId, true);

        if (!$result['success']) {
            return json(['code' => 1, 'msg' => $result['message']]);
        }

        // 提供下载
        $zipPath = $result['path'];
        if (!file_exists($zipPath)) {
            return json(['code' => 1, 'msg' => 'ZIP文件不存在']);
        }

        return download($zipPath, $themeId . '_custom.zip');
    }

    /**
     * 检查导入冲突
     * GET /admin/theme_custom/checkConflict?theme=xxx
     */
    public function checkConflict()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $packageService = new \app\common\service\theme\ThemePackageService();
        $result = $packageService->checkImportConflict($themeId);

        return json(['code' => 0, 'data' => $result]);
    }
}
