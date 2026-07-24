<?php
declare(strict_types=1);
namespace app\common\service\template;

use think\facade\Db;
use think\facade\Cache;

/**
 * 模板样式版本历史Service - V2.9.32 CUS2-4
 */
class TemplateStyleVersionService
{
    private const CACHE_TAG = 'style_version';

    /**
     * 创建版本记录
     */
    public function createVersion(int $memberId, int $templateId, string $changeType, string $summary, array $configSnapshot, ?array $diff = null): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $lastVersion = (int) Db::table($prefix . 'template_style_version')->where('member_id', $memberId)->where('template_id', $templateId)->max('version');
        $newVersion = $lastVersion + 1;

        Db::table($prefix . 'template_style_version')->insert([
            'member_id' => $memberId, 'template_id' => $templateId, 'version' => $newVersion,
            'change_type' => $changeType, 'change_summary' => $summary,
            'config_snapshot' => json_encode($configSnapshot, JSON_UNESCAPED_UNICODE),
            'diff' => $diff ? json_encode($diff, JSON_UNESCAPED_UNICODE) : null,
            'create_time' => time(),
        ]);

        // 自动清理超出保留数的旧版本
        $maxVersions = (int) config('template.v2.9.32.style_version.max_versions', 30);
        $this->cleanup($memberId, $templateId, $maxVersions);

        Cache::clear();
        return ['success' => true, 'version' => $newVersion, 'message' => "版本{$newVersion}已记录"];
    }

    /**
     * 获取版本历史
     */
    public function getHistory(int $memberId, int $templateId): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        return Db::table($prefix . 'template_style_version')
            ->where('member_id', $memberId)->where('template_id', $templateId)
            ->order('version', 'desc')->select()->toArray();
    }

    /**
     * 版本对比
     */
    public function compareVersions(int $memberId, int $templateId, int $v1, int $v2): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $ver1 = Db::table($prefix . 'template_style_version')->where('member_id', $memberId)->where('template_id', $templateId)->where('version', $v1)->find();
        $ver2 = Db::table($prefix . 'template_style_version')->where('member_id', $memberId)->where('template_id', $templateId)->where('version', $v2)->find();
        if (!$ver1 || !$ver2) return ['success' => false, 'message' => '版本不存在'];

        $config1 = json_decode($ver1['config_snapshot'], true) ?: [];
        $config2 = json_decode($ver2['config_snapshot'], true) ?: [];

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($config1), array_keys($config2)));
        foreach ($allKeys as $key) {
            $val1 = $config1[$key] ?? null;
            $val2 = $config2[$key] ?? null;
            if ($val1 !== $val2) $diff[$key] = ['v1' => $val1, 'v2' => $val2];
        }

        return ['success' => true, 'v1' => $v1, 'v2' => $v2, 'diff' => $diff, 'changed_count' => count($diff)];
    }

    /**
     * 版本回滚
     */
    public function rollback(int $memberId, int $templateId, int $targetVersion): array
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $version = Db::table($prefix . 'template_style_version')->where('member_id', $memberId)->where('template_id', $templateId)->where('version', $targetVersion)->find();
        if (!$version) return ['success' => false, 'message' => '目标版本不存在'];

        $config = json_decode($version['config_snapshot'], true) ?: [];

        // 创建回滚记录
        $this->createVersion($memberId, $templateId, 'all', "回滚到版本{$targetVersion}", $config);

        return ['success' => true, 'message' => "已回滚到版本{$targetVersion}", 'config' => $config];
    }

    /**
     * 清理旧版本
     */
    public function cleanup(int $memberId, int $templateId, int $maxVersions = 30): void
    {
        $prefix = Db::getConfig('prefix') ?: config('database.connections.mysql.prefix');
        $count = Db::table($prefix . 'template_style_version')->where('member_id', $memberId)->where('template_id', $templateId)->count();
        if ($count > $maxVersions) {
            $deleteCount = $count - $maxVersions;
            $oldIds = Db::table($prefix . 'template_style_version')->where('member_id', $memberId)->where('template_id', $templateId)->order('version', 'asc')->limit($deleteCount)->column('id');
            Db::table($prefix . 'template_style_version')->whereIn('id', $oldIds)->delete();
        }
    }
}
