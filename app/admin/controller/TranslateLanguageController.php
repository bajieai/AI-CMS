<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Setting;
use think\facade\Config;

/**
 * V2.9.17 M-2: 翻译语言管理控制器
 *
 * 后台翻译语言多选管理界面
 * 路径：/admin/translate/languages
 */
class TranslateLanguageController extends AdminBaseController
{
    public function index()
    {
        $languageList = $this->buildLanguageList();
        $savedOrder = Setting::get('translate.language_order');
        $languageOrder = !empty($savedOrder) ? json_decode($savedOrder, true) : [];

        if (!empty($languageOrder)) {
            $ordered = [];
            foreach ($languageOrder as $code) {
                foreach ($languageList as $lang) {
                    if ($lang['code'] === $code) {
                        $ordered[] = $lang;
                        break;
                    }
                }
            }
            foreach ($languageList as $lang) {
                if (!in_array($lang['code'], $languageOrder, true)) {
                    $ordered[] = $lang;
                }
            }
            $languageList = $ordered;
        }

        $this->assign('languages', $languageList);
        $this->assign('total_count', count($languageList));
        $this->assign('enabled_count', count(array_filter($languageList, fn($l) => $l['enabled'])));
        return $this->view('/translate_languages');
    }

    public function list()
    {
        return $this->success('ok', $this->buildLanguageList());
    }

    public function toggle()
    {
        $code = $this->request->post('code', '');
        $enabled = (bool) $this->request->post('enabled', false);

        if (empty($code)) {
            return $this->error('缺少语言代码');
        }

        $allLanguages = Config::get('ai.translate.languages', []);
        if (!isset($allLanguages[$code])) {
            return $this->error("无效的语言代码: {$code}");
        }

        $enabledLanguages = $this->getEnabledLanguages();
        if ($enabled && !in_array($code, $enabledLanguages, true)) {
            $enabledLanguages[] = $code;
        } elseif (!$enabled && in_array($code, $enabledLanguages, true)) {
            $enabledLanguages = array_values(array_filter($enabledLanguages, fn($c) => $c !== $code));
        }

        if (empty($enabledLanguages)) {
            return $this->error('至少需要启用一种语言');
        }

        Setting::set('translate.enabled_languages', json_encode($enabledLanguages));
        cache('translate_enabled_languages', null);

        return $this->success(($enabled ? '已启用' : '已禁用') . "语言: {$code}");
    }

    public function batch()
    {
        $action = $this->request->post('action', '');
        $languages = $this->request->post('languages/a', []);
        $allLanguages = Config::get('ai.translate.languages', []);

        switch ($action) {
            case 'enable_all':
                $enabledLanguages = array_keys($allLanguages);
                break;
            case 'disable_all':
                return $this->error('至少需要启用一种语言');
            case 'select':
                if (empty($languages)) {
                    return $this->error('至少需要启用一种语言');
                }
                foreach ($languages as $code) {
                    if (!isset($allLanguages[$code])) {
                        return $this->error("无效的语言代码: {$code}");
                    }
                }
                $enabledLanguages = $languages;
                break;
            default:
                return $this->error('无效的操作类型');
        }

        Setting::set('translate.enabled_languages', json_encode($enabledLanguages));
        cache('translate_enabled_languages', null);
        return $this->success('批量操作成功');
    }

    public function sort()
    {
        $order = $this->request->post('order/a', []);
        if (empty($order)) {
            return $this->error('排序数据不能为空');
        }
        $allLanguages = Config::get('ai.translate.languages', []);
        foreach ($order as $code) {
            if (!isset($allLanguages[$code])) {
                return $this->error("无效的语言代码: {$code}");
            }
        }
        Setting::set('translate.language_order', json_encode($order));
        cache('translate_language_order', null);
        return $this->success('排序已保存');
    }

    public function custom()
    {
        $code = trim($this->request->post('code', ''));
        $name = trim($this->request->post('name', ''));
        $native = trim($this->request->post('native', ''));
        $flag = trim($this->request->post('flag', ''));
        $direction = $this->request->post('direction', 'ltr');

        if (empty($code) || empty($name)) {
            return $this->error('语言代码和名称不能为空');
        }
        if (!preg_match('/^[a-z]{2}(-[a-z]{2,4})?$/', $code)) {
            return $this->error('语言代码格式不正确（应为 2-5位字母，如 zh-cn）');
        }
        $allLanguages = Config::get('ai.translate.languages', []);
        if (isset($allLanguages[$code])) {
            return $this->error("语言代码 {$code} 已存在");
        }

        $newLang = [
            'name' => $name,
            'native' => $native ?: $name,
            'flag' => $flag ?: '🔄',
            'direction' => $direction,
            'enabled' => true,
        ];

        $customSaved = Setting::get('translate.custom_languages');
        $customLanguages = !empty($customSaved) ? json_decode($customSaved, true) : [];
        $customLanguages[$code] = $newLang;
        Setting::set('translate.custom_languages', json_encode($customLanguages));

        $enabledLanguages = $this->getEnabledLanguages();
        if (!in_array($code, $enabledLanguages, true)) {
            $enabledLanguages[] = $code;
            Setting::set('translate.enabled_languages', json_encode($enabledLanguages));
        }

        cache('translate_enabled_languages', null);
        cache('translate_custom_languages', null);
        return $this->success("自定义语言 {$native} ({$name}) 已添加");
    }

    protected function buildLanguageList(): array
    {
        $allLanguages = Config::get('ai.translate.languages', []);
        $customSaved = Setting::get('translate.custom_languages');
        if (!empty($customSaved)) {
            $custom = json_decode($customSaved, true) ?: [];
            $allLanguages = array_merge($allLanguages, $custom);
        }
        $enabledLanguages = $this->getEnabledLanguages();
        $languageList = [];
        foreach ($allLanguages as $code => $meta) {
            $languageList[] = [
                'code'      => $code,
                'name'      => $meta['name'] ?? '',
                'native'    => $meta['native'] ?? '',
                'flag'      => $meta['flag'] ?? '🌐',
                'direction' => $meta['direction'] ?? 'ltr',
                'enabled'   => in_array($code, $enabledLanguages, true),
            ];
        }
        return $languageList;
    }

    protected function getEnabledLanguages(): array
    {
        $saved = Setting::get('translate.enabled_languages');
        if (!empty($saved)) {
            return json_decode($saved, true) ?: [];
        }
        $allLanguages = Config::get('ai.translate.languages', []);
        $enabled = [];
        foreach ($allLanguages as $code => $meta) {
            if (!isset($meta['enabled']) || $meta['enabled'] !== false) {
                $enabled[] = $code;
            }
        }
        return $enabled;
    }
}
