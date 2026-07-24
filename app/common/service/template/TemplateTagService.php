<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplateTag;
use app\common\model\TemplateTagRelation;
use think\facade\Db;
use think\facade\Cache;

class TemplateTagService
{
    private const CACHE_TAG = 'template_tag';
    private const CACHE_TTL = 600;

    public function create(string $name, string $type, string $color = '#1890ff', int $sort = 99): TemplateTag
    {
        $tag = TemplateTag::create([
            'name' => $name, 'type' => $type, 'color' => $color, 'sort' => $sort, 'status' => 1,
        ]);
        $this->clearCache();
        return $tag;
    }

    public function update(int $tagId, array $data): bool
    {
        $tag = TemplateTag::find($tagId);
        if (!$tag) return false;
        $tag->save($data);
        $this->clearCache();
        return true;
    }

    public function delete(int $tagId): bool
    {
        Db::transaction(function () use ($tagId) {
            TemplateTagRelation::where('tag_id', $tagId)->delete();
            TemplateTag::destroy($tagId);
        });
        $this->clearCache();
        return true;
    }

    public function getGroupedTags(): array
    {
        return Cache::remember(
            'template_tag:grouped',
            function () {
                $all = TemplateTag::where('status', 1)->order('sort', 'asc')->select()->toArray();
                $grouped = [];
                foreach ($all as $t) {
                    $grouped[$t['type']][] = $t;
                }
                return $grouped;
            },
            self::CACHE_TTL
        );
    }

    public function attachTag(int $templateId, int $tagId): bool
    {
        $exists = TemplateTagRelation::where('template_id', $templateId)->where('tag_id', $tagId)->find();
        if (!$exists) {
            TemplateTagRelation::create(['template_id' => $templateId, 'tag_id' => $tagId]);
            $this->clearCache();
        }
        return true;
    }

    public function syncTags(int $templateId, array $tagIds): void
    {
        Db::transaction(function () use ($templateId, $tagIds) {
            TemplateTagRelation::where('template_id', $templateId)->delete();
            $data = [];
            foreach ($tagIds as $tagId) {
                $data[] = ['template_id' => $templateId, 'tag_id' => $tagId];
            }
            if (!empty($data)) (new TemplateTagRelation())->saveAll($data);
        });
        $this->clearCache();
    }

    public function getTemplateTags(int $templateId): array
    {
        $relations = TemplateTagRelation::where('template_id', $templateId)->select();
        $tagIds = $relations->column('tag_id');
        if (empty($tagIds)) return [];
        return TemplateTag::whereIn('id', $tagIds)->order('sort', 'asc')->select()->toArray();
    }

    public function batchAssignTags(array $templateIds, array $tagIds): void
    {
        Db::transaction(function () use ($templateIds, $tagIds) {
            foreach ($templateIds as $tplId) {
                foreach ($tagIds as $tagId) {
                    $exists = TemplateTagRelation::where('template_id', $tplId)->where('tag_id', $tagId)->find();
                    if (!$exists) {
                        TemplateTagRelation::create(['template_id' => $tplId, 'tag_id' => $tagId]);
                    }
                }
            }
        });
        $this->clearCache();
    }

    private function clearCache(): void
    {
        Cache::clear();
    }
}
