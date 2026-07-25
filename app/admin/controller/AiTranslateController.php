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

    /**
     * V2.9.17 E-2: 翻译进度SSE实时流
     * GET /admin/ai_translate/stream/:taskId
     */
    public function stream(int $taskId)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('X-Content-Type-Options: nosniff');

        echo "retry: 3000\n\n";
        if (ob_get_level()) { ob_flush(); }
        flush();

        $maxWait = 120;
        $start = time();
        $lastHeartbeat = time();
        $lastProgress = -1;

        while (time() - $start < $maxWait) {
            $record = ContentLang::find($taskId);
            if (!$record) {
                $this->sendSSEEvent('error', ['status' => 0, 'progress' => 0, 'message' => '翻译任务不存在', 'task_id' => $taskId, 'timestamp' => time()]);
                return;
            }

            $currentStatus = (int) $record->translate_status;
            $currentProgress = (int) ($record->translate_time > 0 ? 50 : 10); // 简版进度估算
            $currentMessage = $record->error_msg ?: '翻译进行中';

            if ($currentStatus === ContentLang::STATUS_COMPLETED) {
                $currentProgress = 100;
                $currentMessage = '翻译完成';
            }

            $isSpecial = $currentStatus !== ContentLang::STATUS_PROCESSING;
            $progressChanged = abs($currentProgress - $lastProgress) >= 5;

            if ($isSpecial || $progressChanged || $lastProgress === -1) {
                $data = ['status' => $currentStatus, 'progress' => $currentProgress, 'message' => $currentMessage, 'task_id' => $taskId, 'timestamp' => time()];

                if ($currentStatus === ContentLang::STATUS_COMPLETED) {
                    $this->sendSSEEvent('complete', $data);
                    return;
                } elseif ($currentStatus === ContentLang::STATUS_FAILED) {
                    $this->sendSSEEvent('error', $data);
                    return;
                } else {
                    $this->sendSSEEvent('progress', $data);
                }
                $lastProgress = $currentProgress;
            }

            if (time() - $lastHeartbeat >= 15) {
                $this->sendSSEEvent('heartbeat', []);
                $lastHeartbeat = time();
            }

            sleep(2);
        }

        $this->sendSSEEvent('timeout', ['message' => 'SSE连接超时（120秒），请刷新页面', 'task_id' => $taskId, 'timestamp' => time()]);
    }

    /**
     * V2.9.17 E-2: 发送SSE事件
     */
    protected function sendSSEEvent(string $event, array $data = []): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!empty($event) && $event !== 'message') {
            echo "event: {$event}\n";
        }
        echo "data: {$json}\n\n";
        if (ob_get_level()) { ob_flush(); }
        flush();
    }

    // ==================== V2.9.26 R-2: AI翻译增强 ====================

    /**
     * 批量翻译增强（含翻译记忆+术语库）
     */
    public function batchTranslateEnhanced()
    {
        $texts = $this->request->post('texts', []);
        $sourceLang = $this->request->post('source_lang', 'zh-CN');
        $targetLang = $this->request->post('target_lang', 'en');

        if (empty($texts) || !is_array($texts)) {
            return json(['code' => -1, 'msg' => '请提供待翻译文本数组']);
        }

        $service = new \app\common\service\ai\AiTranslateEnhanceService();
        $result = $service->batchTranslate($texts, $sourceLang, $targetLang);

        return json(['code' => 0, 'msg' => '翻译完成', 'data' => $result['results']]);
    }

    /**
     * 翻译记忆统计
     */
    public function memoryStats()
    {
        $service = new \app\common\service\ai\AiTranslateEnhanceService();
        $stats = $service->getMemoryStats();
        return json(['code' => 0, 'data' => $stats]);
    }

    /**
     * 术语库统计
     */
    public function glossaryStats()
    {
        $service = new \app\common\service\ai\AiTranslateEnhanceService();
        $stats = $service->getGlossaryStats();
        return json(['code' => 0, 'data' => $stats]);
    }
}
