<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service\ai\translate;

/**
 * V2.9.15: AI翻译Provider接口
 *
 * 所有翻译Provider必须实现此接口。
 * 支持语言：en(英语)/ja(日语)/ko(韩语)
 */
interface TranslateProviderInterface
{
    /**
     * 翻译文本
     *
     * @param string $text      待翻译文本（可能含HTML标签）
     * @param string $targetLang 目标语言代码：en/ja/ko
     * @param array  $options    可选参数
     *                           - context: string 上下文提示（如"这是一篇科技文章"）
     *                           - preserveHtml: bool 是否保留HTML标签（默认true）
     * @return array ['success'=>bool, 'text'=>string, 'provider'=>string, 'message'=>string]
     */
    public function translate(string $text, string $targetLang, array $options = []): array;

    /**
     * 获取Provider名称
     */
    public function getProviderName(): string;

    /**
     * 检查Provider是否可用（API密钥已配置）
     */
    public function isAvailable(): bool;

    /**
     * 获取支持的目标语言列表
     * @return array ['en', 'ja', 'ko']
     */
    public function getSupportedLanguages(): array;
}
