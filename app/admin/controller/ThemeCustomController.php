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
     * V2.9.8 C-1: 获取配色预设（系统+theme.json）
     * GET /admin/theme_custom/colorPresets?theme=xxx
     */
    public function colorPresets()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }
        return json([
            'code' => 0,
            'data' => $this->customService->getAvailablePresets($themeId),
        ]);
    }

    /**
     * V2.9.8 B-1: 智能推荐预设
     * GET /admin/theme_custom/recommendPreset?theme=xxx
     */
    public function recommendPreset()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }
        $recommended = $this->customService->recommendPreset($themeId);
        return json([
            'code' => 0,
            'data' => ['recommended_preset' => $recommended],
        ]);
    }

    /**
     * V2.9.8 C-2: 获取模板默认CSS变量值（恢复默认用）
     * GET /admin/theme_custom/defaultVars?theme=xxx
     */
    public function defaultVars()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }
        $defaults = $this->customService->getDefaultVars($themeId);
        return json([
            'code' => 0,
            'data' => ['default_vars' => $defaults],
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
     * V2.9.8 C-2: 导出预览——分析修改字段
     * GET /admin/theme_custom/previewExport?theme=xxx
     */
    public function previewExport()
    {
        $themeId = $this->request->param('theme', '');
        if (empty($themeId)) {
            return json(['code' => 1, 'msg' => '缺少theme参数']);
        }

        $custom = $this->customService->getActiveCustomization($themeId);
        if (empty($custom)) {
            return json(['code' => 0, 'data' => ['has_customization' => false, 'message' => '无定制数据']]);
        }

        $variants = $this->customService->getVariants($themeId);
        $activeVariant = '';
        foreach ($variants as $v) {
            if ($v['is_active'] ?? false) {
                $activeVariant = $v['variant_name'];
                break;
            }
        }

        $modified = $this->analyzeModifiedFields($custom);
        $summary = $this->buildExportSummary($modified);

        return json(['code' => 0, 'data' => [
            'has_customization' => true,
            'theme_name' => $themeId,
            'variant_count' => count($variants),
            'active_variant' => $activeVariant,
            'modified_fields' => $modified,
            'summary' => $summary,
        ]]);
    }

    /**
     * 分析哪些字段被修改了
     */
    protected function analyzeModifiedFields(array $custom): array
    {
        $modified = ['colors' => [], 'fonts' => [], 'layout' => [], 'logo' => false];
        $colorVars = ['--primary','--secondary','--accent','--bg','--bg-secondary','--text','--text-secondary','--border','--btn-primary-bg','--btn-primary-hover'];
        $fontVars = ['--font-heading','--font-body'];
        $layoutVars = ['--sidebar-pos','--content-width','--header-style'];

        foreach ($custom as $key => $val) {
            if (empty($val)) continue;
            if (in_array($key, $colorVars, true)) $modified['colors'][] = $key;
            elseif (in_array($key, $fontVars, true)) $modified['fonts'][] = $key;
            elseif (in_array($key, $layoutVars, true)) $modified['layout'][] = $key;
            elseif ($key === '--logo-url') $modified['logo'] = true;
            elseif ($key === '--logo-max-height') $modified['logo'] = true;
        }
        return $modified;
    }

    /**
     * 构建导出摘要文本
     */
    protected function buildExportSummary(array $modified): string
    {
        $parts = [];
        if (!empty($modified['colors'])) {
            $parts[] = '颜色调整(' . count($modified['colors']) . '项)';
        }
        if (!empty($modified['fonts'])) {
            $parts[] = '字体变更(' . count($modified['fonts']) . '项)';
        }
        if (!empty($modified['layout'])) {
            $parts[] = '布局调整(' . count($modified['layout']) . '项)';
        }
        if ($modified['logo']) {
            $parts[] = 'Logo已上传';
        }
        return empty($parts) ? '无定制修改' : implode(' + ', $parts);
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
