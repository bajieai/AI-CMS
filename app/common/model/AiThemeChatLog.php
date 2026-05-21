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

namespace app\common\model;

use think\Model;

/**
 * AI主题多轮对话记录模型 - V3.0 Phase 3
 * 对应表: i8j_ai_theme_chat_log
 *
 * 记录每次增量修改的用户输入、AI返回、变更文件列表
 */
class AiThemeChatLog extends Model
{
    protected $name = 'ai_theme_chat_log';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'record_id'         => 'integer',
        'version'           => 'integer',
        'user_id'           => 'integer',
        'role'              => 'string',
        'changed_files'     => 'json',
        'prompt_tokens'     => 'integer',
        'completion_tokens' => 'integer',
    ];

    // 角色常量
    public const ROLE_USER   = 'user';
    public const ROLE_AI     = 'ai';
    public const ROLE_SYSTEM = 'system';

    /**
     * 获取某条记录的全部对话日志（按版本分组）
     */
    public static function getLogsByRecord(int $recordId, ?int $version = null): array
    {
        $query = self::where('record_id', $recordId)
            ->order('created_at', 'asc');

        if ($version !== null) {
            $query->where('version', $version);
        }

        return $query->select()->toArray();
    }

    /**
     * 获取最近N轮对话（用于上下文窗口）
     */
    public static function getRecentRounds(int $recordId, int $limit = 10): array
    {
        return self::where('record_id', $recordId)
            ->whereIn('role', [self::ROLE_USER, self::ROLE_AI])
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 记录一条对话消息
     */
    public static function logMessage(
        int $recordId,
        int $version,
        int $userId,
        string $role,
        string $content,
        ?array $changedFiles = null,
        ?int $promptTokens = null,
        ?int $completionTokens = null
    ): int {
        $data = [
            'record_id'         => $recordId,
            'version'           => $version,
            'user_id'           => $userId,
            'role'              => $role,
            'content'           => $content,
            'changed_files'     => $changedFiles,
            'prompt_tokens'     => $promptTokens,
            'completion_tokens' => $completionTokens,
        ];

        $model = self::create($data);
        return (int) $model->id;
    }

    /**
     * 获取某版本的变更文件列表
     */
    public static function getChangedFiles(int $recordId, int $version): array
    {
        $logs = self::where('record_id', $recordId)
            ->where('version', $version)
            ->where('role', self::ROLE_AI)
            ->column('changed_files');

        $files = [];
        foreach ($logs as $item) {
            if (!empty($item) && is_array($item)) {
                $files = array_merge($files, $item);
            }
        }
        return array_values(array_unique($files));
    }

    /**
     * 获取对话统计（Token消耗）
     */
    public static function getTokenStats(int $recordId): array
    {
        $result = self::where('record_id', $recordId)
            ->field([
                'SUM(prompt_tokens) as total_prompt',
                'SUM(completion_tokens) as total_completion',
                'COUNT(DISTINCT version) as version_count',
            ])
            ->find();

        return [
            'total_prompt'     => (int) ($result->total_prompt ?? 0),
            'total_completion' => (int) ($result->total_completion ?? 0),
            'version_count'    => (int) ($result->version_count ?? 0),
        ];
    }
}
