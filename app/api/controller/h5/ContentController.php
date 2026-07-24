<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\response\Json;

/**
 * H5内容接口
 */
class ContentController extends H5BaseController
{
    /**
     * 内容列表
     */
    public function list(): Json
    {
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        $categoryId = (int)$this->request->param('category_id', 0);
        $tagId = (int)$this->request->param('tag_id', 0);

        $query = Db::name('content')->where('status', 1);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($tagId) {
            $query->whereRaw('FIND_IN_SET(?, tag_ids)', [$tagId]);
        }
        $total = $query->count();
        $list = $query->order('create_time', 'desc')->page($page, $limit)
            ->field('id,title,cover,description,author,view_count,create_time')
            ->select()->toArray();

        return $this->success(['list' => $list, 'total' => $total, 'page' => $page, 'has_more' => ($page * $limit < $total)]);
    }

    /**
     * 内容详情
     */
    public function detail(): Json
    {
        $id = (int)$this->request->param('id', 0);
        if (!$id) {
            return $this->error('参数错误');
        }
        $content = Db::name('content')->where('id', $id)->where('status', 1)->find();
        if (!$content) {
            return $this->error('内容不存在');
        }
        // 增加浏览量
        Db::name('content')->where('id', $id)->inc('view_count')->update();
        // 上一篇/下一篇
        $prev = Db::name('content')->where('id', '<', $id)->where('status', 1)->order('id', 'desc')->field('id,title')->find();
        $next = Db::name('content')->where('id', '>', $id)->where('status', 1)->order('id', 'asc')->field('id,title')->find();
        // 推荐
        $recommend = Db::name('content')->where('status', 1)->where('category_id', $content['category_id'] ?? 0)->where('id', '<>', $id)->order('view_count', 'desc')->limit(5)->field('id,title,cover')->select()->toArray();

        return $this->success([
            'detail' => $content,
            'prev' => $prev,
            'next' => $next,
            'recommend' => $recommend,
        ]);
    }
}
