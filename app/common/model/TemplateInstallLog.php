<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | V2.9.31 Sprint T3: 模板安装日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 模板安装日志模型 - V2.9.31 T3-2
 */
class TemplateInstallLog extends Model
{
    protected $name = 'template_install_log';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'store_id' => 'integer',
        'member_id' => 'integer',
        'result' => 'integer',
    ];

    // 动作常量
    const ACTION_INSTALL = 'install';
    const ACTION_ACTIVATE = 'activate';
    const ACTION_UNINSTALL = 'uninstall';
    const ACTION_UPGRADE = 'upgrade';
    const ACTION_MIGRATE = 'migrate';

    /**
     * 关联模板商店
     */
    public function store()
    {
        return $this->belongsTo(TemplateStore::class, 'store_id');
    }

    /**
     * 关联用户
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * 查询作用域 — 指定模板
     */
    public function scopeByStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * 查询作用域 — 指定用户
     */
    public function scopeByMember($query, int $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * 查询作用域 — 成功记录
     */
    public function scopeSuccess($query)
    {
        return $query->where('result', 1);
    }
}
