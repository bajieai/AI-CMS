<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateBackup;
use app\common\model\TemplateCustomConfig;

/**
 * 模板备份还原服务 - V2.9.12
 */
class TemplateBackupRestoreService
{
    /**
     * 备份目录
     */
    protected function getBackupDir(): string
    {
        $dir = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'theme_backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * 创建手动备份
     */
    public function createBackup(int $memberId, string $themeSlug, string $name = ''): array
    {
        $config = TemplateCustomConfig::getThemeConfig($memberId, $themeSlug);
        if (empty($config)) {
            return ['success' => false, 'message' => '当前无自定义配置，无需备份'];
        }

        $timestamp = date('Ymd_His');
        $name = $name ?: "backup_{$timestamp}";
        $fileName = "{$memberId}_{$themeSlug}_{$timestamp}.json";
        $filePath = $this->getBackupDir() . DIRECTORY_SEPARATOR . $fileName;

        $content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, $content);

        $backup = TemplateBackup::addBackup(
            $memberId,
            $themeSlug,
            $name,
            $fileName,
            strlen($content),
            $config,
            false
        );

        return ['success' => true, 'message' => '备份成功', 'data' => $backup->toArray()];
    }

    /**
     * 创建自动备份（保存前触发）
     */
    public function createAutoBackup(int $memberId, string $themeSlug, array $config): array
    {
        $timestamp = date('Ymd_His');
        $fileName = "{$memberId}_{$themeSlug}_auto_{$timestamp}.json";
        $filePath = $this->getBackupDir() . DIRECTORY_SEPARATOR . $fileName;

        $content = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filePath, $content);

        // 清理旧自动备份（保留最近5个）
        $this->cleanupAutoBackups($memberId, $themeSlug);

        $backup = TemplateBackup::addBackup(
            $memberId,
            $themeSlug,
            "自动备份 {$timestamp}",
            $fileName,
            strlen($content),
            $config,
            true
        );

        return ['success' => true, 'data' => $backup->toArray()];
    }

    /**
     * 清理自动备份（保留最近5个）
     */
    protected function cleanupAutoBackups(int $memberId, string $themeSlug): void
    {
        $autoBackups = TemplateBackup::where('member_id', $memberId)
            ->where('theme_slug', $themeSlug)
            ->where('is_auto', 1)
            ->order('create_time', 'desc')
            ->column('id');

        if (count($autoBackups) > 5) {
            $toDelete = array_slice($autoBackups, 5);
            TemplateBackup::whereIn('id', $toDelete)->delete();
        }
    }

    /**
     * 还原备份
     */
    public function restoreBackup(int $backupId, int $memberId): array
    {
        $backup = TemplateBackup::where('id', $backupId)
            ->where('member_id', $memberId)
            ->find();

        if (!$backup) {
            return ['success' => false, 'message' => '备份不存在或无权访问'];
        }

        $config = $backup->getConfig();
        if (empty($config)) {
            return ['success' => false, 'message' => '备份数据为空'];
        }

        // 先创建当前状态的备份（防止误操作）
        $this->createBackup($memberId, $backup->theme_slug, '还原前自动备份');

        // 清除旧配置并写入备份配置
        TemplateCustomConfig::clearThemeConfig($memberId, $backup->theme_slug);
        TemplateCustomConfig::setConfigs($memberId, $backup->theme_slug, $config);

        return ['success' => true, 'message' => '还原成功'];
    }

    /**
     * 获取备份列表
     */
    public function listBackups(int $memberId, string $themeSlug): array
    {
        return TemplateBackup::getBackups($memberId, $themeSlug);
    }

    /**
     * 删除备份
     */
    public function deleteBackup(int $backupId, int $memberId): bool
    {
        $backup = TemplateBackup::where('id', $backupId)
            ->where('member_id', $memberId)
            ->find();

        if (!$backup) {
            return false;
        }

        // 删除文件
        $filePath = $this->getBackupDir() . DIRECTORY_SEPARATOR . $backup->backup_file;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $backup->delete() > 0;
    }

    /**
     * 恢复官方默认（清除所有自定义+删除备份文件记录）
     */
    public function resetToDefault(int $memberId, string $themeSlug): array
    {
        TemplateCustomConfig::clearThemeConfig($memberId, $themeSlug);
        return ['success' => true, 'message' => '已恢复官方默认设置'];
    }
}
