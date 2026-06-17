<?php
/**
 * V2.9.23 C-1/C-2/C-4: 前台模板设计API控制器
 * 配色方案、布局切换、区块排序、AI配色生成
 */

namespace app\api\controller;

use app\common\service\TemplateCustomizeService;
use app\common\model\TemplateSectionConfig;
use think\facade\Cache;

class TemplateDesignController extends BaseController
{
    /**
     * V2.9.23 C-1: 获取预设配色方案
     */
    public function presetColors(): \think\Response
    {
        $service = new TemplateCustomizeService();
        $presets = $service->getPresetColors();
        return json(['code' => 0, 'data' => $presets]);
    }

    /**
     * V2.9.23 C-4: 根据行业推荐配色
     */
    public function recommendColors(): \think\Response
    {
        $industry = $this->request->get('industry', '');
        $service = new TemplateCustomizeService();
        $presets = $service->getPresetColors($industry);
        return json(['code' => 0, 'data' => $presets]);
    }

    /**
     * V2.9.23 C-1: AI生成配色方案
     */
    public function aiGenerateColor(): \think\Response
    {
        $description = $this->request->post('description', '');
        if (empty($description)) {
            return json(['code' => 1, 'msg' => '请输入配色描述']);
        }

        $service = new TemplateCustomizeService();
        $colors = $service->generateAIColors($description);

        if ($colors) {
            return json(['code' => 0, 'data' => $colors]);
        }

        // AI失败时返回随机预设配色
        $presets = $service->getPresetColors();
        if (!empty($presets)) {
            $random = $presets[array_rand($presets)];
            return json(['code' => 0, 'msg' => 'AI生成失败，已使用推荐配色', 'data' => $random['colors'] ?? []]);
        }

        return json(['code' => 1, 'msg' => '配色生成失败']);
    }

    /**
     * V2.9.23 C-1: 保存设计配置（配色+布局）
     */
    public function saveDesignConfig(): \think\Response
    {
        $memberId = (int) session('member.id');
        if ($memberId <= 0) {
            return json(['code' => 403, 'msg' => '请先登录']);
        }

        $themeSlug = $this->request->post('theme_slug', '');
        $colors = $this->request->post('colors/a', []);
        $layout = $this->request->post('layout/a', []);

        if (empty($themeSlug)) {
            return json(['code' => 1, 'msg' => '模板标识不能为空']);
        }

        $config = [];

        // 合并配色配置
        if (!empty($colors)) {
            $mapping = [
                'primary' => '--primary',
                'secondary' => '--secondary',
                'bg' => '--bg',
                'text' => '--text',
                'heading' => '--font-heading',
                'link' => '--primary',
                'accent' => '--accent',
            ];
            foreach ($mapping as $colorKey => $cssVar) {
                if (isset($colors[$colorKey])) {
                    $config[$cssVar] = $colors[$colorKey];
                }
            }
        }

        // 合并布局配置
        if (!empty($layout)) {
            if (isset($layout['sidebar_pos'])) {
                $config['--sidebar-pos'] = $layout['sidebar_pos'];
            }
            if (isset($layout['content_width'])) {
                $config['--content-width'] = $layout['content_width'];
            }
            if (isset($layout['radius'])) {
                $config['--radius'] = $layout['radius'];
            }
        }

        $service = new TemplateCustomizeService();
        $result = $service->saveUserConfig($memberId, $themeSlug, $config);

        if ($result) {
            return json(['code' => 0, 'msg' => '设计配置已保存']);
        }

        return json(['code' => 1, 'msg' => '保存失败']);
    }

    /**
     * V2.9.23 C-1: 获取布局方案
     */
    public function layoutPresets(): \think\Response
    {
        $service = new TemplateCustomizeService();
        return json(['code' => 0, 'data' => $service->getLayoutPresets()]);
    }

    /**
     * V2.9.23 C-2: 保存区块排序
     */
    public function saveSectionOrder(): \think\Response
    {
        $memberId = (int) session('member.id');
        if ($memberId <= 0) {
            return json(['code' => 403, 'msg' => '请先登录']);
        }

        $themeSlug = $this->request->post('theme_slug', '');
        $pageType = $this->request->post('page_type', 'index');
        $sections = $this->request->post('sections/a', []);

        if (empty($themeSlug) || empty($sections)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $config = TemplateSectionConfig::where('theme_slug', $themeSlug)
                ->where('member_id', $memberId)
                ->where('page_type', $pageType)
                ->find();

            if ($config) {
                $config->sections = json_encode($sections);
                $config->save();
            } else {
                TemplateSectionConfig::create([
                    'theme_slug' => $themeSlug,
                    'member_id' => $memberId,
                    'page_type' => $pageType,
                    'sections' => json_encode($sections),
                ]);
            }

            // 清除区块配置缓存
            Cache::tag('section_config')->clear();

            return json(['code' => 0, 'msg' => '区块排序已保存']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * V2.9.23 C-2: 获取区块配置
     */
    public function getSectionOrder(): \think\Response
    {
        $memberId = (int) session('member.id');
        $themeSlug = $this->request->get('theme_slug', '');
        $pageType = $this->request->get('page_type', 'index');

        if (empty($themeSlug) || $memberId <= 0) {
            return json(['code' => 0, 'data' => []]);
        }

        $cacheKey = 'section_' . $themeSlug . '_' . $memberId . '_' . $pageType;
        $sections = Cache::remember($cacheKey, function () use ($themeSlug, $memberId, $pageType) {
            $config = TemplateSectionConfig::where('theme_slug', $themeSlug)
                ->where('member_id', $memberId)
                ->where('page_type', $pageType)
                ->find();
            return $config ? json_decode($config->sections, true) : [];
        }, 3600);

        return json(['code' => 0, 'data' => $sections]);
    }
}
