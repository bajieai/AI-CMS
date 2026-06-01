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
use app\common\model\ContentLang;
use app\common\service\ai\AiTranslateService;
use app\common\service\ai\translate\TranslateProviderRouter;

/**
 * V2.9.15: AI翻译管理控制器
 */
class AiTranslateController extends AdminBaseController
{
    /**
     * 翻译单篇文章
     */
    public function translate(int $id)
    {
        $lang = $this->request->post('lang', 'en');
        $force = (bool) $this->request->post('force', false);

        if (!TranslateProviderRouter::isLanguageRegistered($lang)) {
            return $this->error('不支持的目标语言: ' . $lang);
        }

        $service = new AiTranslateService();
        $result = $service->translateContent($id, $lang, ['force' => $force]);

        if ($result['success']) {
            return $this->success($result['message'], [
                'data' => $result['data'] ? $result['data']->toArray() : null,
            ]);
        }

        return $this->error($result['message']);
    }

    /**
     * 批量翻译（异步入队）
     */
    public function batchTranslate()
    {
        $ids = $this->request->post('ids', []);
        $lang = $this->request->post('lang', 'en');

        if (empty($ids)) {
            return $this->error('请选择要翻译的文章');
        }

        if (!TranslateProviderRouter::isLanguageRegistered($lang)) {
            return $this->error('不支持的目标语言: ' . $lang);
        }

        $service = new AiTranslateService();
        $result = $service->batchTranslate($ids, $lang);

        if ($result['success']) {
            return $this->success($result['message'], [
                'task_ids' => $result['task_ids'],
                'count'    => count($result['task_ids']),
            ]);
        }

        return $this->error($result['message']);
    }

    /**
     * 查询翻译状态
     */
    public function getStatus(int $id, string $lang)
    {
        $record = ContentLang::getByContentIdAndLang($id, $lang);
        if (!$record) {
            return $this->success('未翻译', ['status' => ContentLang::STATUS_PENDING, 'status_label' => '待翻译']);
        }

        return $this->success('查询成功', [
            'status'       => $record->translate_status,
            'status_label' => $record->getStatusLabel(),
            'status_color' => $record->getStatusColor(),
            'provider'     => $record->translate_provider,
            'translate_time'=> $record->translate_time,
            'error_msg'    => $record->error_msg,
            'update_time'  => $record->update_time,
        ]);
    }

    /**
     * 删除翻译版本
     */
    public function delete(int $id, string $lang)
    {
        $service = new AiTranslateService();
        $result = $service->deleteTranslation($id, $lang);

        if ($result) {
            return $this->success('翻译版本已删除');
        }

        return $this->error('翻译版本不存在或删除失败');
    }

    /**
     * 获取文章的所有翻译版本
     */
    public function list(int $id)
    {
        $records = ContentLang::getTranslationsByContentId($id);
        $langs = TranslateProviderRouter::getRegisteredLanguages();

        // 构建完整语言状态列表
        $result = [];
        foreach ($langs as $code => $name) {
            $found = null;
            foreach ($records as $r) {
                if ($r['lang'] === $code) {
                    $found = $r;
                    break;
                }
            }
            $result[] = [
                'lang_code'    => $code,
                'lang_name'    => $name,
                'status'       => $found ? $found['translate_status'] : ContentLang::STATUS_PENDING,
                'status_label' => $found ? (ContentLang::STATUS_LABELS[$found['translate_status']] ?? '未知') : '未翻译',
                'status_color' => $found ? (ContentLang::STATUS_COLORS[$found['translate_status']] ?? 'default') : 'default',
                'update_time'  => $found ? $found['update_time'] : 0,
            ];
        }

        return $this->success('查询成功', ['list' => $result]);
    }
}
