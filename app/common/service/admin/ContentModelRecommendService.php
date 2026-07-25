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

namespace app\common\service\admin;

use app\common\model\Content;
use app\common\model\ContentModel;
use app\common\model\ContentModelTemplateMap;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * V2.9.27 S-5: 内容模型-模板推荐联动服务
 */
class ContentModelRecommendService
{
    /**
     * 为内容推荐模板
     * @param Content $content 内容对象
     * @param int $limit 推荐数量
     * @return array
     */
    public static function recommendForContent(Content $content, int $limit = 5): array
    {
        $modelId = (int)($content->model_id ?? 0);
        if ($modelId <= 0) {
            return [];
        }

        // 获取内容标签
        $tags = [];
        if ($content->tags) {
            foreach ($content->tags as $tag) {
                $tags[] = $tag->name;
            }
        }

        return ContentModelTemplateMap::getRecommendedTemplates($modelId, $tags, $limit);
    }

    /**
     * 保存模型-模板映射
     * @param array $data 映射数据
     * @return ContentModelTemplateMap
     */
    public static function saveMap(array $data): ContentModelTemplateMap
    {
        $mapId = (int)($data['id'] ?? 0);

        $saveData = [
            'model_id' => (int)($data['model_id'] ?? 0),
            'template_id' => (int)($data['template_id'] ?? 0),
            'tag_match' => $data['tag_match'] ?? [],
            'priority' => (int)($data['priority'] ?? 50),
            'is_default' => (int)($data['is_default'] ?? 0),
            'status' => (int)($data['status'] ?? 1),
        ];

        if ($mapId > 0) {
            $map = ContentModelTemplateMap::find($mapId);
            if ($map) {
                $map->save($saveData);
                self::clearCache($saveData['model_id']);
                return $map;
            }
        }

        $map = ContentModelTemplateMap::create($saveData);
        self::clearCache($saveData['model_id']);
        return $map;
    }

    /**
     * 删除映射
     */
    public static function deleteMap(int $mapId): bool
    {
        $map = ContentModelTemplateMap::find($mapId);
        if (!$map) {
            return false;
        }
        $modelId = $map->model_id;
        $map->delete();
        self::clearCache($modelId);
        return true;
    }

    /**
     * 获取模型的所有映射列表
     */
    public static function getMapsByModel(int $modelId): array
    {
        return ContentModelTemplateMap::where('model_id', $modelId)
            ->order('is_default', 'desc')
            ->order('priority', 'desc')
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 设置默认模板（一个模型只能有一个默认模板）
     */
    public static function setDefault(int $modelId, int $mapId): bool
    {
        // 先取消该模型的所有默认
        ContentModelTemplateMap::where('model_id', $modelId)
            ->where('is_default', 1)
            ->update(['is_default' => 0]);

        // 设置新默认
        $map = ContentModelTemplateMap::find($mapId);
        if (!$map || $map->model_id != $modelId) {
            return false;
        }

        $map->is_default = 1;
        $map->save();
        self::clearCache($modelId);
        return true;
    }

    /**
     * 清除模型模板映射缓存
     */
    public static function clearCache(int $modelId): void
    {
        Cache::clear();
    }
}
