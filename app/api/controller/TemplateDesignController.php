<?php
/**
 * V2.9.23 C-1/C-2/C-4: 前台模板设计API控制器
 * 配色方案、布局切换、区块排序、AI配色生成
 */

namespace app\api\controller;

use app\common\service\TemplateCustomizeService;
use app\common\model\TemplateSectionConfig;
use app\common\model\TemplatePresetColor;
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
            Cache::clear();

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

    // ==================== V2.9.24 I-2: 自定义配色CRUD ====================

    /**
     * I-2: 获取用户自定义配色方案列表
     */
    public function customColors(): \think\Response
    {
        $memberId = (int) session('member.id');
        if ($memberId <= 0) {
            return json(['code' => 0, 'data' => []]);
        }

        $list = TemplatePresetColor::where('member_id', $memberId)
            ->where('is_system', 0)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->select();

        return json(['code' => 0, 'data' => $list]);
    }

    /**
     * I-2: 保存自定义配色方案
     */
    public function saveCustomColor(): \think\Response
    {
        $memberId = (int) session('member.id');
        if ($memberId <= 0) {
            return json(['code' => 403, 'msg' => '请先登录']);
        }

        $id = (int) $this->request->post('id', 0);
        $name = trim($this->request->post('name', ''));
        $colors = $this->request->post('colors/a', []);

        if (empty($name)) {
            return json(['code' => 1, 'msg' => '配色名称不能为空']);
        }
        if (empty($colors) || empty($colors['primary'])) {
            return json(['code' => 1, 'msg' => '配色数据不完整']);
        }

        $data = [
            'name' => $name,
            'colors' => $colors,
            'is_system' => 0,
            'member_id' => $memberId,
            'sort' => (int) $this->request->post('sort', 0),
        ];

        try {
            if ($id > 0) {
                $existing = TemplatePresetColor::where('id', $id)
                    ->where('member_id', $memberId)
                    ->where('is_system', 0)
                    ->find();
                if (!$existing) {
                    return json(['code' => 1, 'msg' => '配色方案不存在']);
                }
                $existing->save($data);
            } else {
                TemplatePresetColor::create($data);
            }
            return json(['code' => 0, 'msg' => '配色方案已保存']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * I-2: 删除自定义配色方案
     */
    public function deleteCustomColor(): \think\Response
    {
        $memberId = (int) session('member.id');
        if ($memberId <= 0) {
            return json(['code' => 403, 'msg' => '请先登录']);
        }

        $id = (int) $this->request->post('id', 0);
        if ($id <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $color = TemplatePresetColor::where('id', $id)
            ->where('member_id', $memberId)
            ->where('is_system', 0)
            ->find();

        if (!$color) {
            return json(['code' => 1, 'msg' => '配色方案不存在']);
        }

        $color->delete();
        return json(['code' => 0, 'msg' => '已删除']);
    }

    // ==================== V2.9.24 I-5: 区块内容编辑 ====================

    /**
     * I-5: 保存区块内容（标题/描述文字的实时编辑）
     */
    public function saveSectionContent(): \think\Response
    {
        $memberId = (int) session('member.id');
        if ($memberId <= 0) {
            return json(['code' => 403, 'msg' => '请先登录']);
        }

        $themeSlug = $this->request->post('theme_slug', '');
        $pageType = $this->request->post('page_type', 'index');
        $sectionId = $this->request->post('section_id', '');
        $content = $this->request->post('content/a', []);

        if (empty($themeSlug) || empty($sectionId)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        try {
            $config = TemplateSectionConfig::where('theme_slug', $themeSlug)
                ->where('member_id', $memberId)
                ->where('page_type', $pageType)
                ->find();

            if ($config) {
                $sections = json_decode($config->sections, true) ?: [];
            } else {
                $sections = [];
            }

            // 更新指定区块的内容
            $found = false;
            foreach ($sections as &$section) {
                if ($section['id'] === $sectionId) {
                    $section['content'] = $content;
                    $found = true;
                    break;
                }
            }
            unset($section);

            if (!$found) {
                $sections[] = ['id' => $sectionId, 'name' => '', 'visible' => true, 'sort' => count($sections), 'content' => $content];
            }

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

            Cache::clear();
            return json(['code' => 0, 'msg' => '区块内容已保存']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * I-5: 获取区块内容
     */
    public function getSectionContent(): \think\Response
    {
        $memberId = (int) session('member.id');
        $themeSlug = $this->request->get('theme_slug', '');
        $pageType = $this->request->get('page_type', 'index');
        $sectionId = $this->request->get('section_id', '');

        if (empty($themeSlug) || $memberId <= 0) {
            return json(['code' => 0, 'data' => null]);
        }

        $config = TemplateSectionConfig::where('theme_slug', $themeSlug)
            ->where('member_id', $memberId)
            ->where('page_type', $pageType)
            ->find();

        if (!$config) {
            return json(['code' => 0, 'data' => null]);
        }

        $sections = json_decode($config->sections, true) ?: [];
        foreach ($sections as $section) {
            if ($section['id'] === $sectionId) {
                return json(['code' => 0, 'data' => $section['content'] ?? null]);
            }
        }

        return json(['code' => 0, 'data' => null]);
    }
}
