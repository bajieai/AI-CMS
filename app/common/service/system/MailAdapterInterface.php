<?php
declare(strict_types=1);

namespace app\common\service\system;

/**
 * 邮件适配器接口
 */
interface MailAdapterInterface
{
    public function send(string $to, string $subject, string $body): array;
    public function getName(): string;
}
