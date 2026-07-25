<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 内容模型 — V2.9.36 CM-2
 */
class ContentModel extends Model
{
    use SoftDelete;

    protected $name = 'content_model';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';

    protected $json = ['model_config', 'template_config'];
    protected $jsonAssoc = true;

    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    /**
     * 获取启用的模型列表
     */
    public static function getEnabledModels(): array
    {
        return self::where('is_enabled', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 根据标识获取模型
     */
    public static function getByIdentifier(string $identifier): ?array
    {
        $model = self::where('model_identifier', $identifier)
            ->find();
        return $model ? $model->toArray() : null;
    }

    /**
     * 根据类型获取默认模型（V2.9.20 兼容方法）
     */
    public static function getDefaultByType(int $type): ?ContentModel
    {
        return self::where('status', 1)
            ->where('type', $type)
            ->order('sort', 'asc')
            ->find();
    }
}
