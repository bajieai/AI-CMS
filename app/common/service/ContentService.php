<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\Content;
use app\common\model\ContentExt;
use app\common\model\ContentTag;
use app\common\service\CacheService;
use think\facade\Config;

/**
 * 内容服务
 * TagLib标签编译后委托此类进行数据查询
 */
class ContentService
{
    /**
     * 获取内容列表（后台分页）
     */
    public function getList(array $params = [], int $pageSize = 20)
    {
        $query = Content::with('cate')->where('status', '>=', -1);

        if (!empty($params['type'])) {
            $query->where('type', (int) $params['type']);
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }
        if (!empty($params['cate_id'])) {
            $query->where('cate_id', (int) $params['cate_id']);
        }
        if (!empty($params['keyword'])) {
            $query->where('title', 'like', '%' . $params['keyword'] . '%');
        }

        return $query->order('id', 'desc')->paginate($pageSize);
    }

    /**
     * 获取信息列表（前台模板标签使用，TagLib委托调用）
     * @param string $type 信息类型标识（product/case/news/download/job/page）
     * @param int $limit 查询数量（不分页时使用）
     * @param string $order 排序规则
     * @param int $page 当前页码（>0 则启用分页）
     * @param int $pageSize 每页数量（分页时使用）
     * @return \think\Collection|\think\Paginator
     */
    public function getInfolist(string $type = '', int $limit = 10, string $order = 'id desc', int $page = 0, int $pageSize = 10)
    {
        $typeMap = [
            'product' => 1,
            'case' => 2,
            'news' => 3,
            'download' => 4,
            'job' => 5,
            'page' => 6,
        ];

        $query = Content::where('status', 2); // 仅已发布

        if (!empty($type) && isset($typeMap[$type])) {
            $query->where('type', $typeMap[$type]);
        }

        if ($page > 0) {
            return $query->order($order)->paginate($pageSize, false, ['page' => $page]);
        }

        return $query->order($order)->limit($limit)->select();
    }

    /**
     * 创建内容
     */
    public function create(array $data): bool
    {
        $content = new Content();
        
        // 处理扩展字段
        $extData = $data['ext'] ?? [];
        unset($data['ext']);
        
        // 处理标签
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        // 设置时间戳
        $data['create_time'] = time();
        $data['update_time'] = time();
        $data['user_id'] = session('user_id') ?: 0;

        if (!$content->save($data)) {
            return false;
        }

        // 保存扩展数据
        if (!empty($extData)) {
            $ext = new ContentExt();
            $ext->content_id = $content->id;
            $ext->type = $content->type;
            $ext->data = $extData;
            $ext->save();
        }

        // 保存标签关联
        if (!empty($tagIds)) {
            $this->syncTags($content->id, $tagIds);
        }

        // 清除内容相关缓存
        $cacheService = new CacheService();
        $cacheService->clearByTag(Config::get('cache.tag.content', 'i8j_content'));

        return true;
    }

    /**
     * 更新内容
     */
    public function update(int $id, array $data): bool
    {
        $content = Content::find($id);
        if (empty($content)) {
            return false;
        }

        // 处理扩展字段
        $extData = $data['ext'] ?? [];
        unset($data['ext']);
        
        // 处理标签
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        $data['update_time'] = time();

        if (!$content->save($data)) {
            return false;
        }

        // 更新扩展数据
        if (!empty($extData)) {
            $ext = ContentExt::where('content_id', $id)->where('type', $content->type)->find();
            if ($ext) {
                $ext->data = $extData;
                $ext->save();
            } else {
                $ext = new ContentExt();
                $ext->content_id = $id;
                $ext->type = $content->type;
                $ext->data = $extData;
                $ext->save();
            }
        }

        // 同步标签
        if (!empty($tagIds)) {
            $this->syncTags($id, $tagIds);
        }

        // 清除内容相关缓存
        $cacheService = new CacheService();
        $cacheService->clearByTag(Config::get('cache.tag.content', 'i8j_content'));

        return true;
    }

    /**
     * 同步标签关联
     */
    protected function syncTags(int $contentId, array $tagIds): void
    {
        // 删除旧关联
        ContentTag::where('content_id', $contentId)->delete();
        
        // 创建新关联
        $data = [];
        foreach ($tagIds as $tagId) {
            $data[] = ['content_id' => $contentId, 'tag_id' => (int) $tagId];
        }
        if (!empty($data)) {
            (new ContentTag())->saveAll($data);
        }
    }
}
