<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme_generator;

use app\common\service\theme\ThemeReviewService;
use think\facade\Db;

/**
 * i8j_theme_info 的来源登记读写
 *
 * 只负责 source=ai-theme 这一类的行：安装时写入/更新，回滚时按历史值复原。
 * 不碰 frontend_theme，不写 template/themes。
 */
class ThemeOriginRepository
{
    /** 来源标识：AI-THEME 适配器安装 */
    public const SOURCE = 'ai-theme';

    /** 主题类型：前台主题 */
    public const TYPE = 'frontend';

    /**
     * 已登记的适配器主题列表（index 页表格）
     */
    public function listInstalled(): array
    {
        return Db::name('theme_info')
            ->where('source', self::SOURCE)
            ->order('installed_at', 'desc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 按 slug 取一行；不存在返回 null
     */
    public function findBySlug(string $slug): ?array
    {
        $row = Db::name('theme_info')
            ->where('code', $slug)
            ->where('type', self::TYPE)
            ->find();

        return $row ?: null;
    }

    /**
     * 安装/升级后登记：存在则更新，不存在则插入
     *
     * @param array{name:string,version:string,task_id:string,installed_at:string,package_sha256:string,description?:string} $manifest
     */
    public function register(string $slug, array $manifest): array
    {
        $now = time();
        $payload = [
            'name'             => (string) $manifest['name'],
            'version'          => (string) $manifest['version'],
            'is_installed'     => 1,
            'installed_version' => (string) $manifest['version'],
            'update_available' => 0,
            'source'           => self::SOURCE,
            'task_id'          => (string) $manifest['task_id'],
            'installed_at'     => (string) $manifest['installed_at'],
            'package_sha256'   => (string) $manifest['package_sha256'],
            'update_time'      => $now,
        ];

        if (isset($manifest['description']) && is_string($manifest['description'])) {
            $payload['description'] = $manifest['description'];
        }

        $existing = $this->findBySlug($slug);

        // V2.9.64 T-3: 安装 / 升级 / 同版本重装 一律回到「待验收」，验收通过前不进前台可选列表
        $reviewMeta = [
            'name'        => (string) $manifest['name'],
            'version'     => (string) $manifest['version'],
            'description' => (string) ($manifest['description'] ?? ''),
        ];

        if ($existing) {
            Db::name('theme_info')->where('id', (int) $existing['id'])->update($payload);
            ThemeReviewService::markPending($slug, $reviewMeta);

            return $this->findBySlug($slug) ?? $existing;
        }

        $payload['code'] = $slug;
        $payload['type'] = self::TYPE;
        $payload['create_time'] = $now;
        $id = Db::name('theme_info')->insertGetId($payload);

        ThemeReviewService::markPending($slug, $reviewMeta);

        return $this->findBySlug($slug) ?? ['id' => (int) $id] + $payload;
    }

    /**
     * 回滚后按快照复原登记（version / sha256 / installed_at / task_id）
     */
    public function restore(string $slug, array $snapshot): void
    {
        Db::name('theme_info')->where('code', $slug)->where('type', self::TYPE)->update([
            'name'              => (string) ($snapshot['name'] ?? ''),
            'version'           => (string) ($snapshot['version'] ?? ''),
            'installed_version' => (string) ($snapshot['version'] ?? ''),
            'source'            => (string) ($snapshot['source'] ?? self::SOURCE),
            'task_id'           => (string) ($snapshot['task_id'] ?? ''),
            'installed_at'      => (string) ($snapshot['installed_at'] ?? ''),
            'package_sha256'    => (string) ($snapshot['package_sha256'] ?? ''),
            'update_time'       => time(),
        ]);
    }
}
