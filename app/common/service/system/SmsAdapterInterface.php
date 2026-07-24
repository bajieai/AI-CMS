<?php
declare(strict_types=1);

namespace app\common\service\system;

/**
 * 短信适配器接口
 */
interface SmsAdapterInterface
{
    public function send(string $mobile, string $templateCode, array $params): array;
    public function getName(): string;
}
