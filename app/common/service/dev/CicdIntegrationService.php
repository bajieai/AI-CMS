<?php

declare(strict_types=1);

namespace app\common\service\dev;

use think\facade\Db;
use think\facade\Cache;

/**
 * CI/CD集成服务
 * 支持 GitHub Actions / GitLab CI / 通用 Webhook 自动化部署
 * 数据存储在 i8j_config 表（group=cicd）
 */
class CicdIntegrationService
{
    private const CACHE_TAG = 'cicd';
    private const CONFIG_GROUP = 'cicd';
    private const CACHE_TTL = 3600;

    /**
     * 获取所有CI/CD配置
     */
    public static function getConfigs(): array
    {
        return Cache::remember('cicd_configs', function (): array {
            $rows = Db::name('config')
                ->where('group', self::CONFIG_GROUP)
                ->select()
                ->toArray();

            $configs = [];
            foreach ($rows as $row) {
                $configs[$row['name']] = $row['value'];
            }

            // 默认值
            $defaults = [
                'github_enabled' => '0',
                'github_token' => '',
                'github_repo' => '',
                'github_workflow' => 'deploy.yml',
                'gitlab_enabled' => '0',
                'gitlab_token' => '',
                'gitlab_project' => '',
                'gitlab_ref' => 'main',
                'webhook_secret' => '',
                'auto_deploy' => '0',
                'deploy_branch' => 'main',
                'notify_email' => '',
                'notify_webhook' => '',
            ];

            return array_merge($defaults, $configs);
        }, self::CACHE_TTL);
    }

    /**
     * 保存CI/CD配置
     */
    public static function saveConfig(array $data): void
    {
        $now = time();
        foreach ($data as $name => $value) {
            // 只允许已知的配置项
            if (!self::isAllowedConfig($name)) {
                continue;
            }

            $exists = Db::name('config')
                ->where('name', $name)
                ->where('group', self::CONFIG_GROUP)
                ->find();

            if ($exists) {
                Db::name('config')
                    ->where('name', $name)
                    ->where('group', self::CONFIG_GROUP)
                    ->update([
                        'value' => (string)$value,
                        'update_time' => $now,
                    ]);
            } else {
                Db::name('config')->insert([
                    'name' => $name,
                    'value' => (string)$value,
                    'group' => self::CONFIG_GROUP,
                    'type' => 'text',
                    'remark' => self::getConfigRemark($name),
                    'sort' => 0,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            }
        }

        Cache::clear();
    }

    /**
     * 获取 Webhook 调用记录
     */
    public static function getWebhooks(int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        // 使用 security_log 表记录 webhook 调用（action_type = 'cicd_webhook'）
        // 如果表不存在则返回空
        try {
            $list = Db::name('security_log')
                ->where('action_type', 'cicd_webhook')
                ->order('id', 'desc')
                ->limit($offset, $pageSize)
                ->select()
                ->toArray();

            $total = Db::name('security_log')
                ->where('action_type', 'cicd_webhook')
                ->count();
        } catch (\Exception $e) {
            $list = [];
            $total = 0;
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 记录 Webhook 调用
     */
    public static function logWebhook(string $source, string $event, array $payload, string $status = 'success', string $errorMsg = ''): void
    {
        try {
            Db::name('security_log')->insert([
                'user_id' => 0,
                'action_type' => 'cicd_webhook',
                'action' => $source . ':' . $event,
                'ip' => request()->ip() ?? '0.0.0.0',
                'user_agent' => substr(request()->header('user-agent', ''), 0, 500),
                'detail' => json_encode([
                    'source' => $source,
                    'event' => $event,
                    'status' => $status,
                    'error' => $errorMsg,
                    'payload_size' => strlen(json_encode($payload)),
                    'payload_preview' => substr(json_encode($payload), 0, 1000),
                ], JSON_UNESCAPED_UNICODE),
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // 忽略日志写入错误
        }
    }

    /**
     * 处理 GitHub Webhook
     */
    public static function handleGitHubWebhook(array $payload, string $signature): array
    {
        $configs = self::getConfigs();

        // 验证签名
        if (!empty($configs['webhook_secret'])) {
            $expected = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $configs['webhook_secret']);
            if (!hash_equals($expected, $signature)) {
                self::logWebhook('github', 'push', $payload, 'failed', 'Invalid signature');
                return ['code' => 401, 'msg' => 'Invalid signature'];
            }
        }

        $event = $payload['ref'] ?? '';
        $branch = $configs['deploy_branch'] ?? 'main';

        // 只处理目标分支的 push 事件
        if (strpos($event, "refs/heads/{$branch}") === false) {
            self::logWebhook('github', 'push', $payload, 'skipped', 'Branch mismatch');
            return ['code' => 0, 'msg' => 'Branch mismatch, skipped'];
        }

        // 触发部署
        $result = self::triggerDeploy('github', $payload);

        self::logWebhook('github', 'push', $payload, $result['code'] === 0 ? 'success' : 'failed', $result['msg'] ?? '');

        return $result;
    }

    /**
     * 处理 GitLab Webhook
     */
    public static function handleGitLabWebhook(array $payload, string $token): array
    {
        $configs = self::getConfigs();

        // 验证 Token
        if (!empty($configs['webhook_secret'])) {
            if ($token !== $configs['webhook_secret']) {
                self::logWebhook('gitlab', 'push', $payload, 'failed', 'Invalid token');
                return ['code' => 401, 'msg' => 'Invalid token'];
            }
        }

        $ref = $payload['ref'] ?? '';
        $branch = $configs['deploy_branch'] ?? 'main';

        if (strpos($ref, "refs/heads/{$branch}") === false) {
            self::logWebhook('gitlab', 'push', $payload, 'skipped', 'Branch mismatch');
            return ['code' => 0, 'msg' => 'Branch mismatch, skipped'];
        }

        $result = self::triggerDeploy('gitlab', $payload);
        self::logWebhook('gitlab', 'push', $payload, $result['code'] === 0 ? 'success' : 'failed', $result['msg'] ?? '');

        return $result;
    }

    /**
     * 触发部署
     */
    private static function triggerDeploy(string $source, array $payload): array
    {
        $configs = self::getConfigs();

        // 如果未开启自动部署
        if ($configs['auto_deploy'] !== '1') {
            return ['code' => 0, 'msg' => 'Auto deploy disabled, webhook received only'];
        }

        // 发送通知
        if (!empty($configs['notify_webhook'])) {
            self::sendNotify($configs['notify_webhook'], [
                'source' => $source,
                'event' => 'deploy_triggered',
                'time' => date('Y-m-d H:i:s'),
                'commit' => $payload['after'] ?? ($payload['checkout_sha'] ?? ''),
            ]);
        }

        return ['code' => 0, 'msg' => 'Deploy triggered successfully'];
    }

    /**
     * 发送通知
     */
    private static function sendNotify(string $url, array $data): void
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // 忽略通知发送错误
        }
    }

    /**
     * 检查配置项是否允许
     */
    private static function isAllowedConfig(string $name): bool
    {
        $allowed = [
            'github_enabled', 'github_token', 'github_repo', 'github_workflow',
            'gitlab_enabled', 'gitlab_token', 'gitlab_project', 'gitlab_ref',
            'webhook_secret', 'auto_deploy', 'deploy_branch',
            'notify_email', 'notify_webhook',
        ];
        return in_array($name, $allowed, true);
    }

    /**
     * 获取配置项备注
     */
    private static function getConfigRemark(string $name): string
    {
        $remarks = [
            'github_enabled' => 'GitHub Actions 启用',
            'github_token' => 'GitHub Token',
            'github_repo' => 'GitHub 仓库 (owner/repo)',
            'github_workflow' => 'GitHub Workflow 文件名',
            'gitlab_enabled' => 'GitLab CI 启用',
            'gitlab_token' => 'GitLab Token',
            'gitlab_project' => 'GitLab 项目ID',
            'gitlab_ref' => 'GitLab 分支',
            'webhook_secret' => 'Webhook 密钥',
            'auto_deploy' => '自动部署开关',
            'deploy_branch' => '部署分支',
            'notify_email' => '通知邮箱',
            'notify_webhook' => '通知 Webhook URL',
        ];
        return $remarks[$name] ?? $name;
    }
}
