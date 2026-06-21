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
 * V2.9.27 S-3e: 内容关系模型
 */
class ContentRelation extends Model
{
    protected $name = 'content_relation';

    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'content_id' => 'integer',
        'relation_id' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 关联主内容
     */
    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * 关联被引用内容
     */
    public function relationContent()
    {
        return $this->belongsTo(Content::class, 'relation_id');
    }

    /**
     * 关系类型映射
     */
    public static array $typeMap = [
        'related'        => '相关内容',
        'previous_next'  => '上下篇',
        'recommended'    => '推荐内容',
        'similar'        => '相似内容',
    ];

    /**
     * 获取关系类型文本
     */
    public function getRelationTypeTextAttr($value, $data): string
    {
        return self::$typeMap[$data['relation_type']] ?? $data['relation_type'];
    }
}
