<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 操作日志模型
 */
class OperationLog extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_operation_logs';

    /**
     * 主键
     */
    protected $pk = 'id';

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = false;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 时间戳格式
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 类型转换
     */
    protected $type = [
        'user_id' => 'integer',
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 记录操作
     */
    public static function record(
        int $userId,
        string $username,
        string $action,
        string $content = '',
        string $ip = '',
        string $userAgent = '',
        array $params = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'username' => $username,
            'action' => $action,
            'content' => $content,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取用户操作日志
     */
    public static function getUserLogs(int $userId, int $limit = 50): array
    {
        return self::where('user_id', '=', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取最近操作日志
     */
    public static function getRecentLogs(int $limit = 100): array
    {
        return self::order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 搜索日志
     */
    public static function search(array $conditions, int $page = 1, int $perPage = 20): array
    {
        $query = self::whereRaw('1=1');
        
        if (!empty($conditions['user_id'])) {
            $query->where('user_id', '=', $conditions['user_id']);
        }
        
        if (!empty($conditions['username'])) {
            $query->where('username', 'like', '%' . $conditions['username'] . '%');
        }
        
        if (!empty($conditions['action'])) {
            $query->where('action', 'like', '%' . $conditions['action'] . '%');
        }
        
        if (!empty($conditions['start_date'])) {
            $query->where('created_at', '>=', $conditions['start_date']);
        }
        
        if (!empty($conditions['end_date'])) {
            $query->where('created_at', '<=', $conditions['end_date'] . ' 23:59:59');
        }
        
        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($page, $perPage)
            ->select();
        
        return [
            'list' => $list->toArray(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * 获取日志信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'action' => $this->action,
            'content' => $this->content,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'params' => json_decode($this->params, true) ?? [],
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 清理旧日志
     */
    public static function cleanup(int $days = 90): int
    {
        $beforeDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return self::where('created_at', '<', $beforeDate)->delete();
    }
}
