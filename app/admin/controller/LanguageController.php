<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\model\Language;
use app\common\service\LanguageService;
use think\facade\Db;

/**
 * 多语言管理后台控制器 - V2.5新增
 */
class LanguageController extends AdminBaseController
{
    /**
     * 语言列表
     */
    public function index()
    {
        $list = Language::order('sort', 'asc')->select();

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list->toArray()]);
        }

        $this->assign('list', $list);
        return $this->view('/language_index');
    }

    /**
     * 添加语言
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = [
                'name'       => $this->request->post('name', ''),
                'code'       => $this->request->post('code', ''),
                'sort'       => (int) $this->request->post('sort', 0),
                'is_default' => (int) $this->request->post('is_default', 0),
                'is_enabled' => (int) $this->request->post('is_enabled', 1),
            ];

            if (empty($data['name']) || empty($data['code'])) {
                return json(['code' => 1, 'msg' => '语言名称和编码不能为空']);
            }

            try {
                Language::create($data);
                \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_LANGUAGE);
                return json(['code' => 0, 'msg' => '添加成功']);
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }

        $this->assign('info', null);
        return $this->view('/language_edit');
    }

    /**
     * 编辑语言
     */
    public function edit(int $id = 0)
    {
        $language = $id ? Db::name('language')->where('id', $id)->find() : null;
        $this->app->view->assign('info', $language);
        return $this->app->view->fetch('/language_edit');
    }

    /**
     * 保存语言
     */
    public function save()
    {
        $data = [
            'id'         => (int) $this->request->post('id', 0),
            'name'       => $this->request->post('name', ''),
            'code'       => $this->request->post('code', ''),
            'sort'       => (int) $this->request->post('sort', 0),
            'is_default' => (int) $this->request->post('is_default', 0),
            'is_enabled' => (int) $this->request->post('is_enabled', 1),
        ];

        try {
            $lang = Db::name('language')->where('id', $data['id'])->find();
            if ($lang) { Db::name('language')->where('id', $data['id'])->update($data); }
            \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_LANGUAGE);
            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 删除语言
     */
    public function delete()
    {
        $id = (int) $this->request->post('id', 0);
        try {
            Language::destroy($id);
            \app\common\service\CacheService::clearByTag(\app\common\service\CacheService::TAG_LANGUAGE);
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 翻译管理
     */
    public function translate()
    {
        $langCode = $this->request->param('lang', 'zh-cn');
        $group = $this->request->param('group', 'common');

        if ($this->request->isPost()) {
            $translations = $this->request->post('translations', []);
            try {
                foreach ($translations as $key => $value) {
                    LanguageService::saveTranslation($langCode, $group, $key, $value);
                }
                return json(['code' => 0, 'msg' => '翻译保存成功']);
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }

        $translations = LanguageService::getGroupTranslations($langCode, $group);
        $languages = Language::where('is_enabled', 1)->select();

        $this->assign('translations', $translations);
        $this->assign('languages', $languages);
        $this->assign('current_lang', $langCode);
        $this->assign('current_group', $group);
        return $this->view('/language_translate');
    }

    /**
     * AI批量翻译 - V2.9新增
     */
    public function aiTranslate()
    {
        $texts  = $this->request->post('texts/a', []);
        $from   = $this->request->post('from', 'zh');
        $to     = $this->request->post('to', 'en');
        $group  = $this->request->post('group', 'common');

        if (empty($texts)) {
            return json(['code' => 1, 'msg' => '待翻译文本为空']);
        }

        try {
            $ai = new \app\common\service\AiService();
            $result = $ai->translateBatch($texts, $from, $to);

            return json(['code' => 0, 'msg' => '翻译成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => 'AI翻译失败：' . $e->getMessage()]);
        }
    }
}
