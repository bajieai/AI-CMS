<?php
declare(strict_types=1);

namespace app\api\controller\h5;

use think\facade\Db;
use think\response\Json;

/**
 * H5搜索接口
 */
class SearchController extends H5BaseController
{
    /**
     * 全文搜索
     */
    public function search(): Json
    {
        $keyword = trim($this->request->param('keyword', ''));
        $page = (int)$this->request->param('page', 1);
        $limit = (int)$this->request->param('limit', 10);
        if (!$keyword) {
            return $this->error('请输入搜索关键词');
        }
        $query = Db::name('content')->where('status', 1)->whereLike('title|description|content', '%' . $keyword . '%');
        $total = $query->count();
        $list = $query->order('create_time', 'desc')->page($page, $limit)->field('id,title,cover,description,create_time')->select()->toArray();
        // 记录搜索历史
        if ($this->memberId) {
            Db::name('search_history')->insert(['member_id' => $this->memberId, 'keyword' => $keyword, 'create_time' => date('Y-m-d H:i:s')]);
        }
        // 更新热搜词
        $hotKey = 'h5_hot_search_' . $keyword;
        \think\facade\Cache::inc($hotKey);
        if (\think\facade\Cache::get($hotKey) === 1) {
            \think\facade\Cache::expire($hotKey, 86400);
        }
        return $this->success(['list' => $list, 'total' => $total, 'page' => $page]);
    }

    /**
     * 热门搜索
     */
    public function hot(): Json
    {
        // 从缓存获取热搜词（简化实现）
        $hot = Db::name('search_history')->field('keyword, COUNT(*) as count')->group('keyword')->order('count', 'desc')->limit(10)->select()->toArray();
        return $this->success($hot);
    }

    /**
     * 搜索建议
     */
    public function suggest(): Json
    {
        $keyword = trim($this->request->param('keyword', ''));
        if (!$keyword) {
            return $this->success([]);
        }
        $list = Db::name('content')->where('status', 1)->whereLike('title', '%' . $keyword . '%')->limit(5)->column('title');
        return $this->success($list);
    }
}
