<?php
declare(strict_types=1);

namespace app\common\service\template;

use app\common\model\TemplatePack;
use app\common\model\TemplatePackItem;
use app\common\model\TemplateStore;
use think\facade\Cache;

/**
 * 模板包管理服务 — V2.9.28 M-4
 */
class TemplatePackService
{
    private const CACHE_TAG = 'template_pack';

    /**
     * 获取模板包列表
     */
    public function getList(array $params = [], int $page = 1, int $limit = 20): array
    {
        $page = max(1, $page);
        $query = TemplatePack::with('items');

        if (!empty($params['keyword'])) {
            $query->where('name', 'like', '%' . $params['keyword'] . '%');
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int)$params['status']);
        }

        $total = $query->count();
        $list = $query->order('sort', 'asc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 获取模板包详情
     */
    public function getDetail(int $id): ?array
    {
        $pack = TemplatePack::with(['items.template'])->find($id);
        return $pack ? $pack->toArray() : null;
    }

    /**
     * 创建/编辑模板包
     */
    public function save(array $data, int $id = 0): array
    {
        $templateIds = $data['template_ids'] ?? [];
        unset($data['template_ids']);

        if ($id > 0) {
            $pack = TemplatePack::find($id);
            if (!$pack) {
                return ['success' => false, 'message' => '模板包不存在'];
            }
            $pack->save($data);
        } else {
            $pack = TemplatePack::create($data);
            $id = $pack->id;
        }

        // 更新关联模板
        if (!empty($templateIds)) {
            // 先删除旧关联
            TemplatePackItem::where('pack_id', $id)->delete();
            // 插入新关联
            $sort = 1;
            foreach ($templateIds as $tid) {
                TemplatePackItem::create([
                    'pack_id' => $id,
                    'template_id' => (int)$tid,
                    'sort' => $sort++,
                ]);
            }
            // 计算原价合计
            $originalPrice = TemplateStore::whereIn('id', $templateIds)->sum('price');
            $pack->original_price = $originalPrice;
            $pack->save();
        }

        Cache::clear();
        return ['success' => true, 'message' => '保存成功', 'id' => $id];
    }

    /**
     * 删除模板包
     */
    public function delete(int $id): array
    {
        TemplatePackItem::where('pack_id', $id)->delete();
        TemplatePack::destroy($id);
        Cache::clear();
        return ['success' => true, 'message' => '删除成功'];
    }
}
