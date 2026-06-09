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
 * 内容模型定义 (V2.9.20 A-1)
 */
class ContentModel extends Model
{
    protected $name = 'content_model';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'type' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    // 内容类型映射
    public static array $typeMap = [
        1 => '产品',
        2 => '案例',
        3 => '新闻',
        4 => '下载',
        5 => '招聘',
        6 => '单页',
    ];

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [self::STATUS_DISABLED => '禁用', self::STATUS_ENABLED => '启用'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 关联扩展字段
     */
    public function fields()
    {
        return $this->hasMany(ContentModelField::class, 'model_id')
            ->where('status', ContentModelField::STATUS_ENABLED)
            ->order('sort', 'asc');
    }

    /**
     * 根据内容类型获取默认模型
     */
    public static function getDefaultByType(int $type): ?self
    {
        return self::where('type', $type)
            ->where('status', self::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->find();
    }
}
