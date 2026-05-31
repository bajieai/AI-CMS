<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace app\common\model;

use app\common\model\User;
use think\Model;

/**
 * 模板开发者上传审核模型 - V2.9.12
 *
 * 状态：0待审核 1已通过 2已拒绝 3需修改
 */
class TemplateDevUpload extends Model
{
    protected $name = 'template_dev_upload';
    protected $pk = 'id';

    public const STATUS_PENDING  = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;
    public const STATUS_NEED_FIX = 3;

    protected $schema = [
        'id'            => 'int',
        'member_id'     => 'int',
        'theme_slug'    => 'string',
        'theme_name'    => 'string',
        'version'       => 'string',
        'file_path'     => 'string',
        'manifest_json' => 'text',
        'status'        => 'int',
        'audit_remark'  => 'string',
        'auditor_id'    => 'int',
        'create_time'   => 'int',
        'update_time'   => 'int',
        'audit_time'    => 'int',
    ];

    protected $autoWriteTimestamp = true;

    /**
     * 状态文本
     */
    public function getStatusTextAttr($value): string
    {
        $map = [
            self::STATUS_PENDING  => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_NEED_FIX => '需修改',
        ];
        return $map[$value] ?? '未知';
    }

    /**
     * 获取某开发者的上传记录
     */
    public static function getByMember(int $memberId, int $page = 1, int $limit = 20): array
    {
        return self::where('member_id', $memberId)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取待审核列表
     */
    public static function getPending(int $page = 1, int $limit = 20): array
    {
        return self::where('status', self::STATUS_PENDING)
            ->order('create_time', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 解析manifest
     */
    public function getManifest(): array
    {
        if (empty($this->manifest_json)) return [];
        return json_decode($this->manifest_json, true) ?: [];
    }

    /**
     * 关联会员（开发者）
     */
    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
