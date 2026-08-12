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

use app\common\support\ContentTypeMap;

use think\Model;

/**
 * 分类模型
 * 注意：模型名Cate，表名cate（不使用ContentCategory全称）
 */
class Cate extends Model
{
    protected $name = 'cate';

    // 自动时间戳
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'type' => 'integer',
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'content_id' => 'integer',
    ];

    // 允许批量赋值的字段
    // V2.9.29 C-1: 增加 content_model_code, list_template, detail_template
    // V2.9.42: 增加 content_id (单页内容关联)
    protected $field = ['name', 'type', 'parent_id', 'sort', 'status', 'seo_title', 'seo_keywords', 'seo_description', 'default_style', 'model_id', 'content_model_code', 'list_template', 'detail_template', 'content_id', 'seo_url'];

    /**
     * 获取URL（模型获取器）
     * URL体系：
     *   有seo_url：/{seo_url}  如 /news, /products, /about
     *   无seo_url：/{type_slug}?cate_id={id} 如 /info?cate_id=3
     *   单页有seo_url：/{seo_url}  如 /about
     *   单页无seo_url：/page/{id}  如 /page/6
     */
    public function getUrlAttr($value, $data): string
    {
        $seoUrl = $data['seo_url'] ?? '';
        $type = $data['type'] ?? 3;

        // 单页类型
        if ($type === ContentTypeMap::pageType()) {
            if (!empty($seoUrl)) {
                return "/{$seoUrl}";
            }
            return "/page/{$data['id']}";
        }

        // 其他类型：有英文名用单段URL
        if (!empty($seoUrl)) {
            return "/{$seoUrl}";
        }

        // 无英文名回退到旧格式
        $typeSlug = ContentTypeMap::slugByType()[$type] ?? 'info';
        return "/{$typeSlug}?cate_id={$data['id']}";
    }

    /**
     * 关联子分类
     */
    public function children()
    {
        return $this->hasMany(Cate::class, 'parent_id');
    }

    /**
     * 关联父分类
     */
    public function parent()
    {
        return $this->belongsTo(Cate::class, 'parent_id');
    }

    /**
     * V2.9.42: 关联单页内容（type=6时使用）
     */
    public function pageContent()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
