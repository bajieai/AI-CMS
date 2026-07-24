<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\common\controller\AdminBaseController;
use app\common\service\ai\AiContentEnhanceService;
use app\common\service\ai\AiProofreadService;
use app\common\service\ai\AiConversationService;
use app\common\service\ai\AiFormatPreserveService;
use app\common\service\ai\AiEditorTranslateService;
use app\common\service\ai\AiEditorSnapshotService;
use app\common\service\ai\AiConfigService;

/**
 * AI内容编辑增强控制器 — V2.9.26 R-1, V2.9.28 A-1~A-8增强
 *
 * V2.9.26: continue/rewrite/expand/summarize 四模式
 * V2.9.28: 段落优化/多轮对话/格式保留/选段翻译/版本快照
 */
class AiContentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct(app());
    }

    // ==================== V2.9.26 R-1: 基础四模式 ====================

    public function continueWriting()
    {
        return $this->execute('continueWriting');
    }

    public function rewrite()
    {
        return $this->execute('rewrite');
    }

    public function expand()
    {
        return $this->execute('expand');
    }

    public function summarize()
    {
        return $this->execute('summarize');
    }

    protected function execute(string $method)
    {
        $text = $this->request->post('text', '');
        $style = $this->request->post('style', 'formal');
        $contentId = (int)$this->request->post('content_id', 0);

        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }

        $service = new AiContentEnhanceService();
        $userId = $this->adminInfo['id'] ?? 0;
        $result = $service->$method($text, $style, $userId, $contentId);

        if ($result['success']) {
            // V2.9.28 A-7: 自动保存版本快照
            if ($contentId > 0) {
                $snapshotService = new AiEditorSnapshotService();
                $snapshotService->createSnapshot($contentId, $userId, $result['text'], $method, "AI{$method}操作");
            }
            return json(['code' => 0, 'msg' => '处理成功', 'data' => [
                'text' => $result['text'],
                'elapsed_ms' => $result['elapsed_ms'],
            ]]);
        }
        return json(['code' => -1, 'msg' => $result['message'] ?? '处理失败']);
    }

    // ==================== V2.9.28 A-1: 段落级优化 ====================

    /**
     * 段落优化（错别字修正/句式优化/语气切换）
     */
    public function optimizeParagraph()
    {
        $text = $this->request->post('text', '');
        $mode = $this->request->post('mode', 'all'); // all/proofread/optimize
        $tone = $this->request->post('tone', ''); // formal/casual/professional/friendly
        $contentId = (int)$this->request->post('content_id', 0);

        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }

        $service = new AiProofreadService();

        if (!empty($tone)) {
            $result = $service->switchTone($text, $tone);
        } else {
            $result = $service->optimizeParagraph($text, $mode);
        }

        // 保存快照
        if ($contentId > 0) {
            $snapshotService = new AiEditorSnapshotService();
            $snapshotService->createSnapshot($contentId, $this->adminInfo['id'] ?? 0, $result['text'], 'optimize', "段落优化({$mode})");
        }

        return json(['code' => 0, 'msg' => '优化成功', 'data' => $result]);
    }

    // ==================== V2.9.28 A-2: 多轮对话 ====================

    /**
     * 发送对话消息
     */
    public function chat()
    {
        $sessionId = $this->request->post('session_id', '');
        $message = $this->request->post('message', '');
        $contentId = (int)$this->request->post('content_id', 0);

        if (empty($message)) {
            return json(['code' => -1, 'msg' => '请输入消息']);
        }

        $service = new AiConversationService();

        // 自动创建会话
        if (empty($sessionId) || $service->isSessionExpired($sessionId)) {
            $sessionId = $service->createSession($this->adminInfo['id'] ?? 0, $contentId);
        }

        $result = $service->chat($sessionId, $this->adminInfo['id'] ?? 0, $message, $contentId);

        if ($result['success']) {
            return json(['code' => 0, 'msg' => '成功', 'data' => array_merge($result, ['session_id' => $sessionId])]);
        }
        return json(['code' => -1, 'msg' => $result['message']]);
    }

    /**
     * 获取对话历史
     */
    public function chatHistory()
    {
        $sessionId = $this->request->get('session_id', '');
        if (empty($sessionId)) {
            return json(['code' => -1, 'msg' => '缺少会话ID']);
        }

        $service = new AiConversationService();
        $history = $service->getHistory($sessionId);
        return json(['code' => 0, 'data' => $history]);
    }

    /**
     * 导出对话
     */
    public function exportChat()
    {
        $sessionId = $this->request->get('session_id', '');
        $format = $this->request->get('format', 'markdown');

        $service = new AiConversationService();
        $content = $service->exportSession($sessionId, $format);

        $ext = $format === 'json' ? 'json' : 'md';
        return download($content, "ai_conversation_{$sessionId}.{$ext}");
    }

    // ==================== V2.9.28 A-3: 格式保留 ====================

    /**
     * 格式感知处理（带格式保留的AI处理）
     */
    public function formatPreserveProcess()
    {
        $text = $this->request->post('text', '');
        $operation = $this->request->post('operation', 'rewrite'); // continue/rewrite/expand/summarize
        $style = $this->request->post('style', 'formal');
        $contentId = (int)$this->request->post('content_id', 0);

        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }

        $formatService = new AiFormatPreserveService();
        $enhanceService = new AiContentEnhanceService();

        // 格式感知：注入格式保留指令
        $originalFidelity = $formatService->analyzeFormat($text);

        // 执行AI处理
        $result = $enhanceService->$operation($text, $style, $this->adminInfo['id'] ?? 0, $contentId);

        if (!$result['success']) {
            return json(['code' => -1, 'msg' => $result['message'] ?? '处理失败']);
        }

        // 格式后处理
        $processedText = $formatService->postProcess($text, $result['text']);
        $fidelity = $formatService->calculateFidelity($text, $processedText);

        return json(['code' => 0, 'msg' => '处理成功', 'data' => [
            'text' => $processedText,
            'elapsed_ms' => $result['elapsed_ms'],
            'format_fidelity' => $fidelity,
        ]]);
    }

    // ==================== V2.9.28 A-4: 选段翻译 ====================

    /**
     * 选段翻译
     */
    public function translate()
    {
        $text = $this->request->post('text', '');
        $targetLang = $this->request->post('target_lang', 'en');
        $mode = $this->request->post('mode', 'replace'); // replace/insert/compare
        $contentId = (int)$this->request->post('content_id', 0);

        if (empty($text)) {
            return json(['code' => -1, 'msg' => '请输入内容']);
        }

        $service = new AiEditorTranslateService();
        $result = $service->translate($text, $targetLang, $mode);

        if ($result['success']) {
            // 保存快照
            if ($contentId > 0) {
                $snapshotService = new AiEditorSnapshotService();
                $snapshotService->createSnapshot($contentId, $this->adminInfo['id'] ?? 0, $result['result'], 'translate', "翻译为{$targetLang}");
            }

            return json(['code' => 0, 'msg' => '翻译成功', 'data' => $result]);
        }
        return json(['code' => -1, 'msg' => $result['message']]);
    }

    /**
     * 获取支持的翻译语言列表
     */
    public function translateLanguages()
    {
        return json(['code' => 0, 'data' => AiEditorTranslateService::$languages]);
    }

    // ==================== V2.9.28 A-7: 版本快照 ====================

    /**
     * 获取版本列表
     */
    public function snapshotList()
    {
        $contentId = (int)$this->request->get('content_id', 0);
        if ($contentId <= 0) {
            return json(['code' => -1, 'msg' => '缺少内容ID']);
        }

        $service = new AiEditorSnapshotService();
        $versions = $service->getVersions($contentId);
        return json(['code' => 0, 'data' => $versions]);
    }

    /**
     * 版本对比
     */
    public function snapshotDiff()
    {
        $contentId = (int)$this->request->get('content_id', 0);
        $v1 = (int)$this->request->get('v1', 0);
        $v2 = (int)$this->request->get('v2', 0);

        $service = new AiEditorSnapshotService();
        $result = $service->diff($contentId, $v1, $v2);
        return json(['code' => $result['success'] ? 0 : -1, 'msg' => $result['message'] ?? '成功', 'data' => $result]);
    }

    /**
     * 回滚到指定版本
     */
    public function snapshotRollback()
    {
        $contentId = (int)$this->request->post('content_id', 0);
        $version = (int)$this->request->post('version', 0);

        $service = new AiEditorSnapshotService();
        $result = $service->rollback($contentId, $version, $this->adminInfo['id'] ?? 0);
        return json(['code' => $result['success'] ? 0 : -1, 'msg' => $result['message'], 'data' => $result]);
    }
}
