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

namespace app\api\controller\ai;

use app\api\controller\BaseController;
use app\common\service\ai\AiWritingAssistService;
use think\facade\Log;

/**
 * AI辅助写作API控制器 — V2.9.39 AI-DEEP-2
 *
 * 提供RESTful API接口，支持9种写作辅助操作
 * 继承 app\api\controller\BaseController
 */
class WritingAssistController extends BaseController
{
    private AiWritingAssistService $service;

    public function __construct()
    {
        $this->service = new AiWritingAssistService();
    }

    /**
     * 1. 续写
     * POST /api/ai/writing_assist/continueWriting
     * @param string text 原文
     * @param int length 续写长度
     */
    public function continueWriting()
    {
        $text = $this->request->post('text', '');
        $length = (int) $this->request->post('length', 500);

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->continueWriting($text, $length);
        return $this->formatResult($result);
    }

    /**
     * 2. 改写
     * POST /api/ai/writing_assist/rewrite
     * @param string text 原文
     * @param string style 改写风格
     */
    public function rewrite()
    {
        $text = $this->request->post('text', '');
        $style = $this->request->post('style', 'professional');

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->rewrite($text, $style);
        return $this->formatResult($result);
    }

    /**
     * 3. 扩写
     * POST /api/ai/writing_assist/expand
     * @param string text 原文
     * @param int targetLength 目标长度
     */
    public function expand()
    {
        $text = $this->request->post('text', '');
        $targetLength = (int) $this->request->post('targetLength', 1000);

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->expand($text, $targetLength);
        return $this->formatResult($result);
    }

    /**
     * 4. 摘要
     * POST /api/ai/writing_assist/summarize
     * @param string text 原文
     * @param int maxLength 最大长度
     */
    public function summarize()
    {
        $text = $this->request->post('text', '');
        $maxLength = (int) $this->request->post('maxLength', 200);

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->summarize($text, $maxLength);
        return $this->formatResult($result);
    }

    /**
     * 5. 润色
     * POST /api/ai/writing_assist/polish
     * @param string text 原文
     */
    public function polish()
    {
        $text = $this->request->post('text', '');

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->polish($text);
        return $this->formatResult($result);
    }

    /**
     * 6. 校对
     * POST /api/ai/writing_assist/proofread
     * @param string text 原文
     */
    public function proofread()
    {
        $text = $this->request->post('text', '');

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->proofread($text);
        return $this->formatResult($result);
    }

    /**
     * 7. 风格转换
     * POST /api/ai/writing_assist/styleConvert
     * @param string text 原文
     * @param string targetStyle 目标风格
     */
    public function styleConvert()
    {
        $text = $this->request->post('text', '');
        $targetStyle = $this->request->post('targetStyle', 'formal');

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->styleConvert($text, $targetStyle);
        return $this->formatResult($result);
    }

    /**
     * 8. 格式转换
     * POST /api/ai/writing_assist/formatConvert
     * @param string text 原文
     * @param string targetFormat 目标格式
     */
    public function formatConvert()
    {
        $text = $this->request->post('text', '');
        $targetFormat = $this->request->post('targetFormat', 'markdown');

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->formatConvert($text, $targetFormat);
        return $this->formatResult($result);
    }

    /**
     * 9. 情感调整
     * POST /api/ai/writing_assist/emotionAdjust
     * @param string text 原文
     * @param string emotion 目标情感
     */
    public function emotionAdjust()
    {
        $text = $this->request->post('text', '');
        $emotion = $this->request->post('emotion', 'neutral');

        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        $result = $this->service->emotionAdjust($text, $emotion);
        return $this->formatResult($result);
    }

    /**
     * 通用执行接口
     * POST /api/ai/writing_assist/execute
     * @param string operation 操作类型
     * @param string text 原文
     * @param array params 参数
     */
    public function execute()
    {
        $operation = $this->request->post('operation', '');
        $text = $this->request->post('text', '');
        $params = $this->request->post('params', []);

        if (empty($operation)) {
            return $this->error('操作类型不能为空');
        }
        if (empty($text)) {
            return $this->error('文本内容不能为空');
        }

        if (is_string($params)) {
            $params = json_decode($params, true) ?: [];
        }

        $result = $this->service->execute($operation, $text, $params);
        return $this->formatResult($result);
    }

    /**
     * 获取支持的写作操作列表
     * GET /api/ai/writing_assist/operations
     */
    public function operations()
    {
        return $this->success('ok', [
            'operations' => $this->service->getSupportedOperations(),
            'styles'      => $this->service->getSupportedStyles(),
            'emotions'   => $this->service->getSupportedEmotions(),
        ]);
    }

    /**
     * 格式化返回结果
     * @param array $result 服务返回结果
     * @return \think\Response
     */
    private function formatResult(array $result): \think\Response
    {
        if ($result['success']) {
            return $this->success('操作成功', [
                'result'    => $result['result'] ?? '',
                'operation' => $result['operation'] ?? '',
                'errors'    => $result['errors'] ?? [],
            ]);
        }

        return $this->error($result['message'] ?? '操作失败');
    }
}
