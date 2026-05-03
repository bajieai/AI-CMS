<?php
declare(strict_types=1);

namespace app\common\service\publish;

use app\common\model\Content;
use app\common\model\PublishPlatform;

/**
 * 发布平台接口 - V2.5
 * 策略模式：微信公众号、头条号等适配器实现此接口
 */
interface PublishPlatformInterface
{
    /**
     * 发布内容到平台
     * @param Content $content 内容模型
     * @param PublishPlatform $platform 平台配置模型
     * @return array ['media_id'|'article_id' => string, ...]
     * @throws \Exception
     */
    public function publish(Content $content, PublishPlatform $platform): array;

    /**
     * 获取平台名称标识
     */
    public function getName(): string;

    /**
     * 获取平台显示名称
     */
    public function getDisplayName(): string;

    /**
     * 验证平台配置是否有效
     */
    public function validateConfig(PublishPlatform $platform): bool;

    /**
     * 获取配置字段定义（用于后台表单渲染）
     * @return array [['name' => string, 'label' => string, 'type' => string, 'required' => bool], ...]
     */
    public function getConfigFields(): array;
}
