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

namespace app\common\service;

use app\common\model\Subscriber;
use app\common\model\MailLog;
use app\common\model\Content;

/**
 * 邮件订阅服务 - V2.9.18 D-3
 * 
 * 实现 Double Opt-in 订阅流程 + 内容发布时自动邮件通知
 */
class SubscribeService
{
    protected MailService $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    /**
     * 提交订阅（生成确认 token，发送确认邮件）
     */
    public function submit(string $email, string $source = 'footer'): array
    {
        $email = trim($email);

        // 邮箱格式校验
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'msg' => '邮箱格式不正确'];
        }

        // 检查是否已存在
        $existing = Subscriber::where('email', $email)->find();
        if ($existing) {
            if ($existing->status == Subscriber::STATUS_CONFIRMED) {
                return ['success' => false, 'msg' => '该邮箱已订阅'];
            }
            if ($existing->status == Subscriber::STATUS_PENDING) {
                // 重新发送确认邮件
                $this->sendConfirmEmail($existing->email, $existing->confirm_token);
                return ['success' => true, 'msg' => '确认邮件已重新发送，请查收'];
            }
            // 已退订的重新订阅
            $existing->status = Subscriber::STATUS_PENDING;
            $existing->confirm_token = Subscriber::generateToken();
            $existing->source = $source;
            $existing->subscribed_at = date('Y-m-d H:i:s');
            $existing->confirmed_at = null;
            $existing->unsubscribed_at = null;
            $existing->save();
            $this->sendConfirmEmail($existing->email, $existing->confirm_token);
            return ['success' => true, 'msg' => '确认邮件已发送，请查收'];
        }

        // 新建订阅
        $token = Subscriber::generateToken();
        Subscriber::create([
            'email'        => $email,
            'status'       => Subscriber::STATUS_PENDING,
            'confirm_token'=> $token,
            'source'       => $source,
            'subscribed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->sendConfirmEmail($email, $token);
        return ['success' => true, 'msg' => '确认邮件已发送，请查收邮箱完成订阅'];
    }

    /**
     * 确认订阅（Double Opt-in）
     */
    public function confirm(string $token): array
    {
        $subscriber = Subscriber::findByToken($token);
        if (!$subscriber) {
            return ['success' => false, 'msg' => '确认链接无效或已过期'];
        }

        if ($subscriber->status == Subscriber::STATUS_CONFIRMED) {
            return ['success' => true, 'msg' => '您已确认订阅，无需重复确认'];
        }

        $subscriber->status = Subscriber::STATUS_CONFIRMED;
        $subscriber->confirmed_at = date('Y-m-d H:i:s');
        $subscriber->save();

        return ['success' => true, 'msg' => '订阅成功！您将收到最新内容通知'];
    }

    /**
     * 退订
     */
    public function unsubscribe(string $token): array
    {
        $subscriber = Subscriber::findByToken($token);
        if (!$subscriber) {
            return ['success' => false, 'msg' => '退订链接无效'];
        }

        $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
        $subscriber->unsubscribed_at = date('Y-m-d H:i:s');
        $subscriber->save();

        return ['success' => true, 'msg' => '已退订，您将不再收到邮件通知'];
    }

    /**
     * 内容发布时通知所有已确认订阅者
     *
     * @return int 发送数量
     */
    public function notifySubscribers(int $contentId): int
    {
        $content = Content::find($contentId);
        if (!$content) return 0;

        $subscribers = Subscriber::getConfirmed();
        $siteName = $this->getSiteName();
        $siteUrl  = $this->getSiteUrl();
        $contentUrl = $siteUrl . '/info/' . $contentId . '.html';

        $subject = '【' . $siteName . '】新内容发布：' . ($content->title ?? '点击查看');
        $body = $this->buildNotifyBody($content, $contentUrl, $siteName);

        $count = 0;
        $today = date('Y-m-d');

        foreach ($subscribers as $sub) {
            // 每日频率限制：同订阅者每日最多 1 封
            $todayCount = MailLog::where('subscriber_id', $sub['id'])
                ->whereTime('sent_at', '>=', $today . ' 00:00:00')
                ->count();
            if ($todayCount > 0) continue;

            $sent = $this->mailService->send(
                $sub['email'],
                $subject,
                str_replace('{unsubscribe_link}', $this->buildUrl('/api/subscribe/unsubscribe?token=' . $sub['confirm_token']), $body)
            );

            MailLog::record([
                'subscriber_id' => $sub['id'],
                'content_id'    => $contentId,
                'email'         => $sub['email'],
                'subject'       => $subject,
                'status'        => $sent ? MailLog::STATUS_SENT : MailLog::STATUS_FAILED,
                'error_msg'     => $sent ? '' : '发送失败',
                'sent_at'       => $sent ? date('Y-m-d H:i:s') : null,
            ]);

            if ($sent) $count++;
        }

        return $count;
    }

    /**
     * 发送确认邮件
     */
    protected function sendConfirmEmail(string $email, string $token): bool
    {
        $siteName = $this->getSiteName();
        $confirmUrl = $this->buildUrl('/api/subscribe/confirm?token=' . $token);
        $unsubscribeUrl = $this->buildUrl('/api/subscribe/unsubscribe?token=' . $token);

        $subject = '请确认订阅【' . $siteName . '】的最新资讯';
        $body = $this->buildConfirmBody($siteName, $confirmUrl, $unsubscribeUrl);

        return $this->mailService->send($email, $subject, $body);
    }

    protected function buildConfirmBody(string $siteName, string $confirmUrl, string $unsubscribeUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px">
    <h2 style="color:#333">📬 确认订阅</h2>
    <p style="color:#666">您好！感谢您订阅<strong>{$siteName}</strong>的最新资讯。</p>
    <p style="color:#666">请点击下方按钮确认您的订阅：</p>
    <div style="text-align:center;margin:30px 0">
        <a href="{$confirmUrl}" style="display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">确认订阅</a>
    </div>
    <p style="color:#999;font-size:12px">如果您没有订阅，请忽略此邮件。<br><a href="{$unsubscribeUrl}" style="color:#999">退订</a></p>
    <hr style="border:none;border-top:1px solid #eee;margin:20px 0">
    <p style="color:#999;font-size:12px">{$siteName} 团队</p>
</div></body></html>
HTML;
    }

    protected function buildNotifyBody($content, string $contentUrl, string $siteName): string
    {
        $title   = htmlspecialchars($content->title ?? '');
        $summary = htmlspecialchars(mb_substr(strip_tags($content->description ?? ''), 0, 200));
        $cover   = $content->cover ?? '';

        $coverHtml = $cover ? '<img src="' . htmlspecialchars($cover) . '" style="max-width:100%;border-radius:8px;margin:15px 0" alt="">' : '';

        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;padding:30px">
    <h2 style="color:#333">{$title}</h2>
    {$coverHtml}
    <p style="color:#666;line-height:1.8">{$summary}</p>
    <div style="text-align:center;margin:30px 0">
        <a href="{$contentUrl}" style="display:inline-block;background:#007bff;color:#fff;padding:12px 32px;border-radius:6px;text-decoration:none;font-size:16px">查看详情</a>
    </div>
    <hr style="border:none;border-top:1px solid #eee;margin:20px 0">
    <p style="color:#999;font-size:12px">
        此邮件由 {$siteName} 自动发送，如不想继续接收，<a href="{unsubscribe_link}" style="color:#999">点击退订</a>
    </p>
</div></body></html>
HTML;
    }

    protected function getSiteName(): string
    {
        try {
            return \app\common\model\Config::where('name', 'site_name')->value('value') ?: 'AI-CMS';
        } catch (\Throwable $e) {
            return 'AI-CMS';
        }
    }

    protected function getSiteUrl(): string
    {
        try {
            return \app\common\model\Config::where('name', 'site_url')->value('value') ?: '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function buildUrl(string $path): string
    {
        return rtrim($this->getSiteUrl(), '/') . $path;
    }
}
