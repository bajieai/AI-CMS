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
 * V2.9.15: OpenAI 翻译Provider（预留接口）
 *
 * 当前版本仅实现接口占位，未启用实际翻译逻辑。
 * 计划在后续版本接入 OpenAI GPT-4o 等模型实现翻译。
 */
class OpenAITranslateProvider implements TranslateProviderInterface
{
    public function translate(string $text, string $targetLang, array $options = []): array
    {
        return [
            'success'  => false,
            'text'     => '',
            'provider' => $this->getProviderName(),
            'message'  => 'OpenAI翻译Provider尚未实现（预留V2.9.16+）',
        ];
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function getSupportedLanguages(): array
    {
        return ['en', 'ja', 'ko'];
    }
}
