<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ml\TranslateWorkbenchService;
use app\common\service\ml\LangSyncService;

/**
 * 翻译工作台控制器 — V2.9.34 ML-4/ML-5
 */
class TranslateWorkbenchController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    public function index()
    {
        $langSiteId = (int)$this->request->param('lang_site_id', 0);
        $page = (int)$this->request->param('page', 1);
        $service = new TranslateWorkbenchService();
        $list = $service->getWorkbench($langSiteId, $page);
        $this->assign('list', $list);
        $this->assign('lang_site_id', $langSiteId);
        $this->assign('menuActive', 'translate_workbench');
        return $this->view('/translate_workbench/index');
    }

    public function translate()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $targetLangSiteId = (int)$this->request->post('target_lang_site_id', 0);
        $service = new TranslateWorkbenchService();
        $result = $service->translate($contentId, $targetLangSiteId);
        if ($result['success'] ?? false) {
            return $this->success('翻译完成', $result);
        }
        return $this->error($result['message'] ?? '翻译失败');
    }

    public function batchTranslate()
    {
        $contentIds = $this->request->post('ids', []);
        $targetLangSiteId = (int)$this->request->post('target_lang_site_id', 0);
        $service = new LangSyncService();
        $result = $service->batchSync($contentIds, $targetLangSiteId);
        if ($result['success'] ?? false) {
            return $this->success('批量翻译完成', $result);
        }
        return $this->error($result['message'] ?? '批量翻译失败');
    }

    public function saveTranslation()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $targetLangSiteId = (int)$this->request->post('target_lang_site_id', 0);
        $fields = $this->request->post('fields', []);
        $service = new TranslateWorkbenchService();
        $result = $service->saveTranslation($contentId, $targetLangSiteId, $fields);
        if ($result['success'] ?? false) {
            return $this->success('保存成功', $result);
        }
        return $this->error($result['message'] ?? '保存失败');
    }

    public function syncStatus()
    {
        $contentId = (int)$this->request->param('content_id', 0);
        $langSiteId = (int)$this->request->param('lang_site_id', 0);
        $service = new LangSyncService();
        $result = $service->getSyncStatus($contentId, $langSiteId);
        return json($result);
    }

    public function resolveConflict()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $langSiteId = (int)$this->request->post('lang_site_id', 0);
        $resolution = (string)$this->request->post('resolution', 'overwrite');
        $service = new LangSyncService();
        $result = $service->resolveConflict($contentId, $langSiteId, $resolution);
        if ($result['success'] ?? false) {
            return $this->success('冲突已解决', $result);
        }
        return $this->error($result['message'] ?? '解决失败');
    }
}
