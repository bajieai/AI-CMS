<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 会员降级日志模型 - V2.9.4新增
 * V2.9.5: 复用为等级历史时间线（支持upgrade/downgrade/manual）
 */
class MemberDowngradeLog extends Model
{
    protected $name = 'member_downgrade_log';
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'user_id' => 'integer',
        'from_level' => 'integer',
        'to_level' => 'integer',
        'notified' => 'integer',
    ];

    /**
     * V2.9.5 获取会员等级变更时间线
     * @param int $memberId 会员ID
     * @param int $limit 返回条数
     * @return array
     */
    public static function getTimeline(int $memberId, int $limit = 50): array
    {
        $logs = self::where('user_id', $memberId)
            ->order('create_time', 'desc')
            ->limit($limit)
            ->select();

        $levelNames = MemberLevel::column('name', 'id');
        $result = [];

        foreach ($logs as $log) {
            $action = $log->action ?: 'manual';
            $isUpgrade = $log->to_level > $log->from_level;

            $result[] = [
                'id' => $log->id,
                'date' => date('Y-m-d H:i', $log->create_time),
                'timestamp' => $log->create_time,
                'action' => $action,
                'action_text' => self::getActionText($action, $isUpgrade),
                'from_level' => [
                    'id' => $log->from_level,
                    'name' => $levelNames[$log->from_level] ?? '未知等级',
                ],
                'to_level' => [
                    'id' => $log->to_level,
                    'name' => $levelNames[$log->to_level] ?? '未知等级',
                ],
                'is_upgrade' => $isUpgrade,
                'trigger_condition' => $log->trigger_condition ?: '',
            ];
        }

        return $result;
    }

    /**
     * 获取操作类型文本
     */
    protected static function getActionText(string $action, bool $isUpgrade): string
    {
        $map = [
            'auto_upgrade'   => '自动升级',
            'auto_downgrade' => '自动降级',
            'manual'         => '手动调整',
        ];
        return $map[$action] ?? ($isUpgrade ? '等级升级' : '等级调整');
    }
}
