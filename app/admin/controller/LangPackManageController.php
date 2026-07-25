<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ml\LangPackService;
use app\common\service\ml\TranslationMemoryService;

/**
 * 语言包管理
 * V2.9.37 I18N
 */
class LangPackManageController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    /**
     * 语言包列表
     */
    public function index()
    {
        $service = new LangPackService();
        $packs = $service->getPackList();
        return $this->view('/lang_pack_index', ['packs' => $packs]);
    }

    /**
     * 条目列表
     */
    public function entries()
    {
        $langCode = $this->request->get('lang_code', 'zh-cn');
        $module = $this->request->get('module', '');
        $translated = $this->request->get('translated', '');
        $keyword = $this->request->get('keyword', '');
        $service = new LangPackService();
        $entries = $service->getEntries($langCode, [
            'module' => $module, 'keyword' => $keyword,
            'translated' => $translated !== '' ? (int) $translated : null,
        ]);
        $stats = $service->getStats($langCode);
        return $this->view('/lang_pack_entries', [
            'entries' => $entries, 'lang_code' => $langCode,
            'stats' => $stats, 'module' => $module, 'keyword' => $keyword,
        ]);
    }

    /**
     * 保存条目
     */
    public function save()
    {
        $data = $this->request->post();
        $service = new LangPackService();
        $id = $service->saveEntry($data);
        return json(['success' => $id > 0, 'id' => $id, 'msg' => $id > 0 ? '保存成功' : '保存失败']);
    }

    /**
     * AI批量翻译
     */
    public function batchTranslate()
    {
        $langCode = $this->request->post('lang_code', '');
        $module = $this->request->post('module', 'frontend');
        if (empty($langCode)) {
            return json(['success' => false, 'msg' => '请选择语言']);
        }
        $service = new LangPackService();
        $result = $service->batchTranslate($langCode, $module);
        return json(['success' => true, 'data' => $result, 'msg' => "翻译完成: 成功{$result['success']}条, 失败{$result['failed']}条"]);
    }

    /**
     * 导出
     */
    public function export()
    {
        $langCode = $this->request->get('lang_code', 'zh-cn');
        $module = $this->request->get('module', '');
        $service = new LangPackService();
        $json = $service->exportPack($langCode, $module);
        return download($json, 'lang_pack_' . $langCode . '.json');
    }

    /**
     * 版本历史
     */
    public function versions()
    {
        $langCode = $this->request->get('lang_code', 'zh-cn');
        $module = $this->request->get('module', 'frontend');
        $service = new LangPackService();
        $history = $service->getVersionHistory($langCode, $module);
        return $this->view('/lang_pack_versions', [
            'history' => $history, 'lang_code' => $langCode, 'module' => $module,
        ]);
    }

    /**
     * 版本对比 (P0-2修复)
     */
    public function compareVersions()
    {
        $langCode = $this->request->get('lang_code', 'zh-cn');
        $module = $this->request->get('module', 'frontend');
        $v1 = (int) $this->request->get('v1', 0);
        $v2 = (int) $this->request->get('v2', 0);
        if ($v1 <= 0 || $v2 <= 0) {
            return json(['success' => false, 'msg' => '请选择两个版本']);
        }
        $service = new LangPackService();
        $result = $service->compareVersions($langCode, $module, $v1, $v2);
        return json(['success' => true, 'data' => $result]);
    }

    /**
     * 回滚版本
     */
    public function rollbackVersion()
    {
        $langCode = $this->request->post('lang_code', 'zh-cn');
        $module = $this->request->post('module', 'frontend');
        $version = (int) $this->request->post('version', 0);
        if ($version <= 0) {
            return json(['success' => false, 'msg' => '请选择版本']);
        }
        $service = new LangPackService();
        $result = $service->rollbackVersion($langCode, $module, $version);
        return json(['success' => $result, 'msg' => $result ? '回滚成功' : '回滚失败']);
    }

    /**
     * 创建快照
     */
    public function createSnapshot()
    {
        $langCode = $this->request->post('lang_code', 'zh-cn');
        $module = $this->request->post('module', 'frontend');
        $service = new LangPackService();
        $id = $service->createSnapshot($langCode, $module, 'manual');
        return json(['success' => $id > 0, 'id' => $id, 'msg' => $id > 0 ? '快照已创建' : '创建失败']);
    }

    /**
     * 翻译记忆库
     */
    public function memory()
    {
        $service = new TranslationMemoryService();
        $stats = $service->getStats();
        return $this->view('/lang_pack_memory', ['stats' => $stats]);
    }

    /**
     * 清理翻译记忆
     */
    public function cleanupMemory()
    {
        $days = (int) $this->request->post('days', 180);
        $service = new TranslationMemoryService();
        $count = $service->cleanup($days);
        return json(['success' => true, 'count' => $count, 'msg' => "已清理{$count}条低质量记忆"]);
    }
}
