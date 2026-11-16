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

use app\common\model\Feedback;
use think\facade\Log;

/**
 * 问题反馈服务 - V2.9.63
 *
 * 链路：后台提交 → 本地落库 → （可选）中心网关上报 → （可选）邮件通知
 *
 * 中心网关协议约定（未配置网关时全部降级为本地留存，不阻塞提交）：
 *   POST {gateway_url}/api/feedback
 *   Header: Authorization: Bearer {gateway_token}
 *   Body  : site_url / app_version / type / title / content / contact_email /
 *           contact_mobile / admin_name / env / attachments / create_time
 *   返回  : {"code":0,"msg":"ok","data":{"ticket_no":"GW202611160001"}}
 */
class FeedbackService
{
    /** 配置项键名 */
    public const CFG_GATEWAY_URL    = 'feedback_gateway_url';
    public const CFG_GATEWAY_TOKEN  = 'feedback_gateway_token';
    public const CFG_AUTO_REPORT    = 'feedback_auto_report';
    public const CFG_NOTIFY_ENABLED = 'feedback_notify_enabled';
    public const CFG_NOTIFY_EMAIL   = 'feedback_notify_email';

    private const CFG_GROUP = 'feedback';

    /**
     * 读取反馈配置
     */
    public function getConfig(): array
    {
        return [
            'gateway_url'    => (string) ConfigService::get(self::CFG_GATEWAY_URL, ''),
            'gateway_token'  => (string) ConfigService::get(self::CFG_GATEWAY_TOKEN, ''),
            'auto_report'    => (int) ConfigService::get(self::CFG_AUTO_REPORT, 1),
            'notify_enabled' => (int) ConfigService::get(self::CFG_NOTIFY_ENABLED, 0),
            'notify_email'   => (string) ConfigService::get(self::CFG_NOTIFY_EMAIL, ''),
        ];
    }

    /**
     * 保存反馈配置
     */
    public function saveConfig(array $data): void
    {
        $url = trim((string) ($data['gateway_url'] ?? ''));
        if ($url !== '' && !preg_match('#^https?://#i', $url)) {
            $url = '';
        }
        ConfigService::set(self::CFG_GATEWAY_URL, rtrim($url, '/'), self::CFG_GROUP, '反馈中心网关地址');
        ConfigService::set(self::CFG_GATEWAY_TOKEN, trim((string) ($data['gateway_token'] ?? '')), self::CFG_GROUP, '反馈中心网关令牌');
        ConfigService::set(self::CFG_AUTO_REPORT, empty($data['auto_report']) ? 0 : 1, self::CFG_GROUP, '提交后自动上报中心');
        ConfigService::set(self::CFG_NOTIFY_ENABLED, empty($data['notify_enabled']) ? 0 : 1, self::CFG_GROUP, '提交后发送邮件通知');
        ConfigService::set(self::CFG_NOTIFY_EMAIL, trim((string) ($data['notify_email'] ?? '')), self::CFG_GROUP, '反馈通知接收邮箱');
    }

    /**
     * 提交反馈
     *
     * @param  array $data  表单数据
     * @param  array $admin 当前后台用户
     * @return array{ok:bool,msg:string,id:int}
     */
    public function submit(array $data, array $admin): array
    {
        $type    = (string) ($data['type'] ?? 'bug');
        $title   = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));

        if (!array_key_exists($type, Feedback::TYPE_MAP)) {
            return ['ok' => false, 'msg' => '反馈类型不合法', 'id' => 0];
        }
        if ($title === '' || mb_strlen($title) > 200) {
            return ['ok' => false, 'msg' => '标题必填且不超过 200 字', 'id' => 0];
        }
        if (mb_strlen($content) < 5 || mb_strlen($content) > 5000) {
            return ['ok' => false, 'msg' => '问题描述需 5~5000 字', 'id' => 0];
        }

        $attachments = $data['attachments'] ?? [];
        if (is_string($attachments)) {
            $decoded = json_decode($attachments, true);
            $attachments = is_array($decoded) ? $decoded : [];
        }

        $record = [
            'type'           => $type,
            'title'          => $title,
            'content'        => $content,
            'contact_email'  => trim((string) ($data['contact_email'] ?? '')),
            'contact_mobile' => trim((string) ($data['contact_mobile'] ?? '')),
            'admin_id'       => (int) ($admin['id'] ?? 0),
            'admin_name'     => (string) ($admin['username'] ?? ''),
            'attachments'    => json_encode(array_values((array) $attachments), JSON_UNESCAPED_UNICODE),
            'env'            => json_encode($this->buildEnv(), JSON_UNESCAPED_UNICODE),
            'status'         => 0,
            'gateway_status' => 0,
            'notify_status'  => 0,
            'site_url'       => (string) (request()->domain() ?: ''),
            'app_version'    => (string) config('app.app_version', ''),
        ];

        $model = new Feedback();
        if (!$model->save($record)) {
            return ['ok' => false, 'msg' => '保存失败，请重试', 'id' => 0];
        }
        $id = (int) $model->id;

        $cfg = $this->getConfig();
        if (!empty($cfg['auto_report']) && $cfg['gateway_url'] !== '') {
            $this->report($id);
        }
        if (!empty($cfg['notify_enabled']) && $cfg['notify_email'] !== '') {
            $this->notify($id);
        }

        return ['ok' => true, 'msg' => '反馈已提交，编号 #' . $id, 'id' => $id];
    }

    /**
     * 上报中心网关
     *
     * @return array{ok:bool,msg:string}
     */
    public function report(int $id): array
    {
        $row = Feedback::find($id);
        if (!$row) {
            return ['ok' => false, 'msg' => '反馈不存在'];
        }
        $cfg = $this->getConfig();
        if ($cfg['gateway_url'] === '') {
            return ['ok' => false, 'msg' => '未配置中心网关地址，已保留本地记录'];
        }

        $payload = [
            'site_url'       => (string) ($row['site_url'] ?? ''),
            'app_version'    => (string) ($row['app_version'] ?? ''),
            'type'           => (string) $row['type'],
            'title'          => (string) $row['title'],
            'content'        => (string) $row['content'],
            'contact_email'  => (string) ($row['contact_email'] ?? ''),
            'contact_mobile' => (string) ($row['contact_mobile'] ?? ''),
            'admin_name'     => (string) ($row['admin_name'] ?? ''),
            'env'            => json_decode((string) ($row['env'] ?? '{}'), true) ?: [],
            'attachments'    => json_decode((string) ($row['attachments'] ?? '[]'), true) ?: [],
            'create_time'    => (int) ($row['create_time'] ?? time()),
        ];

        $result = $this->postJson($cfg['gateway_url'] . '/api/feedback', $payload, $cfg['gateway_token']);
        if (!empty($result['ok'])) {
            $row->save([
                'gateway_status' => 1,
                'gateway_no'     => (string) ($result['ticket_no'] ?? ''),
                'gateway_error'  => '',
            ]);
            return ['ok' => true, 'msg' => '已上报中心' . (!empty($result['ticket_no']) ? '，受理编号 ' . $result['ticket_no'] : '')];
        }

        $row->save([
            'gateway_status' => 2,
            'gateway_error'  => mb_substr((string) ($result['msg'] ?? '未知错误'), 0, 240),
        ]);
        return ['ok' => false, 'msg' => (string) ($result['msg'] ?? '上报失败')];
    }

    /**
     * 邮件通知
     *
     * @return array{ok:bool,msg:string}
     */
    public function notify(int $id): array
    {
        $row = Feedback::find($id);
        if (!$row) {
            return ['ok' => false, 'msg' => '反馈不存在'];
        }
        $cfg = $this->getConfig();
        $to  = $cfg['notify_email'] !== '' ? $cfg['notify_email'] : (string) ($row['contact_email'] ?? '');
        if ($to === '') {
            return ['ok' => false, 'msg' => '未配置通知邮箱'];
        }

        $subject = '[问题反馈] #' . $id . ' ' . (string) $row['title'];
        $body    = '<div style="font-family:sans-serif;line-height:1.7">'
            . '<p><strong>站点：</strong>' . htmlspecialchars((string) ($row['site_url'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>版本：</strong>' . htmlspecialchars((string) ($row['app_version'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>类型：</strong>' . htmlspecialchars((string) $row['type_text'], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>标题：</strong>' . htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>提交人：</strong>' . htmlspecialchars((string) ($row['admin_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>描述：</strong></p><pre style="white-space:pre-wrap">'
            . htmlspecialchars((string) $row['content'], ENT_QUOTES, 'UTF-8') . '</pre>'
            . '</div>';

        try {
            $ok = (new MailService())->send($to, $subject, $body);
        } catch (\Throwable $e) {
            Log::warning('[feedback] 通知邮件发送异常: ' . $e->getMessage());
            $ok = false;
        }

        $row->save(['notify_status' => $ok ? 1 : 2]);
        return ['ok' => (bool) $ok, 'msg' => $ok ? '通知邮件已发送' : '通知邮件发送失败，请检查邮件配置'];
    }

    /**
     * 测试通知
     *
     * @return array{ok:bool,msg:string}
     */
    public function testNotify(string $to = ''): array
    {
        $cfg = $this->getConfig();
        $to  = $to !== '' ? $to : $cfg['notify_email'];
        if ($to === '') {
            return ['ok' => false, 'msg' => '请先填写通知邮箱'];
        }
        try {
            $ok = (new MailService())->send($to, '[问题反馈] 通知通道测试', '<p>这是一封来自 AI-CMS 后台「反馈设置」的测试邮件。</p>');
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => '发送异常：' . $e->getMessage()];
        }
        return ['ok' => (bool) $ok, 'msg' => $ok ? '测试邮件已发送至 ' . $to : '发送失败，请检查邮件配置'];
    }

    /**
     * 状态统计（首页卡片）
     */
    public function stats(): array
    {
        $stats = ['total' => 0, 'pending' => 0, 'solved' => 0];
        try {
            $stats['total']   = (int) Feedback::count();
            $stats['pending'] = (int) Feedback::whereIn('status', [0, 1])->count();
            $stats['solved']  = (int) Feedback::where('status', 2)->count();
        } catch (\Throwable $e) {
            Log::warning('[feedback] 统计失败: ' . $e->getMessage());
        }
        return $stats;
    }

    /**
     * 采集运行环境（反馈时自动上报，便于定位问题）
     */
    public function buildEnv(): array
    {
        return [
            'php_version'   => PHP_VERSION,
            'app_version'   => (string) config('app.app_version', ''),
            'os'            => PHP_OS_FAMILY . ' ' . php_uname('r'),
            'server'        => (string) ($_SERVER['SERVER_SOFTWARE'] ?? ''),
            'db_version'    => $this->dbVersion(),
            'extensions'    => array_values(array_intersect(
                ['curl', 'gd', 'mbstring', 'openssl', 'pdo_mysql', 'redis', 'zip'],
                get_loaded_extensions()
            )),
            'collected_at'  => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 读取数据库版本（失败返回空字符串，不影响提交）
     */
    private function dbVersion(): string
    {
        try {
            $row = \think\facade\Db::query('SELECT VERSION() AS v');
            return (string) ($row[0]['v'] ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * POST JSON 到中心网关（无 curl 扩展时降级为 stream）
     *
     * @return array{ok:bool,msg:string,ticket_no:string}
     */
    private function postJson(string $url, array $payload, string $token = ''): array
    {
        $body    = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type: application/json; charset=utf-8'];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $raw = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);
                if ($raw === false) {
                    return ['ok' => false, 'msg' => '连接中心失败：' . $err, 'ticket_no' => ''];
                }
            } else {
                $ctx = stream_context_create(['http' => [
                    'method'        => 'POST',
                    'header'        => implode("\r\n", $headers),
                    'content'       => $body,
                    'timeout'       => 10,
                    'ignore_errors' => true,
                ]]);
                $raw = @file_get_contents($url, false, $ctx);
                if ($raw === false) {
                    return ['ok' => false, 'msg' => '连接中心失败（无 curl 扩展且 stream 不可用）', 'ticket_no' => ''];
                }
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => '上报异常：' . $e->getMessage(), 'ticket_no' => ''];
        }

        $json = json_decode((string) $raw, true);
        if (!is_array($json)) {
            return ['ok' => false, 'msg' => '中心返回非 JSON：' . mb_substr((string) $raw, 0, 120), 'ticket_no' => ''];
        }
        if ((int) ($json['code'] ?? -1) !== 0) {
            return ['ok' => false, 'msg' => (string) ($json['msg'] ?? '中心返回失败'), 'ticket_no' => ''];
        }
        return [
            'ok'        => true,
            'msg'       => 'ok',
            'ticket_no' => (string) ($json['data']['ticket_no'] ?? ''),
        ];
    }
}
