<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | 官网: http://www.i8j.cn
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\service\theme;

use think\facade\Db;

/**
 * 主题验收状态服务 - V2.9.64
 *
 * 状态机：0 待验收 → 1 已验收 / 2 已驳回（驳回可重新验收）
 * 口径：只有 review_status=1 的主题才出现在前台可选列表（TemplateService::scanThemes）。
 * 兜底：读库异常时 approvedCodes() 返回 null，调用方按「不过滤」处理，避免误伤线上可用主题。
 */
class ThemeReviewService
{
    public const STATUS_PENDING  = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    public const TYPE_FRONTEND = 'frontend';

    /** 后台列表要展示的状态文案 */
    public static function statusText(int $status): string
    {
        $map = [
            self::STATUS_PENDING  => '待验收',
            self::STATUS_APPROVED => '已验收',
            self::STATUS_REJECTED => '已驳回',
        ];

        return $map[$status] ?? '未知';
    }

    /**
     * 已验收主题 code 列表；DB 异常返回 null（表示不做过滤）
     */
    public static function approvedCodes(): ?array
    {
        try {
            return Db::name('theme_info')
                ->where('type', self::TYPE_FRONTEND)
                ->where('review_status', self::STATUS_APPROVED)
                ->column('code');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * code => review_status 映射（单次查询，供 scanThemes 批量过滤）
     */
    public static function statusMap(): array
    {
        try {
            return Db::name('theme_info')
                ->where('type', self::TYPE_FRONTEND)
                ->column('review_status', 'code');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 取某个主题的登记行
     */
    public static function find(string $code): ?array
    {
        $row = Db::name('theme_info')
            ->where('code', $code)
            ->where('type', self::TYPE_FRONTEND)
            ->find();

        return $row ?: null;
    }

    /**
     * 登记为「待验收」（安装 / AI 生成落盘后调用）；已有行则重置状态
     */
    public static function markPending(string $code, array $meta = []): void
    {
        try {
            $now = time();
            $existing = self::find($code);

            if ($existing) {
                Db::name('theme_info')->where('id', (int) $existing['id'])->update([
                    'review_status' => self::STATUS_PENDING,
                    'review_by'     => 0,
                    'review_time'   => 0,
                    'review_remark' => '',
                    'update_time'   => $now,
                ]);

                return;
            }

            Db::name('theme_info')->insert([
                'code'              => $code,
                'type'              => self::TYPE_FRONTEND,
                'name'              => (string) ($meta['name'] ?? $code),
                'version'           => (string) ($meta['version'] ?? '1.0.0'),
                'description'       => (string) ($meta['description'] ?? ''),
                'is_installed'      => 1,
                'installed_version' => (string) ($meta['version'] ?? '1.0.0'),
                'review_status'     => self::STATUS_PENDING,
                'review_by'         => 0,
                'review_time'       => 0,
                'review_remark'     => '',
                'create_time'       => $now,
                'update_time'       => $now,
            ]);
        } catch (\Throwable $e) {
            // 登记失败不阻断安装主流程，验收页可按目录补登记
        }
    }

    /**
     * 确保登记行存在（验收动作前调用；不存在则按目录里的 theme.json 补一条待验收行）
     */
    public static function ensureRow(string $code): ?array
    {
        $row = self::find($code);
        if ($row) {
            return $row;
        }

        $meta = self::readThemeJson($code);
        self::markPending($code, $meta);

        return self::find($code);
    }

    /**
     * 验收通过 → 进入前台可选列表
     */
    public static function accept(string $code, int $adminId): array
    {
        $row = self::ensureRow($code);
        if (!$row) {
            return ['success' => false, 'message' => '主题登记失败，请确认主题目录存在'];
        }

        Db::name('theme_info')->where('id', (int) $row['id'])->update([
            'review_status' => self::STATUS_APPROVED,
            'review_by'     => $adminId,
            'review_time'   => time(),
            'review_remark' => '',
            'update_time'   => time(),
        ]);

        return ['success' => true, 'message' => '已验收，主题可在前台选用'];
    }

    /**
     * 验收驳回 → 保持前台不可选，记录原因
     */
    public static function reject(string $code, int $adminId, string $remark): array
    {
        $remark = trim($remark);
        if ($remark === '') {
            return ['success' => false, 'message' => '请填写驳回原因'];
        }

        $row = self::ensureRow($code);
        if (!$row) {
            return ['success' => false, 'message' => '主题登记失败，请确认主题目录存在'];
        }

        Db::name('theme_info')->where('id', (int) $row['id'])->update([
            'review_status' => self::STATUS_REJECTED,
            'review_by'     => $adminId,
            'review_time'   => time(),
            'review_remark' => mb_substr($remark, 0, 255),
            'update_time'   => time(),
        ]);

        return ['success' => true, 'message' => '已驳回，该主题不会出现在前台'];
    }

    /**
     * 删除登记行（卸载主题时调用）
     */
    public static function forget(string $code): void
    {
        try {
            Db::name('theme_info')->where('code', $code)->where('type', self::TYPE_FRONTEND)->delete();
        } catch (\Throwable $e) {
            // 忽略
        }
    }

    /**
     * 读取主题目录里的 theme.json 元数据
     */
    private static function readThemeJson(string $code): array
    {
        $file = root_path() . 'template' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $code . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($file)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($file), true);
        if (!is_array($json)) {
            return [];
        }

        return [
            'name'        => (string) ($json['name'] ?? $code),
            'version'     => (string) ($json['version'] ?? '1.0.0'),
            'description' => (string) ($json['description'] ?? ''),
        ];
    }
}
