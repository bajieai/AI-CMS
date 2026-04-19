<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 文章状态变更日志模型
 */
class ArticleStatusLog extends Model
{
    /**
     * 表名
     */
    protected $table = 'i8j_aicms_article_status_logs';

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
        'article_id' => 'integer',
        'old_status' => 'integer',
        'new_status' => 'integer',
        'operator_id' => 'integer',
    ];

    /**
     * 文章关联
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * 操作者关联
     */
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * 获取状态文本
     */
    public function getOldStatusText(): string
    {
        return Article::STATUS_TEXT[$this->old_status] ?? '未知';
    }

    /**
     * 获取新状态文本
     */
    public function getNewStatusText(): string
    {
        return Article::STATUS_TEXT[$this->new_status] ?? '未知';
    }

    /**
     * 获取变更描述
     */
    public function getChangeDescription(): string
    {
        return "{$this->getOldStatusText()} → {$this->getNewStatusText()}";
    }

    /**
     * 获取文章的状态变更历史
     */
    public static function getArticleHistory(int $articleId): array
    {
        return self::where('article_id', '=', $articleId)
            ->order('created_at', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取日志信息
     */
    public function getInfo(): array
    {
        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'old_status' => $this->old_status,
            'old_status_text' => $this->getOldStatusText(),
            'new_status' => $this->new_status,
            'new_status_text' => $this->getNewStatusText(),
            'change_description' => $this->getChangeDescription(),
            'reason' => $this->reason,
            'operator_id' => $this->operator_id,
            'operator' => $this->operator ? [
                'id' => $this->operator->id,
                'username' => $this->operator->username,
                'nickname' => $this->operator->nickname,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}
