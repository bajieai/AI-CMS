<?php

// +----------------------------------------------------------------------
// | 八界AI-CMS 内容管理系统
// +----------------------------------------------------------------------
// | Copyright (c) 2026 湖北八界智能技术有限公司 Licensed under the MIT License.
// +----------------------------------------------------------------------
// | Author: 八界AI Team <admin@i8j.cn>
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * V2.9.15: 内容多语言翻译版本模型
 *
 * 对应表: i8j_content_lang
 * 字段: content_id(内容ID), lang(语言代码), translate_status(翻译状态)
 */
class ContentLang extends Model
{
    protected $name = 'content_lang';
    protected $pk = 'id';

    /** 翻译状态常量 */
    public const STATUS_PENDING    = 0; // 等待翻译
    public const STATUS_PROCESSING = 1; // 翻译中
    public const STATUS_COMPLETED  = 2; // 翻译完成
    public const STATUS_FAILED     = 3; // 翻译失败

    /** 状态标签映射 */
    public const STATUS_LABELS = [
        self::STATUS_PENDING    => '待翻译',
        self::STATUS_PROCESSING => '翻译中',
        self::STATUS_COMPLETED  => '已翻译',
        self::STATUS_FAILED     => '失败',
    ];

    /** 状态颜色映射（用于前端badge） */
    public const STATUS_COLORS = [
        self::STATUS_PENDING    => 'default', // 灰色
        self::STATUS_PROCESSING => 'primary', // 蓝色
        self::STATUS_COMPLETED  => 'success', // 绿色
        self::STATUS_FAILED     => 'danger',  // 红色
    ];

    protected $schema = [
        'id'                => 'int',
        'content_id'        => 'int',
        'lang'              => 'string',
        'title'             => 'string',
        'content'           => 'string',
        'description'       => 'string',
        'seo_title'         => 'string',
        'seo_desc'          => 'string',
        'keywords'          => 'string',
        'image_alt'         => 'string',
        'error_msg'         => 'string',
        'translate_status'  => 'int',
        'translate_provider'=> 'string',
        'translate_time'    => 'int',
        'create_time'       => 'int',
        'update_time'       => 'int',
    ];

    protected $type = [
        'id'                => 'integer',
        'content_id'        => 'integer',
        'translate_status'  => 'integer',
        'translate_time'    => 'integer',
        'create_time'       => 'integer',
        'update_time'       => 'integer',
    ];

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 关联内容模型
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * 按内容ID和语言查询
     */
    public static function getByContentIdAndLang(int $contentId, string $lang): ?self
    {
        return self::where('content_id', $contentId)->where('lang', $lang)->find();
    }

    /**
     * 获取内容的翻译版本列表
     */
    public static function getTranslationsByContentId(int $contentId): array
    {
        return self::where('content_id', $contentId)->order('create_time', 'desc')->select()->toArray();
    }

    /**
     * 获取翻译状态标签
     */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->translate_status] ?? '未知';
    }

    /**
     * 获取翻译状态颜色
     */
    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->translate_status] ?? 'default';
    }
}
