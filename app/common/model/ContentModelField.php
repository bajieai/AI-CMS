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
 * 内容模型扩展字段 (V2.9.20 A-1)
 * [修正：小扣-3] default_value 改为 text 类型
 */
class ContentModelField extends Model
{
    protected $name = 'content_model_field';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $type = [
        'model_id' => 'integer',
        'required' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    // 支持的字段类型
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_NUMBER = 'number';
    const TYPE_SELECT = 'select';
    const TYPE_RADIO = 'radio';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_DATE = 'date';
    const TYPE_IMAGE = 'image';
    const TYPE_FILE = 'file';

    public static array $typeMap = [
        self::TYPE_TEXT => '单行文本',
        self::TYPE_TEXTAREA => '多行文本',
        self::TYPE_NUMBER => '数字',
        self::TYPE_SELECT => '下拉选择',
        self::TYPE_RADIO => '单选',
        self::TYPE_CHECKBOX => '多选',
        self::TYPE_DATE => '日期',
        self::TYPE_IMAGE => '图片',
        self::TYPE_FILE => '文件',
    ];

    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data): string
    {
        return self::$typeMap[$data['type']] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data): string
    {
        $map = [self::STATUS_DISABLED => '禁用', self::STATUS_ENABLED => '启用'];
        return $map[$data['status']] ?? '未知';
    }

    /**
     * 解析选项(JSON)
     */
    public function getParsedOptionsAttr($value, $data): array
    {
        if (empty($data['options'])) {
            return [];
        }
        $decoded = json_decode($data['options'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 关联模型
     */
    public function contentModel()
    {
        return $this->belongsTo(ContentModel::class, 'model_id');
    }

    /**
     * 根据模型ID获取有效字段列表
     */
    public static function getFieldsByModelId(int $modelId): array
    {
        return self::where('model_id', $modelId)
            ->where('status', self::STATUS_ENABLED)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }
}
