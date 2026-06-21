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
 * 模板安装日志模型 - V2.9.24 G-4
 * 用于统计看板数据收集
 */
class TemplateInstallLog extends Model
{
    protected $name = 'template_install_log';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'template_id' => 'integer',
        'member_id' => 'integer',
        'action' => 'integer',  // 1=install, 2=uninstall, 3=switch, 4=migrate(基线)
        'source' => 'integer',  // 1=store, 2=upload, 3=restore
    ];

    // 动作常量
    const ACTION_INSTALL = 1;
    const ACTION_UNINSTALL = 2;
    const ACTION_SWITCH = 3;
    const ACTION_MIGRATE = 4;  // V2.9.24 基线迁移标记

    // 来源常量
    const SOURCE_STORE = 1;
    const SOURCE_UPLOAD = 2;
    const SOURCE_RESTORE = 3;

    public static array $actionMap = [
        self::ACTION_INSTALL => '安装',
        self::ACTION_UNINSTALL => '卸载',
        self::ACTION_SWITCH => '切换',
        self::ACTION_MIGRATE => '基线迁移',
    ];

    public static array $sourceMap = [
        self::SOURCE_STORE => '商店',
        self::SOURCE_UPLOAD => '上传',
        self::SOURCE_RESTORE => '恢复',
    ];

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(TemplateStore::class, 'template_id', 'id')
            ->field('id, name, banner_url');
    }

    /**
     * 关联会员
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id')
            ->field('id, nickname');
    }

    /**
     * 查询作用域 — 按动作
     */
    public function scopeByAction($query, int $action)
    {
        return $query->where('action', $action);
    }

    /**
     * 查询作用域 — 按日期范围
     */
    public function scopeByDateRange($query, string $start, string $end)
    {
        $startTs = strtotime($start . ' 00:00:00');
        $endTs = strtotime($end . ' 23:59:59');
        return $query->whereBetween('create_time', [$startTs, $endTs]);
    }

    /**
     * 记录安装日志
     */
    public static function record(int $templateId, int $memberId, int $action, int $source = self::SOURCE_STORE, array $extra = []): self
    {
        return self::create([
            'template_id' => $templateId,
            'member_id' => $memberId,
            'action' => $action,
            'source' => $source,
            'ip' => request()->ip() ?: '',
            'extra' => json_encode($extra),
            'create_time' => time(),
        ]);
    }
}
