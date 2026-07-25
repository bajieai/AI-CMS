<?php
declare(strict_types=1);
namespace app\common\service\webhook;

/**
 * Webhook签名服务 (V2.9.29 D-4)
 * HMAC-SHA256签名
 */
class WebhookSigner
{
    public function sign(string $data, string $secret): string
    {
        return hash_hmac('sha256', $data, $secret);
    }

    public function verify(string $data, string $signature, string $secret): bool
    {
        $expected = $this->sign($data, $secret);
        return hash_equals($expected, $signature);
    }
}
