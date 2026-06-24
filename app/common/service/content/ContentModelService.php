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

namespace app\common\service\content;

use app\common\model\ContentModel;
use app\common\model\Cate;
use think\facade\Cache;
use think\facade\Db;

/**
 * 内容模型管理服务 (V2.9.29 C-1)
 * 
 * 提供模型查询、栏目-模型关联、模板Fallback链等核心逻辑
 * 所有查询使用Cache::tag('content_model')缓存
 */
class ContentModelService
{
    private const CACHE_TAG = 'content_model';
    private const CACHE_TTL = 3600;

    /**
     * 根据code获取内容模型（带缓存）
     */
    public function getByCode(string $code): ?ContentModel
    {
        return Cache::tag(self::CACHE_TAG)->remember(
            'model_by_code_' . $code,
            function () use ($code) {
                return ContentModel::where('code', $code)
                    ->where('status', ContentModel::STATUS_ENABLED)
                    ->find();
            },
            self::CACHE_TTL
        );
    }

    /**
     * 根据ID获取内容模型（带缓存）
     */
    public function getById(int $id): ?ContentModel
    {
        return Cache::tag(self::CACHE_TAG)->remember(
            'model_by_id_' . $id,
            function () use ($id) {
                return ContentModel::find($id);
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取所有启用的内容模型
     */
    public function getAllEnabled(): array
    {
        return Cache::tag(self::CACHE_TAG)->remember(
            'all_enabled_models',
            function () {
                return ContentModel::where('status', ContentModel::STATUS_ENABLED)
                    ->order('sort', 'asc')
                    ->select()
                    ->toArray();
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取栏目的内容模型code
     * 
     * 优先级：cate.content_model_code → cate.model_id → 默认'article'
     */
    public function getCateModelCode(int $cateId): string
    {
        $cate = Cache::tag(self::CACHE_TAG)->remember(
            'cate_model_code_' . $cateId,
            function () use ($cateId) {
                return Cate::where('id', $cateId)
                    ->field('id, content_model_code, model_id')
                    ->find();
            },
            self::CACHE_TTL
        );

        if (!$cate) {
            return 'article';
        }

        // 优先使用 content_model_code
        if (!empty($cate['content_model_code'])) {
            return $cate['content_model_code'];
        }

        // 其次通过 model_id 查找 code
        if (!empty($cate['model_id'])) {
            $model = $this->getById($cate['model_id']);
            if ($model && $model->code) {
                return $model->code;
            }
        }

        return 'article';
    }

    /**
     * 获取栏目的列表模板（Fallback链核心）
     * 
     * Fallback链：栏目自定义 → 模型默认 → 系统默认(list)
     */
    public function resolveListTemplate(int $cateId): string
    {
        $cacheKey = 'list_template_' . $cateId;

        return Cache::tag(self::CACHE_TAG)->remember(
            $cacheKey,
            function () use ($cateId) {
                $cate = Cate::where('id', $cateId)
                    ->field('id, content_model_code, model_id, list_template')
                    ->find();

                if (!$cate) {
                    return 'list';
                }

                // 1. 栏目自定义模板优先
                if (!empty($cate['list_template'])) {
                    return $cate['list_template'];
                }

                // 2. 模型默认模板
                $modelCode = $this->getCateModelCode($cateId);
                $model = $this->getByCode($modelCode);
                if ($model && !empty($model->default_list_template)) {
                    return $model->default_list_template;
                }

                // 3. 系统默认
                return 'list';
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取栏目的详情模板（Fallback链核心）
     * 
     * Fallback链：栏目自定义 → 模型默认 → 系统默认(detail)
     */
    public function resolveDetailTemplate(int $cateId): string
    {
        $cacheKey = 'detail_template_' . $cateId;

        return Cache::tag(self::CACHE_TAG)->remember(
            $cacheKey,
            function () use ($cateId) {
                $cate = Cate::where('id', $cateId)
                    ->field('id, content_model_code, model_id, detail_template')
                    ->find();

                if (!$cate) {
                    return 'detail';
                }

                // 1. 栏目自定义模板优先
                if (!empty($cate['detail_template'])) {
                    return $cate['detail_template'];
                }

                // 2. 模型默认模板
                $modelCode = $this->getCateModelCode($cateId);
                $model = $this->getByCode($modelCode);
                if ($model && !empty($model->default_detail_template)) {
                    return $model->default_detail_template;
                }

                // 3. 系统默认
                return 'detail';
            },
            self::CACHE_TTL
        );
    }

    /**
     * 获取模型预置数据（5大模型）
     */
    public function getPresetModels(): array
    {
        return [
            'article'  => ['name' => '文章模型', 'icon' => 'bi bi-file-text', 'description' => '标准文章模型'],
            'image'    => ['name' => '图片模型', 'icon' => 'bi bi-images', 'description' => '图片图集模型'],
            'download' => ['name' => '下载模型', 'icon' => 'bi bi-download', 'description' => '下载资源模型'],
            'product'  => ['name' => '产品模型', 'icon' => 'bi bi-box', 'description' => '产品展示模型'],
            'video'    => ['name' => '视频模型', 'icon' => 'bi bi-play-btn', 'description' => '视频播放模型'],
        ];
    }

    /**
     * 清除模型相关缓存
     */
    public function clearCache(): void
    {
        Cache::tag(self::CACHE_TAG)->clear();
    }

    /**
     * 清除指定栏目的模板缓存
     */
    public function clearCateTemplateCache(int $cateId): void
    {
        Cache::tag(self::CACHE_TAG)->delete('list_template_' . $cateId);
        Cache::tag(self::CACHE_TAG)->delete('detail_template_' . $cateId);
        Cache::tag(self::CACHE_TAG)->delete('cate_model_code_' . $cateId);
    }
}
