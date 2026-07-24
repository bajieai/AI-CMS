<?php
declare(strict_types=1);

namespace app\common\service\system;

// This file has been split into individual class files:
// - SmsService.php, SmsAdapterInterface.php, AliyunSmsAdapter.php, TencentSmsAdapter.php, QiniuSmsAdapter.php
// - MailServiceV2.php, MailAdapterInterface.php, SmtpAdapter.php, AliyunMailAdapter.php, SendGridAdapter.php
// - UnifiedNotifyService.php, NotifyChannelManager.php, InAppNotifyChannel.php, WechatTemplateChannel.php, NotifySubscriptionService.php
// Payment adapters moved to app/common/service/payment/:
// - AlipayPaymentChannel.php, UnionPayChannel.php
