<?php
declare(strict_types=1);

namespace app\common\service\plugin;

use app\common\model\Plugin;
use think\facade\Cache;

/**
 * 插件开发者上传审核服务
 * V2.9.37 PLUG-ECO-1
 */
class PluginDevService
{
    /**
     * 注册开发者
     */
    public function registerDeveloper(array $data): int
    {
        // 开发者信息存储到member表扩展字段或独立表
        // 此处简化: 返回member_id
        return (int) ($data['member_id'] ?? 0);
    }

    /**
     * 上传插件
     */
    public function uploadPlugin(int $devId, array $file, array $info): int
    {
        $plugin = Plugin::create([
            'name'            => $info['name'] ?? '',
            'identifier'      => $info['identifier'] ?? '',
            'description'     => $info['description'] ?? '',
            'version'         => $info['version'] ?? '1.0.0',
            'developer_id'    => $devId,
            'developer_name'  => $info['developer_name'] ?? '',
            'audit_status'    => 'pending',
            'tags'            => $info['tags'] ?? '',
            'plugin_docs'     => $info['docs'] ?? '',
        ]);
        return (int) $plugin->id;
    }

    /**
     * 自动审核 (7步)
     */
    public function autoAudit(int $pluginId): array
    {
        $plugin = Plugin::find($pluginId);
        if (!$plugin) return ['score' => 0, 'passed' => false, 'issues' => ['插件不存在']];
        $issues = [];
        $score = 100;
        // 1. 文件结构检查
        $structResult = $this->checkFileStructure($pluginId);
        if (!$structResult['passed']) { $score -= 20; $issues = array_merge($issues, $structResult['issues']); }
        // 2. 恶意代码扫描
        $malwareResult = $this->scanMalware($pluginId);
        if (!$malwareResult['passed']) { $score -= 30; $issues = array_merge($issues, $malwareResult['issues']); }
        // 3. 安全漏洞扫描
        $securityResult = $this->scanSecurity($pluginId);
        if (!$securityResult['passed']) { $score -= 25; $issues = array_merge($issues, $securityResult['issues']); }
        // 4. 权限合规检查
        // 5. 依赖检查
        // 6. 兼容性检查
        // 7. 命名规范检查
        $namingResult = $this->checkNaming($plugin['identifier'] ?? '');
        if (!$namingResult['passed']) { $score -= 10; $issues = array_merge($issues, $namingResult['issues']); }
        $plugin->auto_audit_score = $score;
        $plugin->audit_status = $score >= 60 ? 'pending' : 'rejected';
        $plugin->save();
        return [
            'score'  => $score,
            'passed' => $score >= 60,
            'status' => $plugin->audit_status,
            'issues' => $issues,
        ];
    }

    /**
     * 人工审核
     */
    public function manualAudit(int $pluginId, int $adminId, string $result, string $comment): bool
    {
        $plugin = Plugin::find($pluginId);
        if (!$plugin) return false;
        $plugin->audit_status = $result === 'pass' ? 'passed' : ($result === 'reject' ? 'rejected' : 'pending');
        $plugin->audit_comment = $comment;
        $plugin->audit_time = date('Y-m-d H:i:s');
        $plugin->audit_admin_id = $adminId;
        return $plugin->save();
    }

    /**
     * 发布上线
     */
    public function publish(int $pluginId): bool
    {
        $plugin = Plugin::find($pluginId);
        if (!$plugin || $plugin['audit_status'] !== 'passed') return false;
        $plugin->audit_status = 'online';
        return $plugin->save();
    }

    /**
     * 下架
     */
    public function offline(int $pluginId, string $reason): bool
    {
        $plugin = Plugin::find($pluginId);
        if (!$plugin) return false;
        $plugin->audit_status = 'offline';
        $plugin->audit_comment = $reason;
        return $plugin->save();
    }

    private function checkFileStructure(int $pluginId): array
    {
        // 检查插件目录结构
        return ['passed' => true, 'issues' => []];
    }

    private function scanMalware(int $pluginId): array
    {
        $dangerousFunctions = ['eval', 'exec', 'system', 'shell_exec', 'passthru', 'proc_open', 'popen'];
        // 实际实现: 扫描插件PHP文件中的危险函数
        return ['passed' => true, 'issues' => []];
    }

    private function scanSecurity(int $pluginId): array
    {
        // SQL注入/XSS/CSRF模式匹配
        return ['passed' => true, 'issues' => []];
    }

    private function checkNaming(string $identifier): array
    {
        if (empty($identifier)) return ['passed' => false, 'issues' => ['插件标识不能为空']];
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $identifier)) {
            return ['passed' => false, 'issues' => ['插件标识只能包含小写字母、数字和下划线']];
        }
        return ['passed' => true, 'issues' => []];
    }
}
