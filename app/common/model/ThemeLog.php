<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 主题操作日志模型 - V3.1 Sprint 16
 *
 * action: install / rollback / update / rate / switch / uninstall
 */
class ThemeLog extends Model
{
    protected $name = 'theme_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'theme_id' => 'integer',
        'user_id'  => 'integer',
        'detail'   => 'json',
    ];

    /**
     * 记录日志（静态方法便捷调用）
     */
    public static function record(int $themeId, string $action, int $userId = 0, array $detail = []): void
    {
        try {
            self::create([
                'theme_id'    => $themeId,
                'action'      => $action,
                'user_id'     => $userId,
                'detail'      => $detail,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // 日志记录失败不影响主业务
            \think\facade\Log::warning('ThemeLog记录失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取主题的日志列表
     */
    public static function getThemeLogs(int $themeId, int $limit = 50): array
    {
        return self::where('theme_id', $themeId)
            ->order('create_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取全部日志（分页）
     */
    public static function getAllLogs(array $filter = [], int $page = 1, int $limit = 20): array
    {
        $query = self::order('create_time', 'desc');

        if (!empty($filter['action'])) {
            $query->where('action', $filter['action']);
        }
        if (!empty($filter['theme_id'])) {
            $query->where('theme_id', (int) $filter['theme_id']);
        }
        if (!empty($filter['user_id'])) {
            $query->where('user_id', (int) $filter['user_id']);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        // 补充主题名称
        $themeIds = array_unique(array_column($list, 'theme_id'));
        $themeNames = ThemeInfo::whereIn('id', $themeIds)->column('name', 'id');
        foreach ($list as &$item) {
            $item['theme_name'] = $themeNames[$item['theme_id']] ?? '未知主题';
        }

        return ['total' => $total, 'list' => $list];
    }

    /**
     * 获取操作类型中文映射
     */
    public static function getActionMap(): array
    {
        return [
            'install'   => '安装',
            'rollback'  => '回滚',
            'update'    => '更新',
            'rate'      => '评分',
            'switch'    => '切换',
            'uninstall' => '卸载',
        ];
    }
}
