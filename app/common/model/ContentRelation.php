<?php
declare(strict_types=1);

namespace app\common\model;

use think\Model;

/**
 * 内容关系 — V2.9.36 CM-3
 */
class ContentRelation extends Model
{
    protected $name = 'content_relation';
    protected $pk = 'id';
    protected $autoWriteTimestamp = 'datetime';

    protected $json = ['relation_data'];
    protected $jsonAssoc = true;

    public const TYPE_ONE_TO_ONE = 'one_to_one';
    public const TYPE_ONE_TO_MANY = 'one_to_many';
    public const TYPE_MANY_TO_MANY = 'many_to_many';
    public const TYPE_PARENT_CHILD = 'parent_child';
    public const TYPE_RECOMMENDED = 'recommended';
    public const TYPE_RELATED = 'related';

    public static function getTypeList(): array
    {
        return [
            ['type' => self::TYPE_ONE_TO_ONE, 'label' => '一对一'],
            ['type' => self::TYPE_ONE_TO_MANY, 'label' => '一对多'],
            ['type' => self::TYPE_MANY_TO_MANY, 'label' => '多对多'],
            ['type' => self::TYPE_PARENT_CHILD, 'label' => '父子关系'],
            ['type' => self::TYPE_RECOMMENDED, 'label' => '推荐关系'],
            ['type' => self::TYPE_RELATED, 'label' => '相关关系'],
        ];
    }
}
