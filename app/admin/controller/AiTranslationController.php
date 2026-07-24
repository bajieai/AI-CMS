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
use app\common\model\Content;
use app\common\service\AiTranslationService;
use app\common\service\LanguageService;

/**
 * AI翻译管理后台控制器 - V2.9.2 M19a
 */
class AiTranslationController extends AdminBaseController
{
    /**
     * 翻译管理首页
     */
    public function index()
    {
        $keyword = $this->request->get('keyword', '');
        $lang = $this->request->get('lang', '');
        $pageSize = (int) $this->request->get('limit', 20);

        $query = Content::with('cate')
            ->where('translation_of', 0) // 只显示原始内容
            ->where('status', '>=', 0);

        if ($keyword) {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        $list = $query->order('id', 'desc')->paginate($pageSize);

        // 为每条内容获取其翻译状态
        foreach ($list as &$item) {
            $item['translations'] = (new AiTranslationService())->getTranslations((int)$item['id']);
            $item['translation_count'] = count($item['translations']);
        }

        $languages = LanguageService::getEnabledLanguages();

        $this->assign('list', $list);
        $this->assign('languages', $languages);
        $this->assign('keyword', $keyword);
        $this->assign('lang', $lang);

        if ($this->isRealAjax()) {
            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }

        return $this->view('/ai_translation_index');
    }

    /**
     * 翻译内容
     */
    public function translate()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        $targetLangs = $this->request->post('target_langs', []);

        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '内容ID不能为空']);
        }
        if (empty($targetLangs)) {
            return json(['code' => 1, 'msg' => '请选择目标语言']);
        }

        $service = new AiTranslationService();
        $result = $service->translateContent($contentId, $targetLangs);

        if ($result['success'] && empty($result['errors'])) {
            return json(['code' => 0, 'msg' => '翻译成功', 'data' => $result['results']]);
        }

        if ($result['success'] && !empty($result['errors'])) {
            return json([
                'code' => 0,
                'msg'  => $result['msg'],
                'data' => ['results' => $result['results'], 'errors' => $result['errors']],
            ]);
        }

        return json(['code' => 1, 'msg' => $result['msg'], 'data' => $result['errors']]);
    }

    /**
     * 批量翻译
     */
    public function batchTranslate()
    {
        $contentIds = $this->request->post('content_ids', []);
        $targetLangs = $this->request->post('target_langs', []);

        if (empty($contentIds)) {
            return json(['code' => 1, 'msg' => '请选择要翻译的内容']);
        }
        if (empty($targetLangs)) {
            return json(['code' => 1, 'msg' => '请选择目标语言']);
        }

        $service = new AiTranslationService();
        $result = $service->batchTranslate($contentIds, $targetLangs);

        return json([
            'code' => 0,
            'msg'  => "批量翻译完成：成功 {$result['success']} 条，失败 {$result['failed']} 条",
            'data' => $result,
        ]);
    }

    /**
     * 删除翻译
     */
    public function deleteTranslation()
    {
        $contentId = (int) $this->request->post('content_id', 0);
        $lang = $this->request->post('lang', '');

        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '内容ID不能为空']);
        }

        try {
            if ($lang) {
                Content::where('translation_of', $contentId)
                    ->where('lang', $lang)
                    ->delete();
            } else {
                $service = new AiTranslationService();
                $service->deleteTranslations($contentId);
            }

            return json(['code' => 0, 'msg' => '翻译已删除']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取内容的翻译详情
     */
    public function detail()
    {
        $contentId = (int) $this->request->get('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => 1, 'msg' => '内容ID不能为空']);
        }

        $content = Content::find($contentId);
        if (!$content) {
            return json(['code' => 1, 'msg' => '内容不存在']);
        }

        $service = new AiTranslationService();
        $translations = $service->getTranslations($contentId);

        return json([
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'content'      => $content->toArray(),
                'translations' => $translations,
            ],
        ]);
    }
}
